<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBackgroundTask;
use App\Models\BackgroundTask;
use App\Services\AuditService;
use App\Services\ReportService;
use App\Support\PlantAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class BackgroundTaskController extends Controller
{
    public function index(Request $r)
    {
        $tasks = BackgroundTask::where('user_id', $r->user()->id)->latest()->paginate(20);
        return \App\Support\WebPage::render('background-tasks', compact('tasks'));
    }
    public function export(Request $r)
    {
        abort_unless($r->user()->can('report.export'), 403);
        $d = $r->validate(['report_type' => 'required|in:' . implode(',', array_keys(ReportService::TYPES)), 'plant_id' => 'required|integer', 'from' => 'nullable|date', 'to' => 'nullable|date' . ($r->filled('from') ? '|after_or_equal:from' : ''), 'equipment_id' => 'nullable|integer']);
        PlantAccess::check($r->user(), $d['plant_id']);
        $task = BackgroundTask::create(['user_id' => $r->user()->id, 'plant_id' => $d['plant_id'], 'type' => 'REPORT_EXPORT', 'parameters' => ['report_type' => $d['report_type'], 'filters' => collect($d)->except('report_type')->all()]]);
        ProcessBackgroundTask::dispatch($task->id);
        return redirect('/background-tasks')->with('success', 'Ekspor masuk antrean. Notifikasi dikirim setelah selesai.');
    }
    public function download(Request $r, BackgroundTask $task)
    {
        abort_unless($task->user_id === $r->user()->id && $r->user()->can('report.export'), 403);
        PlantAccess::check($r->user(), $task->plant_id);
        abort_unless($task->status === 'COMPLETED' && $task->path, 404);
        AuditService::record('report.download', null, [], ['task_id' => $task->id]);
        return Storage::disk('local')->download($task->path, 'laporan-' . $task->id . '.xlsx');
    }
}
