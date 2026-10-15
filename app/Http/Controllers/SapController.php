<?php

namespace App\Http\Controllers;

use App\Exports\RowsExport;
use App\Imports\Iw47Import;
use App\Jobs\ProcessBackgroundTask;
use App\Models\ActivityCode;
use App\Models\BackgroundTask;
use App\Models\SapImportBatch;
use App\Models\SapMappingRule;
use App\Models\SapReconciliation;
use App\Services\AuditService;
use App\Services\SapService;
use App\Support\PlantAccess;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
class SapController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->can('sap.import'), 403);
        $batches = PlantAccess::query(SapImportBatch::query(), $r->user())->latest()->paginate(15);
        return \App\Support\WebPage::render('sap.index', compact('batches'));
    }
    public function store(Request $r, SapService $s)
    {
        abort_unless($r->user()->can('sap.import'), 403);
        $d = $r->validate(['plant_id' => 'required|integer', 'file' => 'required|file|mimes:csv,txt,xlsx|max:10240', 'mapping' => 'nullable|json', 'background' => 'nullable|boolean']);
        PlantAccess::check($r->user(), $d['plant_id']);
        $mapping = json_decode($d['mapping'] ?? '{}', true);
        if ($r->boolean('background')) {
            $task = BackgroundTask::create(['user_id' => $r->user()->id, 'plant_id' => $d['plant_id'], 'type' => 'SAP_IMPORT', 'parameters' => ['source_path' => $r->file('file')->store('imports', 'local'), 'filename' => $r->file('file')->getClientOriginalName(), 'mapping' => $mapping]]);
            ProcessBackgroundTask::dispatch($task->id);
            return redirect('/background-tasks')->with('success', 'Import masuk antrean.');
        }
        $import = new Iw47Import();
        Excel::import($import, $r->file('file'));
        $b = $s->stage($import->rows, $d['plant_id'], $r->file('file')->getClientOriginalName(), $r->user(), $mapping);
        return redirect('/sap/imports/' . $b->id)->with('success', 'File masuk staging. Periksa preview sebelum commit.');
    }
    public function show(Request $r, SapImportBatch $batch)
    {
        abort_unless($r->user()->can('sap.import'), 403);
        PlantAccess::check($r->user(), $batch->plant_id);
        $rows = $batch->rows()->paginate(30);
        return \App\Support\WebPage::render('sap.show', compact('batch', 'rows'));
    }
    public function action(Request $r, SapImportBatch $batch, string $action, SapService $s)
    {
        abort_unless(in_array($action, ['commit', 'rollback', 'reconcile']), 404);
        $s->{$action}($batch, $r->user());
        return back()->with('success', 'Batch: ' . $action . ' berhasil.');
    }
    public function reconciliation(Request $r)
    {
        abort_unless($r->user()->can('sap.reconcile'), 403);
        $q = PlantAccess::query(SapReconciliation::with('report', 'row'), $r->user());
        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }
        if ($r->filled('batch_id')) {
            $q->where('sap_import_batch_id', $r->batch_id);
        }
        $records = $q->latest()->paginate(20)->withQueryString();
        $mappings = PlantAccess::query(SapMappingRule::query(), $r->user())->get();
        $activities = ActivityCode::where('active', true)->get();
        return \App\Support\WebPage::render('sap.reconciliation', compact('records', 'mappings', 'activities'));
    }
    public function mapping(Request $r)
    {
        abort_unless($r->user()->can('sap.reconcile'), 403);
        $d = $r->validate(['plant_id' => 'required|integer', 'activity_code_id' => 'required|exists:activity_codes,id', 'order_number' => 'required|string|max:255', 'operation_activity' => 'required|string|max:20', 'work_center' => 'nullable|string|max:255', 'actual_work_unit' => 'required|string|max:20', 'value_source' => 'required|in:meter_total,work_hours,volume', 'tolerance' => 'required|numeric|min:0']);
        PlantAccess::check($r->user(), $d['plant_id']);
        $m = SapMappingRule::updateOrCreate(collect($d)->only('plant_id', 'activity_code_id')->all(), $d);
        AuditService::record('sap.mapping', $m, [], $d);
        return back()->with('success', 'Mapping tersimpan. Jalankan rekonsiliasi ulang.');
    }
    public function review(Request $r, SapReconciliation $reconciliation)
    {
        abort_unless($r->user()->can('sap.reconcile'), 403);
        PlantAccess::check($r->user(), $reconciliation->plant_id);
        $d = $r->validate(['reason' => 'required|string|max:2000']);
        $reconciliation->update($d + ['reviewed_by' => $r->user()->id, 'reviewed_at' => now()]);
        AuditService::record('sap.review', $reconciliation, [], $d);
        return back()->with('success', 'Review dicatat; raw staging tetap utuh.');
    }
    public function template(Request $r)
    {
        abort_unless($r->user()->can('sap.import'), 403);
        return Excel::download(new RowsExport([], SapService::COLUMNS), 'template-iw47.xlsx');
    }
}
