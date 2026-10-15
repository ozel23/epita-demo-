<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\ActivityCode;
use App\Models\ApprovalHistory;
use App\Models\Assignment;
use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\EquipmentLocationHistory;
use App\Models\FuelingLocation;
use App\Models\FuelRatioStandard;
use App\Models\FuelType;
use App\Models\LoadType;
use App\Models\Location;
use App\Models\NonMovingReason;
use App\Models\OvertimeRule;
use App\Models\Personnel;
use App\Models\WorkUnit;
use App\Support\PlantAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class DailyReportService
{
    private function fail($field, $message)
    {
        throw ValidationException::withMessages([$field => $message]);
    }
    public function save(array $data, $user, ?DailyReport $report = null): DailyReport
    {
        return DB::transaction(function () use ($data, $user, $report) {
            PlantAccess::check($user, $data['plant_id']);
            $equipment = Equipment::where('plant_id', $data['plant_id'])->where('active', true)->lockForUpdate()->findOrFail($data['equipment_id']);
            if ($report) {
                $report = DailyReport::lockForUpdate()->findOrFail($report->id);
                abort_unless($user->can('update', $report), 403);
            }
            $operator = Personnel::where('plant_id', $equipment->plant_id)->where('active', true)->whereIn('type', ['OPERATOR', 'DRIVER'])->findOrFail($data['main_operator_id']);
            if (!$user->can('daily-report.view-all') && $operator->user_id !== $user->id) {
                abort(403);
            }
            $location = Location::where('plant_id', $equipment->plant_id)->where('active', true)->findOrFail($data['location_id']);
            $start = Carbon::parse($data['work_start_at'])->setTimezone(config('app.timezone'));
            $end = Carbon::parse($data['work_end_at'])->setTimezone(config('app.timezone'));
            if ($end->lte($start) || $start->diffInMinutes($end) > 1440) {
                $this->fail('work_end_at', 'Durasi harus lebih dari nol dan maksimal 24 jam; isi tanggal berikutnya untuk lintas tengah malam.');
            }
            if ($start->format('Y-m-d') !== $data['report_date']) {
                $this->fail('report_date', 'Tanggal laporan harus sama dengan tanggal mulai.');
            }
            $total = app(ReportCalculator::class)->meter($data['meter_start'], $data['meter_end']);
            if ($total < 0) {
                $this->fail('meter_end', 'Meter akhir tidak boleh lebih kecil dari meter awal.');
            }
            $latest = $equipment->reports()->where('status', 'APPROVED')->orderByDesc('work_end_at')->first();
            if ($latest && $start->lt($latest->work_end_at)) {
                $this->fail('work_start_at', 'Tidak dapat memasukkan laporan sebelum meter terakhir yang disetujui.');
            }
            $expected = $latest ? $latest->meter_end : $equipment->last_meter;
            if (abs((float) $data['meter_start'] - (float) $expected) > 0.008999999999999999 && (!$user->can('daily-report.override-meter') || empty($data['meter_override_reason']))) {
                $this->fail('meter_start', 'Meter awal harus ' . $expected . '. Override memerlukan izin dan alasan.');
            }
            $conflict = DailyReport::where('equipment_id', $equipment->id)->where('status', '!=', 'CANCELLED')->where('work_start_at', '<', $end)->where('work_end_at', '>', $start);
            if ($report) {
                $conflict->where('id', '!=', $report->id);
            }
            if ($conflict->exists()) {
                $this->fail('equipment_id', 'Interval laporan alat bertumpang tindih.');
            }
            if (!empty($data['assignment_id'])) {
                $a = Assignment::where('plant_id', $equipment->plant_id)->findOrFail($data['assignment_id']);
                if ($a->equipment_id != $equipment->id || $a->operator_id != $operator->id || in_array($a->status, ['CANCELLED', 'COMPLETED']) || $start->lt($a->planned_start_at) || $end->gt($a->planned_end_at)) {
                    $this->fail('assignment_id', 'Penugasan tidak cocok dengan alat, operator, waktu, atau status.');
                }
            }
            if ($total == 0 && empty($data['non_moving_reason_id'])) {
                $this->fail('non_moving_reason_id', 'Alasan tidak bergerak wajib diisi.');
            }
            if (!empty($data['non_moving_reason_id'])) {
                NonMovingReason::where('active', true)->findOrFail($data['non_moving_reason_id']);
            }
            $activities = [];
            foreach ($data['activities'] as $item) {
                $activity = ActivityCode::where('active', true)->findOrFail($item['activity_code_id']);
                WorkUnit::where('active', true)->findOrFail($item['work_unit_id']);
                if (!empty($item['load_type_id'])) {
                    LoadType::where('active', true)->findOrFail($item['load_type_id']);
                }
                if ($activity->productive && $item['volume'] <= 0) {
                    $this->fail('activities', 'Volume aktivitas produktif harus lebih dari nol.');
                }
                $activities[] = $item;
            }
            $maxHelpers = OvertimeRule::where('plant_id', $equipment->plant_id)->value('max_helpers') ?? 4;
            if (count($data['helpers'] ?? []) > $maxHelpers) {
                $this->fail('helpers', 'Jumlah helper melebihi aturan plant (' . $maxHelpers . ').');
            }
            foreach ($data['helpers'] ?? [] as $id) {
                $helper = Personnel::where('plant_id', $equipment->plant_id)->where('active', true)->where('type', 'HELPER')->findOrFail($id);
            }
            $before = $report ? $report->toArray() : [];
            $fields = collect($data)->only(['plant_id', 'equipment_id', 'main_operator_id', 'location_id', 'assignment_id', 'report_date', 'shift', 'meter_start', 'meter_end', 'work_start_at', 'work_end_at', 'equipment_condition', 'non_moving_reason_id', 'description', 'meter_override_reason'])->all();
            $fields['work_start_at']=$start;
            $fields['work_end_at']=$end;
            $fields += ['company_id' => $equipment->plant->company_id, 'division_id' => $location->division_id, 'meter_type' => $equipment->meter_type, 'meter_total' => $total, 'work_minutes' => app(ReportCalculator::class)->minutes($start, $end), 'updated_by' => $user->id];
            if ($report) {
                $report->update($fields);
            } else {
                $report = DailyReport::create($fields + ['report_number' => 'OP-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))), 'created_by' => $user->id]);
            }
            $report->activities()->delete();
            $report->activities()->createMany($activities);
            $report->helpers()->delete();
            foreach ($data['helpers'] ?? [] as $id) {
                $report->helpers()->create(['personnel_id' => $id]);
            }
            $report->fuels()->delete();
            $standard = FuelRatioStandard::where('equipment_type_id', $equipment->equipment_type_id)->whereDate('effective_date', '<=', $report->report_date)->latest('effective_date')->first();
            foreach ($data['fuels'] ?? [] as $fuel) {
                FuelType::where('active', true)->findOrFail($fuel['fuel_type_id']);
                if (!empty($fuel['fueling_location_id'])) {
                    FuelingLocation::where('plant_id', $equipment->plant_id)->where('active', true)->findOrFail($fuel['fueling_location_id']);
                }
                $denominator = $equipment->meter_type === 'KM' ? $total : $fields['work_minutes'] / 60;
                $ratio = app(ReportCalculator::class)->fuelRatio($fuel['quantity_used'], $denominator);
                $report->fuels()->create($fuel + ['ratio' => $ratio, 'abnormal' => $standard && $ratio !== null && ($ratio < $standard->lower_limit || $ratio > $standard->upper_limit)]);
            }
            AuditService::record($before ? 'daily-report.updated' : 'daily-report.created', $report, $before, $report->toArray());
            if (abs((float) $data['meter_start'] - (float) $expected) > 0.008999999999999999) {
                AuditService::record('meter.override', $report, ['expected' => $expected], ['meter_start' => $data['meter_start'], 'reason' => $data['meter_override_reason']]);
            }
            return $report->fresh(['activities', 'fuels', 'helpers']);
        });
    }
    public function transition(DailyReport $report, string $action, $user, ?string $note = null): DailyReport
    {
        return DB::transaction(function () use ($report, $action, $user, $note) {
            // Lock equipment before report consistently with save to serialize meter approvals.
            Equipment::whereKey($report->equipment_id)->lockForUpdate()->firstOrFail();
            $r = DailyReport::lockForUpdate()->findOrFail($report->id);
            $from = $r->status;
            $actions = DocumentStatus::REPORT_TRANSITIONS;
            if (!isset($actions[$action]) || !in_array($from, $actions[$action])) {
                $this->fail('status', 'Status telah berubah atau transisi tidak diizinkan.');
            }
            if ($action === 'return') {
                abort_unless($user->can('daily-report.verify') && $user->can('view', $r) && $r->created_by !== $user->id, 403);
            } else {
                abort_unless($user->can($action, $r), 403);
            }
            if (in_array($action, ['return', 'revise']) && !trim($note ?? '')) {
                $this->fail('note', 'Catatan wajib diisi.');
            }
            if ($action === 'submit') {
                $needsPhoto = $r->equipment_condition === 'BREAKDOWN' || $r->activities()->whereHas('activity', function ($q) {
                    $q->where('requires_photo', true);
                })->exists();
                if ($needsPhoto && !$r->attachments()->exists()) {
                    $this->fail('attachments', 'Foto bukti wajib diunggah sebelum mengirim.');
                }
                $r->submitted_at = now();
            }
            if ($action === 'approve') {
                $e = $r->equipment;
                $last = $e->reports()->where('status', 'APPROVED')->latest('work_end_at')->first();
                if ($last && $r->work_start_at->lt($last->work_end_at)) {
                    $this->fail('meter_start', 'Laporan lebih lama dari persetujuan terakhir.');
                }
                if (abs((float) $r->meter_start - (float) $e->last_meter) > 0.008999999999999999 && !$r->meter_override_reason) {
                    $this->fail('meter_start', 'Meter berubah sejak laporan dibuat. Kembalikan untuk koreksi.');
                }
                $e->update(['last_meter' => $r->meter_end]);
                if (in_array($e->current_condition, ['WORKING', 'STANDBY']) && in_array($r->equipment_condition, ['WORKING', 'STANDBY']) && $e->current_condition !== $r->equipment_condition) {
                    app(MaintenanceService::class)->condition($e, $r->equipment_condition, $user, 'Laporan approved ' . $r->report_number);
                }
                $r->approved_at = now();
                $r->approved_by = $user->id;
                EquipmentLocationHistory::create(['equipment_id' => $e->id, 'location_id' => $r->location_id, 'actor_id' => $user->id, 'reason' => 'Persetujuan ' . $r->report_number]);
                if ($r->assignment) {
                    $r->assignment->update(['status' => 'COMPLETED']);
                }
            }
            if ($action === 'verify') {
                $r->verified_at = now();
                $r->verified_by = $user->id;
            }
            if ($action === 'revise') {
                if (DailyReport::where('equipment_id', $r->equipment_id)->where('status', 'APPROVED')->where('work_end_at', '>', $r->work_end_at)->exists()) {
                    $this->fail('status', 'Revisi harus dimulai dari laporan approved terakhir agar rantai meter tetap valid.');
                }
                $previous = DailyReport::where('equipment_id', $r->equipment_id)->where('status', 'APPROVED')->where('id', '!=', $r->id)->latest('work_end_at')->first();
                $r->equipment->update(['last_meter' => $previous ? $previous->meter_end : $r->meter_start]);
                $r->revision_no++;
                $r->approved_at = null;
                $r->approved_by = null;
                $r->verified_at = null;
                $r->verified_by = null;
            }
            $r->status = ['submit' => 'SUBMITTED', 'return' => 'RETURNED', 'verify' => 'VERIFIED', 'approve' => 'APPROVED', 'revise' => 'RETURNED'][$action];
            $r->save();
            ApprovalHistory::create(['daily_report_id' => $r->id, 'actor_id' => $user->id, 'from_status' => $from, 'to_status' => $r->status, 'revision_no' => $r->revision_no, 'note' => $note, 'snapshot' => $r->load(['activities', 'fuels', 'helpers', 'attachments'])->toArray(), 'ip' => request()->ip(), 'user_agent' => substr(request()->userAgent() ?? '', 0, 2000)]);
            AuditService::record('daily-report.' . $action, $r, ['status' => $from], ['status' => $r->status, 'note' => $note]);
            app(NotificationService::class)->report($r);
            return $r;
        });
    }
}
