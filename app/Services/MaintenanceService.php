<?php

namespace App\Services;

use App\Models\BreakdownReport;
use App\Models\Equipment;
use App\Models\EquipmentStatusHistory;
use App\Models\Location;
use App\Models\MaintenanceWorkOrder;
use App\Models\Personnel;
use App\Models\SparePart;
use App\Support\PlantAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class MaintenanceService
{
    public function condition(Equipment $e, string $condition, $user, string $reason)
    {
        $from = $e->current_condition;
        $e->update(['current_condition' => $condition]);
        EquipmentStatusHistory::create(['equipment_id' => $e->id, 'actor_id' => $user->id, 'from_condition' => $from, 'to_condition' => $condition, 'reason' => $reason]);
        AuditService::record('equipment.condition', $e, ['condition' => $from], ['condition' => $condition, 'reason' => $reason]);
    }
    public function breakdown(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            abort_unless($user->can('breakdown.create'), 403);
            PlantAccess::check($user, $data['plant_id']);
            $e = Equipment::where('plant_id', $data['plant_id'])->where('active', true)->lockForUpdate()->findOrFail($data['equipment_id']);
            Location::where('plant_id', $e->plant_id)->where('active', true)->findOrFail($data['location_id']);
            $data['occurred_at']=Carbon::parse($data['occurred_at'])->setTimezone(config('app.timezone'));
            $r = BreakdownReport::create($data + ['number' => 'BD-' . strtoupper(bin2hex(random_bytes(6))), 'reported_by' => $user->id]);
            $this->condition($e, 'BREAKDOWN', $user, $r->number);
            AuditService::record('breakdown.created', $r, [], $r->toArray());
            return $r;
        });
    }
    public function create(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            abort_unless($user->can('maintenance.manage'), 403);
            PlantAccess::check($user, $data['plant_id']);
            $e = Equipment::where('plant_id', $data['plant_id'])->lockForUpdate()->findOrFail($data['equipment_id']);
            $b = null;
            if (!empty($data['breakdown_report_id'])) {
                $b = BreakdownReport::where('plant_id', $e->plant_id)->where('equipment_id', $e->id)->where('status', '!=', 'CLOSED')->findOrFail($data['breakdown_report_id']);
            }
            if (MaintenanceWorkOrder::where('equipment_id', $e->id)->whereNotIn('status', ['CLOSED', 'CANCELLED'])->exists()) {
                throw ValidationException::withMessages(['equipment_id' => 'Alat masih memiliki WO terbuka.']);
            }
            $technicians = $data['technicians'] ?? [];
            unset($data['technicians']);
            $wo = MaintenanceWorkOrder::create($data + ['created_by' => $user->id, 'number' => 'WO-' . strtoupper(bin2hex(random_bytes(6))), 'unavailable_at' => $b ? $b->occurred_at : now()]);
            foreach ($technicians as $id) {
                Personnel::where('plant_id', $e->plant_id)->where('active', true)->where('type', 'MECHANIC')->findOrFail($id);
                $wo->technicians()->create(['personnel_id' => $id]);
            }
            AuditService::record('maintenance.created', $wo, [], $wo->toArray());
            return $wo;
        });
    }
    public function transition(MaintenanceWorkOrder $order, string $action, array $data, $user)
    {
        return DB::transaction(function () use ($order, $action, $data, $user) {
            abort_unless($user->can('maintenance.manage'), 403);
            PlantAccess::check($user, $order->plant_id);
            $e = Equipment::lockForUpdate()->findOrFail($order->equipment_id);
            $wo = MaintenanceWorkOrder::lockForUpdate()->findOrFail($order->id);
            $from = $wo->status;
            $expected = ['start' => 'OPEN', 'complete' => 'IN_PROGRESS', 'close' => 'COMPLETED'];
            if (!isset($expected[$action]) || $from !== $expected[$action]) {
                throw ValidationException::withMessages(['status' => 'Transisi WO tidak valid.']);
            }
            if ($action === 'start') {
                if (!$wo->technicians()->exists()) {
                    throw ValidationException::withMessages(['technicians' => 'Teknisi wajib ditugaskan.']);
                }
                $wo->started_at = now();
                $wo->status = 'IN_PROGRESS';
                $this->condition($e, 'MAINTENANCE', $user, $wo->number);
            }
            if ($action === 'complete') {
                if (empty($data['diagnosis']) || empty($data['result']) || !$wo->actions()->exists()) {
                    throw ValidationException::withMessages(['result' => 'Diagnosis, tindakan, dan hasil testing wajib diisi.']);
                }
                $wo->fill(collect($data)->only(['diagnosis', 'result', 'next_service_date', 'next_service_meter'])->all());
                $wo->ended_at = now();
                $wo->status = 'COMPLETED';
            }
            if ($action === 'close') {
                abort_unless($user->can('maintenance.close'), 403);
                $condition = $data['condition'] ?? 'STANDBY';
                if (!in_array($condition, ['STANDBY', 'WORKING'])) {
                    throw ValidationException::withMessages(['condition' => 'Kondisi release tidak valid.']);
                }
                $wo->released_at = now();
                $wo->closed_by = $user->id;
                $wo->downtime_minutes = Carbon::parse($wo->unavailable_at)->diffInMinutes(now());
                $wo->status = 'CLOSED';
                $this->condition($e, $condition, $user, 'Release ' . $wo->number);
                if ($wo->breakdown) {
                    $wo->breakdown->update(['status' => 'CLOSED']);
                }
            }
            $wo->save();
            AuditService::record('maintenance.' . $action, $wo, ['status' => $from], $wo->toArray());
            return $wo;
        });
    }
    public function action(MaintenanceWorkOrder $wo, array $data, $user)
    {
        return DB::transaction(function () use ($wo, $data, $user) {
            abort_unless($user->can('maintenance.manage'), 403);
            PlantAccess::check($user, $wo->plant_id);
            $wo = MaintenanceWorkOrder::lockForUpdate()->findOrFail($wo->id);
            abort_unless($wo->status === 'IN_PROGRESS', 422);
            Personnel::where('plant_id', $wo->plant_id)->where('type', 'MECHANIC')->where('active', true)->findOrFail($data['technician_id']);
            $record = $wo->actions()->create(collect($data)->only(['technician_id', 'description', 'labor_minutes'])->all());
            foreach ($data['parts'] ?? [] as $part) {
                SparePart::where('active', true)->findOrFail($part['spare_part_id']);
                $wo->parts()->create($part);
            }
            AuditService::record('maintenance.action', $wo, [], $record->toArray());
            return $record;
        });
    }
}
