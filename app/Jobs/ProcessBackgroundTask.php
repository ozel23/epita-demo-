<?php

namespace App\Jobs;

use App\Exports\RowsExport;
use App\Imports\Iw47Import;
use App\Models\BackgroundTask;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\ReportService;
use App\Services\SapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
class ProcessBackgroundTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $taskId;
    public $tries = 1;
    public $timeout = 300;
    public function __construct(int $id)
    {
        $this->taskId = $id;
    }
    public function handle()
    {
        $task = BackgroundTask::findOrFail($this->taskId);
        $user = User::findOrFail($task->user_id);
        abort_unless($user->active, 403);
        $previousUser = Auth::user();
        Auth::onceUsingId($user->id);
        try {
            $task->update(['status' => 'RUNNING']);
            $params = $task->parameters;
            if ($task->type === 'SAP_IMPORT') {
                $import = new Iw47Import();
                Excel::import($import, Storage::disk('local')->path($params['source_path']));
                $batch = app(SapService::class)->stage($import->rows, $task->plant_id, $params['filename'], $user, $params['mapping'] ?? []);
                $params['batch_id'] = $batch->id;
                Storage::disk('local')->delete($params['source_path']);
                $task->update(['parameters' => $params, 'status' => 'COMPLETED']);
                app(NotificationService::class)->send($user, 'Import IW47 selesai. Periksa staging batch #' . $batch->id, '/sap/imports/' . $batch->id);
            } else {
                abort_unless($user->can('report.export'), 403);
                $rows = app(ReportService::class)->rows($params['report_type'], $user, $params['filters']);
                $headers = $params['report_type'] === 'iw47' ? SapService::COLUMNS : array_keys($rows[0] ?? []);
                $path = 'exports/' . bin2hex(random_bytes(16)) . '.xlsx';
                Excel::store(new RowsExport($rows, $headers), $path, 'local');
                $task->update(['path' => $path, 'status' => 'COMPLETED']);
                AuditService::record('report.export.queued', null, [], ['task_id' => $task->id, 'rows' => count($rows), 'filters' => $params['filters']]);
                app(NotificationService::class)->send($user, 'Ekspor laporan selesai.', '/background-tasks');
            }
        } finally {
            if ($previousUser) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetGuards();
            }
        }
    }
    public function failed(\Throwable $e)
    {
        $task = BackgroundTask::find($this->taskId);
        if ($task) {
            $task->update(['status' => 'FAILED', 'error' => 'Proses gagal. Periksa hak akses dan format data, lalu coba kembali.']);
            $user = User::find($task->user_id);
            if ($user) {
                app(NotificationService::class)->send($user, 'Proses latar belakang gagal. Periksa daftar tugas.', '/background-tasks');
            }
        }
    }
}
