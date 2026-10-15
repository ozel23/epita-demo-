<?php

namespace App\Services;

use App\Models\ApprovalHistory;
use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\EquipmentLocationHistory;
use App\Models\EquipmentStatusHistory;
use App\Models\MaintenanceWorkOrder;
use App\Models\SapReconciliation;
use App\Models\WorkPlan;
use App\Support\PlantAccess;
use Carbon\Carbon;
class ReportService
{
    const TYPES = ['daily' => 'Aktivitas Harian', 'meter' => 'Rekap HM/KM', 'fuel' => 'BBM & Rasio', 'volume' => 'Volume Pekerjaan', 'plan' => 'Rencana vs Realisasi', 'utilization' => 'Utilisasi Alat', 'downtime' => 'Downtime', 'maintenance' => 'Maintenance & Spare Part', 'equipment-history' => 'Riwayat Alat', 'personnel-history' => 'Riwayat Personel', 'reconciliation' => 'Rekonsiliasi IW47', 'exceptions' => 'Exception & Approval', 'iw47' => 'Output IW47'];
    public function rows(string $type, $u, array $filters)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $q = PlantAccess::query(DailyReport::with('equipment', 'operator', 'activities.activity', 'activities.unit', 'fuels', 'helpers', 'assignment'), $u);
        if ($type !== 'exceptions') {
            $q->where('status', 'APPROVED');
        }
        foreach (['from', 'to'] as $k) {
            if (!empty($filters[$k])) {
                $q->whereDate('report_date', $k === 'from' ? '>=' : '<=', $filters[$k]);
            }
        }
        foreach (['plant_id', 'division_id', 'equipment_id', 'main_operator_id', 'company_id'] as $k) {
            if (!empty($filters[$k])) {
                $q->where($k, $filters[$k]);
            }
        }
        foreach (['equipment_type_id', 'ownership'] as $k) {
            if (!empty($filters[$k])) {
                $q->whereHas('equipment', function ($e) use ($k, $filters) {
                    $e->where($k, $filters[$k]);
                });
            }
        }
        $equipmentQuery = PlantAccess::query(Equipment::withTrashed(), $u);
        foreach (['plant_id', 'equipment_type_id', 'ownership'] as $key) {
            if (!empty($filters[$key])) {
                $equipmentQuery->where($key, $filters[$key]);
            }
        }
        if (!empty($filters['equipment_id'])) {
            $equipmentQuery->whereKey($filters['equipment_id']);
        }
        if (!empty($filters['company_id'])) {
            $equipmentQuery->whereHas('plant', function ($p) use ($filters) {
                $p->where('company_id', $filters['company_id']);
            });
        }
        $equipmentIds = $equipmentQuery->pluck('id');
        $equipmentCodes = (clone $equipmentQuery)->pluck('code');
        $q->whereIn('equipment_id', $equipmentIds);
        $reports = $q->orderBy('report_date')->get();
        $ids = $reports->pluck('id');
        $rows = [];
        if ($type === 'reconciliation' || $type === 'iw47') {
            $rq = PlantAccess::query(SapReconciliation::with('row', 'report'), $u);
            $rq->where(function ($rq) use ($equipmentIds, $equipmentCodes) {
                $rq->whereHas('report', function ($q) use ($equipmentIds) {
                    $q->whereIn('equipment_id', $equipmentIds);
                })->orWhereHas('row', function ($q) use ($equipmentCodes) {
                    $q->whereIn('equipment_code', $equipmentCodes);
                });
            });
            if (!empty($filters['status'])) {
                $rq->where('status', $filters['status']);
            }
            if (!empty($filters['plant_id'])) {
                $rq->where('plant_id', $filters['plant_id']);
            }
            foreach (['from', 'to'] as $k) {
                if (!empty($filters[$k])) {
                    $rq->whereHas('row', function ($q) use ($filters, $k) {
                        $q->whereDate('posting_date', $k === 'from' ? '>=' : '<=', $filters[$k]);
                    });
                }
            }
            foreach ($rq->get() as $r) {
                if ($type === 'iw47') {
                    if ($r->row) {
                        $rows[] = array_map(function ($col) use ($r) {
                            return $r->row->normalized_data[$col] ?? null;
                        }, SapService::COLUMNS);
                    }
                } else {
                    $rows[] = ['Batch' => $r->sap_import_batch_id, 'Laporan' => $r->report->report_number ?? '—', 'Alat' => $r->row->equipment_code ?? '—', 'Status' => $r->status, 'Lokal' => $r->local_value, 'SAP' => $r->sap_value, 'Selisih' => $r->variance, 'Catatan' => $r->reason, 'Reviewer' => $r->reviewed_by, 'Waktu Review' => $r->reviewed_at];
                }
            }
            return $rows;
        }
        if (in_array($type, ['maintenance', 'downtime'])) {
            $mq = PlantAccess::query(MaintenanceWorkOrder::with('equipment', 'parts', 'actions'), $u)->whereIn('equipment_id', $equipmentIds);
            foreach (['plant_id', 'equipment_id'] as $k) {
                if (!empty($filters[$k])) {
                    $mq->where($k, $filters[$k]);
                }
            }
            if (!empty($filters['from'])) {
                $mq->whereDate('unavailable_at', '>=', $filters['from']);
            }
            if (!empty($filters['to'])) {
                $mq->whereDate('unavailable_at', '<=', $filters['to']);
            }
            foreach ($mq->get() as $m) {
                $rows[] = ['WO' => $m->number, 'Alat' => $m->equipment->code, 'Jenis' => $m->type, 'Kategori' => $m->type === 'PREVENTIVE' ? 'Planned' : 'Unplanned', 'Status' => $m->status, 'Downtime (menit)' => $m->downtime_minutes, 'Diagnosis' => $m->diagnosis, 'Hasil' => $m->result, 'Biaya Part' => $m->parts->sum(function ($p) {
                    return $p->quantity * $p->unit_cost;
                }), 'Tenaga (menit)' => $m->actions->sum('labor_minutes')];
            }
            return $rows;
        }
        if ($type === 'plan') {
            $pq = PlantAccess::query(WorkPlan::with('items'), $u);
            if (!empty($filters['equipment_id']) || !empty($filters['equipment_type_id']) || !empty($filters['ownership'])) {
                $pq->whereHas('assignments', function ($a) use ($equipmentIds) {
                    $a->whereIn('equipment_id', $equipmentIds);
                });
            }
            foreach (['plant_id', 'company_id', 'division_id'] as $key) {
                if (!empty($filters[$key])) {
                    $pq->where($key, $filters[$key]);
                }
            }
            if (!empty($filters['from'])) {
                $pq->whereDate('end_date', '>=', $filters['from']);
            }
            if (!empty($filters['to'])) {
                $pq->whereDate('start_date', '<=', $filters['to']);
            }
            foreach ($pq->get() as $plan) {
                foreach ($plan->items as $item) {
                    $real = $reports->filter(function ($r) use ($plan) {
                        return $r->assignment && $r->assignment->work_plan_id === $plan->id;
                    })->sum(function ($r) use ($item) {
                        return $r->activities->where('activity_code_id', $item->activity_code_id)->where('work_unit_id', $item->work_unit_id)->sum('volume');
                    });
                    $rows[] = ['Rencana' => $plan->number, 'Aktivitas' => $item->activity_code_id, 'Satuan' => $item->work_unit_id, 'Target' => $item->target_volume, 'Realisasi' => $real, 'Selisih' => $real - $item->target_volume];
                }
            }
            return $rows;
        }
        if ($type === 'equipment-history') {
            foreach ([EquipmentStatusHistory::class, EquipmentLocationHistory::class] as $class) {
                $hq = $class::whereIn('equipment_id', $equipmentIds)->whereHas('equipment', function ($q) use ($u, $filters) {
                    PlantAccess::query($q, $u);
                    if (!empty($filters['equipment_id'])) {
                        $q->whereKey($filters['equipment_id']);
                    }
                });
                if (!empty($filters['from'])) {
                    $hq->whereDate('created_at', '>=', $filters['from']);
                }
                if (!empty($filters['to'])) {
                    $hq->whereDate('created_at', '<=', $filters['to']);
                }
                foreach ($hq->get() as $h) {
                    $rows[] = ['Alat' => $h->equipment_id, 'Waktu' => $h->created_at->format('Y-m-d H:i'), 'Dari' => $h->from_condition ?? '—', 'Ke / Lokasi' => $h->to_condition ?? $h->location_id, 'Alasan' => $h->reason, 'Pelaku' => $h->actor_id];
                }
            }
            return $rows;
        }
        if ($type === 'exceptions') {
            foreach (ApprovalHistory::whereIn('daily_report_id', $ids)->get() as $a) {
                $rows[] = ['Laporan' => $a->daily_report_id, 'Dari' => $a->from_status, 'Ke' => $a->to_status, 'Revisi' => $a->revision_no, 'Pelaku' => $a->actor_id, 'Catatan' => $a->note, 'Waktu' => $a->created_at->format('Y-m-d H:i')];
            }
            return $rows;
        }
        if (in_array($type, ['meter', 'utilization'])) {
            foreach ($reports->groupBy('equipment_id') as $group) {
                $first = $group->first();
                $hours = round($group->sum('work_minutes') / 60, 2);
                $days = !empty($filters['from']) && !empty($filters['to']) ? Carbon::parse($filters['from'])->diffInDays(Carbon::parse($filters['to'])) + 1 : $group->pluck('report_date')->unique()->count();
                $rows[] = ['Alat' => $first->equipment->code, 'Satuan' => $first->meter_type, 'Total Meter' => $group->sum('meter_total'), 'Jam Kerja' => $hours, 'Jam Tersedia' => $days * 24, 'Utilisasi (%)' => $days ? round($hours / ($days * 24) * 100, 2) : 0];
            }
            return $rows;
        }
        foreach ($reports as $r) {
            $base = ['Nomor' => $r->report_number, 'Tanggal' => $r->report_date->format('Y-m-d'), 'Alat' => $r->equipment->code, 'Operator' => $r->operator->name];
            if ($type === 'fuel') {
                foreach ($r->fuels as $f) {
                    $rows[] = $base + ['BBM' => $f->fuel_type_id, 'Diisi' => $f->quantity_filled, 'Dipakai' => $f->quantity_used, 'Sisa' => $f->quantity_remaining, 'Rasio' => $f->ratio, 'Abnormal' => $f->abnormal ? 'Ya' : 'Tidak'];
                }
            } elseif ($type === 'volume') {
                foreach ($r->activities as $a) {
                    $rows[] = $base + ['Aktivitas' => $a->activity->name, 'Volume' => $a->volume, 'Satuan' => $a->unit->name, 'Produktivitas / jam' => $r->work_minutes ? round($a->volume / ($r->work_minutes / 60), 2) : null];
                }
            } elseif ($type === 'personnel-history') {
                $rows[] = $base + ['Peran' => 'OPERATOR', 'Personel' => $r->main_operator_id, 'Jam Kerja' => round($r->work_minutes / 60, 2)];
                foreach ($r->helpers as $h) {
                    $rows[] = $base + ['Peran' => 'HELPER', 'Personel' => $h->personnel_id, 'Jam Kerja' => round($r->work_minutes / 60, 2)];
                }
            } else {
                $rows[] = $base + ['Meter' => $r->meter_type, 'Awal' => $r->meter_start, 'Akhir' => $r->meter_end, 'Total' => $r->meter_total, 'Jam Kerja' => round($r->work_minutes / 60, 2), 'BBM' => $r->fuels->sum('quantity_used'), 'Kondisi' => $r->equipment_condition, 'Disetujui' => $r->approved_by, 'Waktu Approval' => $r->approved_at ? $r->approved_at->format('Y-m-d H:i') : null];
            }
        }
        return $rows;
    }
    /** Only sum compatible measures; kilometer and hour totals remain separate. */
    public function summaries(array $rows, string $type): array
    {
        $measures = ['daily' => ['Total', 'Jam Kerja', 'BBM'], 'meter' => ['Total Meter', 'Jam Kerja'], 'fuel' => ['Diisi', 'Dipakai', 'Sisa'], 'volume' => ['Volume'], 'plan' => ['Target', 'Realisasi', 'Selisih'], 'utilization' => ['Jam Kerja', 'Jam Tersedia'], 'downtime' => ['Downtime (menit)', 'Biaya Part', 'Tenaga (menit)'], 'maintenance' => ['Downtime (menit)', 'Biaya Part', 'Tenaga (menit)']][$type] ?? [];
        if (!$measures || !$rows) {
            return [];
        }
        $unitKey = $type === 'daily' ? 'Meter' : (in_array($type, ['meter', 'volume', 'plan', 'utilization']) ? 'Satuan' : null);
        $groups = collect($rows)->groupBy(function ($row) use ($unitKey) {
            return $unitKey ? $row[$unitKey] ?? '—' : 'Semua';
        });
        $totals = [];
        foreach ($groups as $unit => $group) {
            $total = ['label' => 'Subtotal ' . $unit];
            foreach ($measures as $field) {
                $total[$field] = round($group->sum($field), 2);
            }
            $totals[] = $total;
        }
        if ($groups->count() > 1) {
            $grand = ['label' => 'Grand total (satuan gabungan tidak dijumlahkan)'];
            foreach (array_diff($measures, ['Total', 'Total Meter', 'Volume', 'Target', 'Realisasi', 'Selisih']) as $field) {
                $grand[$field] = round(collect($rows)->sum($field), 2);
            }
            $totals[] = $grand;
        }
        return $totals;
    }
}
