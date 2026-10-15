<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\DailyReportActivity;
use App\Models\DailyReportFuel;
use App\Models\Equipment;
use App\Models\MaintenanceWorkOrder;
use App\Models\SapReconciliation;
use App\Support\PlantAccess;
use Carbon\Carbon;
class DashboardService
{
    public function data($user, array $filters = [])
    {
        $reports = PlantAccess::query(DailyReport::query(), $user);
        $equipment = PlantAccess::query(Equipment::where('active', true), $user);
        foreach (['plant_id', 'company_id', 'division_id'] as $key) {
            if (!empty($filters[$key])) {
                $reports->where($key, $filters[$key]);
            }
        }
        foreach (['plant_id', 'equipment_type_id', 'ownership'] as $key) {
            if (!empty($filters[$key])) {
                $equipment->where($key, $filters[$key]);
            }
        }
        if (!empty($filters['company_id'])) {
            $equipment->whereHas('plant', function ($q) use ($filters) {
                $q->where('company_id', $filters['company_id']);
            });
        }
        $equipmentIds = (clone $equipment)->pluck('id');
        $reports->whereIn('equipment_id', $equipmentIds);
        if (!empty($filters['from'])) {
            $reports->whereDate('report_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $reports->whereDate('report_date', '<=', $filters['to']);
        }
        if (!$user->can('daily-report.view-all')) {
            $reports->where('created_by', $user->id);
        }
        $approved = (clone $reports)->where('status', 'APPROVED');
        $ids = (clone $approved)->pluck('id');
        $conditions = (clone $equipment)->selectRaw('current_condition, count(*) as total')->groupBy('current_condition')->pluck('total', 'current_condition');
        $trend = (clone $approved)->selectRaw('report_date, meter_type, sum(meter_total) as meter_total, sum(work_minutes)/60.0 as hours')->groupBy('report_date', 'meter_type')->orderBy('report_date')->get();
        $downtime = PlantAccess::query(MaintenanceWorkOrder::query(), $user)->whereIn('equipment_id', $equipmentIds);
        if (!empty($filters['from'])) {
            $downtime->whereDate('released_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $downtime->whereDate('released_at', '<=', $filters['to']);
        }
        $fuelTrend = DailyReportFuel::join('daily_reports', 'daily_reports.id', '=', 'daily_report_fuels.daily_report_id')->whereIn('daily_report_id', $ids)->selectRaw('report_date, sum(quantity_used) as liters')->groupBy('report_date')->orderBy('report_date')->get();
        $fuelByEquipment = DailyReportFuel::join('daily_reports', 'daily_reports.id', '=', 'daily_report_fuels.daily_report_id')->join('equipment', 'equipment.id', '=', 'daily_reports.equipment_id')->whereIn('daily_report_id', $ids)->selectRaw('equipment.code, sum(quantity_used) as liters')->groupBy('equipment.code')->orderByDesc('liters')->limit(10)->get();
        $volumeByUnit = DailyReportActivity::join('work_units', 'work_units.id', '=', 'daily_report_activities.work_unit_id')->whereIn('daily_report_id', $ids)->selectRaw('work_units.name as unit, sum(volume) as total')->groupBy('work_units.name')->get();
        $utilization = (clone $approved)->with('equipment')->selectRaw('equipment_id, sum(work_minutes)/60.0 as hours')->groupBy('equipment_id')->get();
        $periodStart = $filters['from'] ?? (clone $approved)->min('report_date');
        $periodEnd = $filters['to'] ?? (clone $approved)->max('report_date');
        $availableHours = $periodStart && $periodEnd ? (Carbon::parse($periodStart)->diffInDays(Carbon::parse($periodEnd)) + 1) * 24 : 24;
        $utilization = $utilization->map(function ($row) use ($availableHours) {
            return ['equipment' => $row->equipment->code, 'hours' => round($row->hours, 2), 'percentage' => round($row->hours / $availableHours * 100, 2)];
        });
        $downtimeByType = (clone $downtime)->selectRaw('type, sum(downtime_minutes)/60.0 as hours')->groupBy('type')->get();
        $reconciliationQuery = PlantAccess::query(SapReconciliation::query(), $user);
        if (!empty($filters['plant_id'])) {
            $reconciliationQuery->where('plant_id', $filters['plant_id']);
        }
        if (!empty($filters['from'])) {
            $reconciliationQuery->whereHas('row', function ($q) use ($filters) {
                $q->whereDate('posting_date', '>=', $filters['from']);
            });
        }
        if (!empty($filters['to'])) {
            $reconciliationQuery->whereHas('row', function ($q) use ($filters) {
                $q->whereDate('posting_date', '<=', $filters['to']);
            });
        }
        $plannedActual = $user->can('report.export') ? app(ReportService::class)->rows('plan', $user, $filters) : [];
        $previousHours = 0;
        $previousFuel = 0;
        if ($periodStart && $periodEnd) {
            $start = Carbon::parse($periodStart)->startOfDay();
            $end = Carbon::parse($periodEnd)->startOfDay();
            $days = $start->diffInDays($end) + 1;
            $previous = PlantAccess::query(DailyReport::where('status', 'APPROVED'), $user)->whereIn('equipment_id', $equipmentIds)->whereDate('report_date', '>=', $start->copy()->subDays($days)->format('Y-m-d'))->whereDate('report_date', '<', $start->format('Y-m-d'));
            foreach (['plant_id', 'company_id', 'division_id'] as $key) {
                if (!empty($filters[$key])) {
                    $previous->where($key, $filters[$key]);
                }
            }
            if (!$user->can('daily-report.view-all')) {
                $previous->where('created_by', $user->id);
            }
            $previousHours = round((clone $previous)->sum('work_minutes') / 60, 2);
            $previousFuel = DailyReportFuel::whereIn('daily_report_id', (clone $previous)->pluck('id'))->sum('quantity_used');
        }
        $comparisons = ['work_hours' => $previousHours > 0 ? round(((clone $approved)->sum('work_minutes') / 60 - $previousHours) / $previousHours * 100, 1) : null, 'fuel' => $previousFuel > 0 ? round((DailyReportFuel::whereIn('daily_report_id', $ids)->sum('quantity_used') - $previousFuel) / $previousFuel * 100, 1) : null];
        $utilizationAverage = round($utilization->avg('percentage') ?? 0, 1);
        $overdueQuery = PlantAccess::query(MaintenanceWorkOrder::query(), $user)->whereIn('equipment_id', $equipmentIds)->whereNotIn('status', ['CLOSED', 'CANCELLED'])->where('unavailable_at', '<', now()->subHours(24));
        foreach (['from', 'to'] as $key) {
            if (!empty($filters[$key])) {
                $overdueQuery->whereDate('unavailable_at', $key === 'from' ? '>=' : '<=', $filters[$key]);
            }
        }
        $overdueMaintenance = $overdueQuery->count();
        $extras = ['comparisons' => $comparisons, 'utilization_average' => $utilizationAverage, 'overdue_maintenance' => $overdueMaintenance] + compact('fuelTrend', 'fuelByEquipment', 'volumeByUnit', 'utilization', 'downtimeByType', 'plannedActual');
        return ['equipment_count' => $equipmentIds->count(), 'conditions' => $conditions, 'km' => (clone $approved)->where('meter_type', 'KM')->sum('meter_total'), 'hm' => (clone $approved)->whereIn('meter_type', ['HM', 'HOUR_KWH'])->sum('meter_total'), 'work_hours' => round((clone $approved)->sum('work_minutes') / 60, 2), 'volume' => DailyReportActivity::whereIn('daily_report_id', $ids)->sum('volume'), 'fuel' => DailyReportFuel::whereIn('daily_report_id', $ids)->sum('quantity_used'), 'awaiting' => (clone $reports)->whereIn('status', ['SUBMITTED', 'VERIFIED'])->count(), 'downtime_minutes' => $downtime->sum('downtime_minutes'), 'trend' => $trend, 'recent' => (clone $reports)->with('equipment', 'operator')->orderByDesc('report_date')->orderByDesc('id')->limit(8)->get(), 'exceptions' => DailyReportFuel::whereIn('daily_report_id', $ids)->where('abnormal', true)->orderByDesc('ratio')->limit(10)->get(), 'reconciliation' => $reconciliationQuery->selectRaw('status,count(*) as total')->groupBy('status')->pluck('total', 'status')] + $extras;
    }
}
