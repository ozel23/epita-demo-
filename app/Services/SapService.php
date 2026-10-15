<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\Plant;
use App\Models\SapImportBatch;
use App\Models\SapIw47StagingRow;
use App\Models\SapMappingRule;
use App\Models\SapReconciliation;
use App\Support\PlantAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class SapService
{
    const COLUMNS = ['equipment_code', 'order_number', 'operation_activity', 'system_status', 'confirmation_number', 'confirmation_counter', 'work_center', 'plant_code', 'personnel_number', 'actual_work', 'actual_work_unit', 'activity_type', 'posting_date', 'accounting_indicator', 'remaining_work', 'actual_duration', 'forecast_end_time', 'work_start_at', 'work_finish_at', 'reason_code', 'reason_description', 'confirmation_text', 'final_confirmation', 'no_remaining_work', 'clear_open_reservation', 'cumulative_actual_work', 'forecast_work', 'cumulative_actual_duration', 'planned_duration', 'actual_start_at', 'actual_end_at'];
    public function stage(array $rows, int $plantId, string $filename, $user, array $mapping = [])
    {
        abort_unless($user->can('sap.import'), 403);
        PlantAccess::check($user, $plantId);
        if (count($rows) > 10000) {
            throw ValidationException::withMessages(['file' => 'Maksimal 10.000 baris per batch.']);
        }
        return DB::transaction(function () use ($rows, $plantId, $filename, $user, $mapping) {
            Plant::whereKey($plantId)->lockForUpdate()->firstOrFail();
            $batch = SapImportBatch::create(['plant_id' => $plantId, 'created_by' => $user->id, 'filename' => $filename, 'mapping' => $mapping]);
            $seen = [];
            $errorCount = 0;
            foreach ($rows as $i => $raw) {
                $n = [];
                foreach (self::COLUMNS as $col) {
                    $n[$col] = $raw[$mapping[$col] ?? $col] ?? null;
                }
                $errors = [];
                foreach (['equipment_code', 'plant_code', 'confirmation_number', 'confirmation_counter', 'posting_date', 'actual_work', 'actual_work_unit'] as $required) {
                    if ($n[$required] === null || $n[$required] === '') {
                        $errors[] = $required . ' wajib diisi';
                    }
                }
                $plant = Plant::findOrFail($plantId);
                if ($n['plant_code'] !== $plant->code) {
                    $errors[] = 'Plant file tidak sesuai';
                }
                if (!Equipment::where('plant_id', $plantId)->where('code', $n['equipment_code'])->exists()) {
                    $errors[] = 'Equipment belum terpetakan';
                }
                try {
                    $date = (string) $n['posting_date'];
                    $format = preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date) ? 'd.m.Y' : 'Y-m-d';
                    $parsed = Carbon::createFromFormat('!' . $format, $date);
                    if (!$parsed || $parsed->format($format) !== $date) {
                        throw new \RuntimeException();
                    }
                    $n['posting_date'] = $parsed->format('Y-m-d');
                } catch (\Throwable $e) {
                    $errors[] = 'Tanggal SAP tidak valid';
                    $n['posting_date'] = null;
                }
                if (!is_numeric($n['actual_work']) || (float) $n['actual_work'] < 0) {
                    $errors[] = 'Actual work tidak valid';
                    $n['actual_work'] = null;
                }
                $key = $n['confirmation_number'] . '|' . $n['confirmation_counter'];
                $duplicate = isset($seen[$key]) || SapIw47StagingRow::where('plant_id', $plantId)->where('confirmation_number', $n['confirmation_number'])->where('confirmation_counter', $n['confirmation_counter'])->whereHas('batch', function ($q) {
                    $q->where('status', '!=', 'ROLLED_BACK');
                })->exists();
                $seen[$key] = true;
                if ($duplicate) {
                    $errors[] = 'Confirmation duplikat';
                }
                $status = $duplicate ? 'DUPLICATE' : ($errors ? 'ERROR' : 'VALID');
                if ($errors) {
                    $errorCount++;
                }
                $batch->rows()->create(['plant_id' => $plantId, 'row_number' => $i + 2, 'raw_data' => $raw, 'normalized_data' => $n, 'errors' => $errors, 'equipment_code' => $n['equipment_code'], 'confirmation_number' => $n['confirmation_number'], 'confirmation_counter' => $n['confirmation_counter'], 'posting_date' => $n['posting_date'], 'actual_work' => $n['actual_work'], 'actual_work_unit' => $n['actual_work_unit'], 'status' => $status]);
            }
            $batch->update(['row_count' => count($rows), 'error_count' => $errorCount]);
            AuditService::record('sap.staged', $batch, [], ['rows' => count($rows), 'errors' => $errorCount]);
            return $batch;
        });
    }
    public function commit(SapImportBatch $batch, $user)
    {
        abort_unless($user->can('sap.import'), 403);
        PlantAccess::check($user, $batch->plant_id);
        return DB::transaction(function () use ($batch) {
            Plant::whereKey($batch->plant_id)->lockForUpdate()->firstOrFail();
            $batch = SapImportBatch::lockForUpdate()->findOrFail($batch->id);
            if ($batch->status !== 'STAGED' || $batch->error_count > 0) {
                throw ValidationException::withMessages(['batch' => 'Batch harus STAGED tanpa error; perbaiki file dan buat batch baru.']);
            }
            foreach ($batch->rows as $row) {
                $dup = SapIw47StagingRow::where('plant_id', $row->plant_id)->where('confirmation_number', $row->confirmation_number)->where('confirmation_counter', $row->confirmation_counter)->where('sap_import_batch_id', '!=', $batch->id)->whereHas('batch', function ($q) {
                    $q->where('status', 'COMMITTED');
                })->exists();
                if ($dup) {
                    throw ValidationException::withMessages(['batch' => 'Confirmation sudah committed di batch lain.']);
                }
            }
            $batch->update(['status' => 'COMMITTED']);
            AuditService::record('sap.committed', $batch);
            return $batch;
        });
    }
    public function rollback(SapImportBatch $batch, $user)
    {
        abort_unless($user->can('sap.import'), 403);
        PlantAccess::check($user, $batch->plant_id);
        return DB::transaction(function () use ($batch) {
            Plant::whereKey($batch->plant_id)->lockForUpdate()->firstOrFail();
            $batch = SapImportBatch::lockForUpdate()->findOrFail($batch->id);
            abort_unless(in_array($batch->status, ['STAGED', 'COMMITTED']), 422);
            SapReconciliation::where('sap_import_batch_id', $batch->id)->delete();
            $batch->update(['status' => 'ROLLED_BACK']);
            AuditService::record('sap.rolled-back', $batch);
            return $batch;
        });
    }
    public function reconcile(SapImportBatch $batch, $user)
    {
        abort_unless($user->can('sap.reconcile'), 403);
        PlantAccess::check($user, $batch->plant_id);
        return DB::transaction(function () use ($batch) {
            Plant::whereKey($batch->plant_id)->lockForUpdate()->firstOrFail();
            $batch = SapImportBatch::lockForUpdate()->findOrFail($batch->id);
            abort_unless($batch->status === 'COMMITTED', 422);
            SapReconciliation::where('sap_import_batch_id', $batch->id)->delete();
            $matched = [];
            $rows = $batch->rows()->where('status', 'VALID')->get();
            $dates = $rows->pluck('posting_date')->filter()->all();
            foreach ($rows as $row) {
                $n = $row->normalized_data;
                $e = Equipment::where('plant_id', $batch->plant_id)->where('code', $row->equipment_code)->first();
                $status = 'ONLY_SAP';
                $local = null;
                $value = null;
                $reason = null;
                $candidates = $e ? DailyReport::with('activities')->where('equipment_id', $e->id)->where('status', 'APPROVED')->whereDate('report_date', $row->posting_date)->get() : collect();
                if (!$e) {
                    $status = 'MAPPING_MISSING';
                } elseif ($candidates->count() > 1) {
                    $status = 'DUPLICATE';
                    $reason = 'Beberapa laporan lokal pada tanggal yang sama; review interval/operation.';
                } else {
                    $local = $candidates->first();
                    if (!$local) {
                        $local = DailyReport::with('activities')->where('equipment_id', $e->id)->where('status', 'APPROVED')->whereDate('report_date', '>=', Carbon::parse($row->posting_date)->subDay()->format('Y-m-d'))->whereDate('report_date', '<=', Carbon::parse($row->posting_date)->addDay()->format('Y-m-d'))->first();
                        if ($local) {
                            $status = 'DATE_MISMATCH';
                        }
                    }
                    if ($local) {
                        $matched[] = $local->id;
                        $rules = SapMappingRule::where('plant_id', $batch->plant_id)->whereIn('activity_code_id', $local->activities->pluck('activity_code_id'))->get();
                        $rule = $rules->first(function ($rule) use ($n) {
                            return $rule->order_number === $n['order_number'] && $rule->operation_activity === $n['operation_activity'] && $rule->actual_work_unit === $n['actual_work_unit'];
                        });
                        if (!$rule) {
                            $status = 'MAPPING_MISSING';
                            $reason = 'Mapping order, operation, unit, atau aktivitas belum sesuai.';
                        } else {
                            $value = $this->value($local, $rule);
                            if ($status !== 'DATE_MISMATCH') {
                                $status = abs($value - (float) $row->actual_work) <= (float) $rule->tolerance ? 'MATCHED' : 'VALUE_MISMATCH';
                            }
                        }
                    }
                }
                SapReconciliation::create(['plant_id' => $batch->plant_id, 'sap_import_batch_id' => $batch->id, 'sap_iw47_staging_row_id' => $row->id, 'daily_report_id' => $local ? $local->id : null, 'status' => $status, 'local_value' => $value, 'sap_value' => $row->actual_work, 'variance' => $value === null ? null : round($value - (float) $row->actual_work, 2), 'reason' => $reason]);
            }
            if ($dates) {
                $locals = DailyReport::where('plant_id', $batch->plant_id)->where('status', 'APPROVED')->whereDate('report_date', '>=', min($dates))->whereDate('report_date', '<=', max($dates))->whereNotIn('id', $matched)->get();
                foreach ($locals as $r) {
                    SapReconciliation::create(['plant_id' => $batch->plant_id, 'sap_import_batch_id' => $batch->id, 'daily_report_id' => $r->id, 'status' => 'ONLY_LOCAL', 'reason' => 'Tidak ditemukan pada batch/periode SAP.']);
                }
            }
            AuditService::record('sap.reconciled', $batch);
            return SapReconciliation::where('sap_import_batch_id', $batch->id)->get();
        });
    }
    public function value(DailyReport $r, SapMappingRule $rule)
    {
        if ($rule->value_source === 'work_hours') {
            return round($r->work_minutes / 60, 2);
        }
        if ($rule->value_source === 'volume') {
            return (float) $r->activities()->where('activity_code_id', $rule->activity_code_id)->sum('volume');
        }
        return (float) $r->meter_total;
    }
}
