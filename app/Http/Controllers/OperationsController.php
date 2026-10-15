<?php

namespace App\Http\Controllers;

use App\Models\ActivityCode;
use App\Models\Assignment;
use App\Models\BreakdownReport;
use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\Location;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceWorkOrder;
use App\Models\Personnel;
use App\Models\Plant;
use App\Models\SparePart;
use App\Models\WorkPlan;
use App\Models\WorkUnit;
use App\Services\AssignmentService;
use App\Services\AttachmentService;
use App\Services\AuditService;
use App\Services\MaintenanceService;
use App\Support\PlantAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class OperationsController extends Controller
{
    private function options($u)
    {
        return ['equipment' => PlantAccess::query(Equipment::where('active', true), $u)->get(), 'personnels' => PlantAccess::query(Personnel::where('active', true), $u)->get(), 'locations' => PlantAccess::query(Location::where('active', true), $u)->get(), 'activities' => ActivityCode::where('active', true)->get(), 'units' => WorkUnit::where('active', true)->get(), 'plans' => PlantAccess::query(WorkPlan::query(), $u)->get(), 'breakdowns' => PlantAccess::query(BreakdownReport::where('status', '!=', 'CLOSED'), $u)->get(), 'parts' => SparePart::where('active', true)->get()];
    }
    public function index(Request $r, string $module)
    {
        $classes = ['work-plans' => WorkPlan::class, 'assignments' => Assignment::class, 'breakdowns' => BreakdownReport::class, 'work-orders' => MaintenanceWorkOrder::class];
        abort_unless(isset($classes[$module]), 404);
        $this->access($r->user(), $module);
        $q = PlantAccess::query($classes[$module]::query(), $r->user());
        if ($module === 'assignments' && !$r->user()->can('assignment.manage')) {
            $q->whereHas('operator', function ($q) use ($r) {
                $q->where('user_id', $r->user()->id);
            });
        }
        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }
        $records = $q->latest()->paginate(15);
        return \App\Support\WebPage::render('operations.index', $this->options($r->user()) + compact('module', 'records'));
    }
    private function access($u, $module)
    {
        if ($module === 'work-plans') {
            abort_unless($u->can('assignment.manage') || $u->can('report.export'), 403);
        }
        if (in_array($module, ['breakdowns', 'work-orders'])) {
            abort_unless($u->can('maintenance.manage') || $u->can('breakdown.create') || $u->can('report.export'), 403);
        }
    }
    public function plan(Request $r)
    {
        abort_unless($r->user()->can('assignment.manage'), 403);
        $d = $r->validate(['plant_id' => 'required|integer', 'location_id' => 'required|integer', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date', 'notes' => 'nullable|string|max:2000', 'activity_code_id' => 'required|exists:activity_codes,id', 'work_unit_id' => 'required|exists:work_units,id', 'target_volume' => 'required|numeric|gt:0', 'equipment_requirement' => 'required|integer|min:1']);
        PlantAccess::check($r->user(), $d['plant_id']);
        $l = Location::where('plant_id', $d['plant_id'])->where('active', true)->findOrFail($d['location_id']);
        DB::transaction(function () use ($d, $l, $r) {
            $p = WorkPlan::create(collect($d)->only(['plant_id', 'location_id', 'start_date', 'end_date', 'notes'])->all() + ['company_id' => $l->plant_id ? Plant::findOrFail($l->plant_id)->company_id : null, 'division_id' => $l->division_id, 'created_by' => $r->user()->id, 'number' => 'WP-' . strtoupper(bin2hex(random_bytes(6)))]);
            $p->items()->create(collect($d)->only(['activity_code_id', 'work_unit_id', 'target_volume', 'equipment_requirement'])->all());
            AuditService::record('work-plan.created', $p, [], $p->toArray());
        });
        return back()->with('success', 'Rencana kerja tersimpan.');
    }
    public function assignment(Request $r, AssignmentService $s)
    {
        $d = $r->validate(['plant_id' => 'required|integer', 'work_plan_id' => 'nullable|integer', 'equipment_id' => 'required|integer', 'operator_id' => 'required|integer', 'planned_start_at' => 'required|date', 'planned_end_at' => 'required|date|after:planned_start_at', 'shift' => 'required|string|max:20', 'override_reason' => 'nullable|string|max:1000', 'helpers' => 'nullable|array|max:4', 'helpers.*' => 'integer|distinct']);
        $s->create($d, $r->user());
        return back()->with('success', 'Penugasan tersimpan.');
    }
    public function breakdown(Request $r, MaintenanceService $s)
    {
        $d = $r->validate(['client_uuid'=>'nullable|uuid|unique:breakdown_reports,client_uuid', 'plant_id' => 'required|integer', 'equipment_id' => 'required|integer', 'location_id' => 'required|integer', 'occurred_at' => 'required|date|before_or_equal:now', 'meter' => 'required|numeric|min:0', 'symptom' => 'required|string|max:255', 'severity' => 'required|in:LOW,MEDIUM,HIGH,CRITICAL', 'description' => 'required|string|max:5000', 'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120']);
        $evidence = app(AttachmentService::class)->store($r->file('file'));
        unset($d['file']);
        $b = DB::transaction(function () use ($d, $r, $s, $evidence) {
            $b = $s->breakdown($d, $r->user());
            $b->attachments()->create($evidence + ['uploaded_by' => $r->user()->id]);
            return $b;
        });
        return $r->expectsJson() ? response()->json(['data' => $b], 201) : back()->with('success', 'Breakdown dilaporkan.');
    }
    public function workOrder(Request $r, MaintenanceService $s)
    {
        $d = $r->validate(['plant_id' => 'required|integer', 'equipment_id' => 'required|integer', 'breakdown_report_id' => 'nullable|integer', 'type' => 'required|in:CORRECTIVE,PREVENTIVE,INSPECTION', 'priority' => 'required|in:LOW,MEDIUM,HIGH,CRITICAL', 'sap_order_number' => 'nullable|string|max:255', 'technicians' => 'required|array|min:1', 'technicians.*' => 'integer|distinct']);
        $s->create($d, $r->user());
        return back()->with('success', 'Work order dibuat.');
    }
    public function showOrder(Request $r, MaintenanceWorkOrder $order)
    {
        PlantAccess::check($r->user(), $order->plant_id);
        $this->access($r->user(), 'work-orders');
        return \App\Support\WebPage::render('operations.order', $this->options($r->user()) + ['order' => $order->load('equipment', 'actions', 'parts', 'technicians'), 'history' => \App\Models\AuditLog::where('subject_type', MaintenanceWorkOrder::class)->where('subject_id', $order->id)->orderBy('id')->get()]);
    }
    public function transition(Request $r, MaintenanceWorkOrder $order, string $action, MaintenanceService $s)
    {
        $d = $r->validate(['diagnosis' => 'nullable|string|max:5000', 'result' => 'nullable|string|max:5000', 'condition' => 'nullable|in:WORKING,STANDBY', 'next_service_date' => 'nullable|date', 'next_service_meter' => 'nullable|numeric|min:0']);
        $s->transition($order, $action, $d, $r->user());
        return back()->with('success', 'Status maintenance diperbarui.');
    }
    public function maintenanceAction(Request $r, MaintenanceWorkOrder $order, MaintenanceService $s)
    {
        $d = $r->validate(['technician_id' => 'required|integer', 'description' => 'required|string|max:5000', 'labor_minutes' => 'required|integer|min:0', 'parts' => 'nullable|array', 'parts.*.spare_part_id' => 'required|integer', 'parts.*.quantity' => 'required|numeric|gt:0', 'parts.*.unit_cost' => 'required|numeric|min:0', 'parts.*.warehouse_reference' => 'nullable|string|max:255']);
        $s->action($order, $d, $r->user());
        return back()->with('success', 'Tindakan dicatat.');
    }
    public function cancelAssignment(Request $r, Assignment $assignment)
    {
        abort_unless($r->user()->can('assignment.manage'), 403);
        PlantAccess::check($r->user(), $assignment->plant_id);
        $r->validate(['note' => 'required|string|max:1000']);
        abort_if(DailyReport::where('assignment_id', $assignment->id)->exists(), 422, 'Penugasan memiliki realisasi.');
        $assignment->update(['status' => 'CANCELLED']);
        AuditService::record('assignment.cancelled', $assignment, [], ['note' => $r->note]);
        return back()->with('success', 'Penugasan dibatalkan.');
    }
    public function attachment(Request $r, MaintenanceWorkOrder $order)
    {
        abort_unless($r->user()->can('maintenance.manage'), 403);
        PlantAccess::check($r->user(), $order->plant_id);
        $r->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120']);
        DB::transaction(function () use ($r, $order) {
            $order = MaintenanceWorkOrder::lockForUpdate()->findOrFail($order->id);
            abort_unless(in_array($order->status, ['OPEN', 'IN_PROGRESS', 'COMPLETED']), 422);
            $order->attachments()->create(app(AttachmentService::class)->store($r->file('file')) + ['uploaded_by' => $r->user()->id]);
            AuditService::record('maintenance.attachment', $order);
        });
        return back()->with('success', 'Bukti maintenance tersimpan.');
    }
    public function downloadAttachment(Request $r, MaintenanceAttachment $attachment)
    {
        $order = $attachment->order;
        PlantAccess::check($r->user(), $order->plant_id);
        $this->access($r->user(), 'work-orders');
        return Storage::disk('local')->response($attachment->path);
    }
}
