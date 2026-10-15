<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Equipment;
use App\Models\OvertimeRule;
use App\Models\Personnel;
use App\Models\WorkPlan;
use App\Support\PlantAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class AssignmentService
{
    public function create(array $data, $user)
    {
        return DB::transaction(function () use ($data, $user) {
            abort_unless($user->can('assignment.manage'), 403);
            PlantAccess::check($user, $data['plant_id']);
            $e = Equipment::where('plant_id', $data['plant_id'])->where('active', true)->lockForUpdate()->findOrFail($data['equipment_id']);
            $p = Personnel::where('plant_id', $data['plant_id'])->where('active', true)->whereIn('type', ['OPERATOR', 'DRIVER'])->lockForUpdate()->findOrFail($data['operator_id']);
            if (!empty($data['work_plan_id'])) {
                WorkPlan::where('plant_id', $data['plant_id'])->findOrFail($data['work_plan_id']);
            }
            $data['planned_start_at']=\Carbon\Carbon::parse($data['planned_start_at'])->setTimezone(config('app.timezone'));
            $data['planned_end_at']=\Carbon\Carbon::parse($data['planned_end_at'])->setTimezone(config('app.timezone'));
            $conflict = Assignment::where('status', '!=', 'CANCELLED')->where('planned_start_at', '<', $data['planned_end_at'])->where('planned_end_at', '>', $data['planned_start_at'])->where(function ($q) use ($e, $p) {
                $q->where('equipment_id', $e->id)->orWhere('operator_id', $p->id);
            })->exists();
            if ($conflict && (!$user->can('assignment.override') || empty($data['override_reason']))) {
                throw ValidationException::withMessages(['equipment_id' => 'Alat/operator memiliki penugasan pada waktu yang sama.']);
            }
            $helpers = $data['helpers'] ?? [];
            unset($data['helpers']);
            if (count($helpers) > (OvertimeRule::where('plant_id', $e->plant_id)->value('max_helpers') ?? 4)) {
                throw ValidationException::withMessages(['helpers' => 'Maksimal 4 helper.']);
            }
            foreach ($helpers as $helper) {
                Personnel::where('plant_id', $e->plant_id)->where('active', true)->where('type', 'HELPER')->findOrFail($helper);
            }
            $a = Assignment::create($data + ['created_by' => $user->id]);
            $a->helpers()->sync($helpers);
            AuditService::record('assignment.created', $a, [], $a->toArray());
            if ($p->user) {
                app(NotificationService::class)->send($p->user, 'Penugasan baru: ' . $e->code, '/assignments');
            }
            return $a;
        });
    }
}
