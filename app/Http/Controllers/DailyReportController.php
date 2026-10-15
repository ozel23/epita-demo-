<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyReportRequest;
use App\Models\ActivityCode;
use App\Models\Assignment;
use App\Models\DailyReport;
use App\Models\DailyReportAttachment;
use App\Models\Equipment;
use App\Models\FuelingLocation;
use App\Models\FuelType;
use App\Models\LoadType;
use App\Models\Location;
use App\Models\NonMovingReason;
use App\Models\Personnel;
use App\Models\WorkUnit;
use App\Services\AttachmentService;
use App\Services\AuditService;
use App\Services\DailyReportService;
use App\Support\PlantAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class DailyReportController extends Controller
{
    public function index(Request $r)
    {
        $q = PlantAccess::query(DailyReport::with('equipment', 'operator'), $r->user());
        if (!$r->user()->can('daily-report.view-all')) {
            $q->where('created_by', $r->user()->id);
        }
        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }
        if ($r->filled('from')) {
            $q->whereDate('report_date', '>=', $r->from);
        }
        if ($r->filled('to')) {
            $q->whereDate('report_date', '<=', $r->to);
        }
        if ($r->filled('equipment_id')) {
            $q->where('equipment_id', $r->equipment_id);
        }
        $reports = $q->latest('report_date')->paginate(15)->withQueryString();
        return $r->expectsJson() ? response()->json($reports) : \App\Support\WebPage::render('daily-reports.index', compact('reports'));
    }
    public function approvals(Request $r)
    {
        abort_unless($r->user()->can('daily-report.verify') || $r->user()->can('daily-report.approve'), 403);
        $r->merge(['status' => $r->get('status', 'SUBMITTED')]);
        return $this->index($r);
    }
    private function options($user)
    {
        $scope = function ($q) use ($user) {
            return PlantAccess::query($q, $user)->where('active', true)->get();
        };
        $operators = PlantAccess::query(Personnel::where('active', true)->whereIn('type', ['OPERATOR', 'DRIVER']), $user);
        if (!$user->can('daily-report.view-all')) {
            $operators->where('user_id', $user->id);
        }
        return ['equipment' => $scope(Equipment::query()), 'operators' => $operators->get(), 'locations' => $scope(Location::query()), 'helpers' => $scope(Personnel::where('type', 'HELPER')), 'activities' => ActivityCode::where('active', true)->get(), 'units' => WorkUnit::where('active', true)->get(), 'fuelTypes' => FuelType::where('active', true)->get(), 'fuelLocations' => $scope(FuelingLocation::query()), 'loadTypes' => LoadType::where('active', true)->get(), 'reasons' => NonMovingReason::where('active', true)->get(), 'assignments' => PlantAccess::query(Assignment::whereNotIn('status', ['COMPLETED', 'CANCELLED']), $user)->get()];
    }
    public function create(Request $r)
    {
        abort_unless($r->user()->can('daily-report.create'), 403);
        return \App\Support\WebPage::render('daily-reports.form', $this->options($r->user()) + ['report' => new DailyReport()]);
    }
    public function edit(Request $r, DailyReport $dailyReport)
    {
        $this->authorize('update', $dailyReport);
        return \App\Support\WebPage::render('daily-reports.form', $this->options($r->user()) + ['report' => $dailyReport->load('activities', 'fuels', 'helpers')]);
    }
    public function store(DailyReportRequest $r, DailyReportService $s)
    {
        $report = $s->save($r->validated(), $r->user());
        return $r->expectsJson() ? response()->json(['data' => $report], 201) : redirect('/daily-reports/' . $report->id)->with('success', 'Draft tersimpan.');
    }
    public function update(DailyReportRequest $r, DailyReport $dailyReport, DailyReportService $s)
    {
        $this->authorize('update', $dailyReport);
        $report = $s->save($r->validated(), $r->user(), $dailyReport);
        return $r->expectsJson() ? response()->json(['data' => $report]) : redirect('/daily-reports/' . $report->id)->with('success', 'Draft diperbarui.');
    }
    public function show(Request $r, DailyReport $dailyReport)
    {
        $this->authorize('view', $dailyReport);
        $report = $dailyReport->load('equipment', 'operator', 'location', 'activities.activity', 'activities.unit', 'fuels', 'helpers', 'attachments', 'approvals');
        return $r->expectsJson() ? response()->json(['data' => $report]) : \App\Support\WebPage::render('daily-reports.show', compact('report'));
    }
    public function transition(Request $r, DailyReport $dailyReport, string $action, DailyReportService $s)
    {
        $data = $r->validate(['note' => 'nullable|string|max:2000']);
        $report = $s->transition($dailyReport, $action, $r->user(), $data['note'] ?? null);
        return $r->expectsJson() ? response()->json(['data' => $report]) : back()->with('success', 'Status laporan: ' . $report->status);
    }
    public function attachment(Request $r, DailyReport $dailyReport)
    {
        $this->authorize('update', $dailyReport);
        $r->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120']);
        $attachment = DB::transaction(function () use ($r, $dailyReport) {
            $dailyReport = DailyReport::lockForUpdate()->findOrFail($dailyReport->id);
            $this->authorize('update', $dailyReport);
            $data = app(AttachmentService::class)->store($r->file('file'));
            return $dailyReport->attachments()->create($data + ['uploaded_by' => $r->user()->id]);
        });
        AuditService::record('attachment.created', $dailyReport, [], ['attachment_id' => $attachment->id]);
        return $r->expectsJson() ? response()->json(['data' => $attachment], 201) : back()->with('success', 'Bukti tersimpan secara privat.');
    }
    public function download(Request $r, DailyReportAttachment $attachment)
    {
        $report = DailyReport::findOrFail($attachment->daily_report_id);
        $this->authorize('view', $report);
        return Storage::disk('local')->response($r->boolean('thumbnail') && $attachment->thumbnail_path ? $attachment->thumbnail_path : $attachment->path, null, ['X-Content-Type-Options' => 'nosniff']);
    }
}
