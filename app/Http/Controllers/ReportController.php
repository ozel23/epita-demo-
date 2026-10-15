<?php

namespace App\Http\Controllers;

use App\Exports\RowsExport;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\ReportService;
use App\Services\SapService;
use App\Support\FilterOptions;
use App\Support\PlantAccess;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
class ReportController extends Controller
{
    public function index(Request $r, ReportService $service, string $type = 'daily')
    {
        abort_unless($r->user()->can('report.export'), 403);
        $filters = $r->validate(['from' => 'nullable|date', 'to' => 'nullable|date' . ($r->filled('from') ? '|after_or_equal:from' : ''), 'plant_id' => 'nullable|integer', 'company_id' => 'nullable|integer', 'division_id' => 'nullable|integer', 'equipment_id' => 'nullable|integer', 'equipment_type_id' => 'nullable|integer', 'ownership' => 'nullable|in:OWN,LEND,RENT', 'main_operator_id' => 'nullable|integer', 'status' => 'nullable|string|max:40', 'format' => 'nullable|in:xlsx,csv,pdf']);
        $rows = $service->rows($type, $r->user(), $filters);
        $headers = $type === 'iw47' ? SapService::COLUMNS : array_keys($rows[0] ?? []);
        $title = ReportService::TYPES[$type];
        $summaries = $service->summaries($rows, $type);
        $exportRows = $rows;
        foreach ($summaries as $summary) {
            $line = [];
            foreach ($headers as $i => $header) {
                $line[$header] = $i === 0 ? $summary['label'] : $summary[$header] ?? null;
            }
            $exportRows[] = $line;
        }
        if ($r->filled('format')) {
            AuditService::record('report.export', null, [], ['type' => $type, 'filters' => $filters, 'count' => count($rows)]);
            if (in_array($r->format, ['xlsx', 'csv'])) {
                return Excel::download(new RowsExport($exportRows, $headers, $r->format === 'csv'), $type . '.' . $r->format, null, ['Content-Type' => $r->format === 'csv' ? 'text/csv; charset=UTF-8' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
            }
            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $pdf = new Dompdf($options);
            $pdf->loadHtml(view('reports.print', compact('rows', 'headers', 'title', 'filters', 'summaries'))->render());
            $pdf->setPaper('a4', 'landscape');
            $pdf->render();
            return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="' . $type . '.pdf"']);
        }
        $page = max(1, (int) $r->get('page', 1));
        $records = new LengthAwarePaginator(array_slice($rows, ($page - 1) * 20, 20), count($rows), 20, $page, ['path' => $r->url(), 'query' => $r->query()]);
        return \App\Support\WebPage::render('reports.index', compact('records', 'headers', 'title', 'filters', 'type', 'rows', 'summaries') + ['options' => FilterOptions::forUser($r->user())]);
    }
    public function audit(Request $r)
    {
        abort_unless($r->user()->can('audit.view'), 403);
        $logs = AuditLog::where(function ($q) use ($r) {
            $q->whereIn('plant_id', PlantAccess::ids($r->user()))->orWhere(function ($q) use ($r) {
                $q->whereNull('plant_id')->where('user_id', $r->user()->id);
            });
        })->latest()->paginate(30);
        return \App\Support\WebPage::render('audit', compact('logs'));
    }
}
