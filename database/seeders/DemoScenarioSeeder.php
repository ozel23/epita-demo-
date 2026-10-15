<?php

namespace Database\Seeders;

use App\Models\ActivityCode;
use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkPlan;
use App\Models\WorkUnit;
use App\Services\AssignmentService;
use App\Services\DailyReportService;
use App\Services\MaintenanceService;
use App\Services\SapService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
class DemoScenarioSeeder extends Seeder
{
    public function run()
    {
        if (!app()->environment('local', 'testing')) {
            throw new \RuntimeException('Demo scenarios only run in local/testing.');
        }
        if (DailyReport::exists()) {
            $this->command->info('Scenario skipped: operational data already exists.');
            return;
        }
        $op = User::where('username', 'operator')->firstOrFail();
        $super = User::where('username', 'supervisor')->firstOrFail();
        $admin = User::where('username', 'admin')->firstOrFail();
        $tech = User::where('username', 'maintenance')->firstOrFail();
        $plant = $op->plants()->where('code', 'BS01')->firstOrFail();
        $operator = $op->personnel;
        $location = Location::where('plant_id', $plant->id)->first();
        $activity = ActivityCode::where('code', '237')->first();
        $unit = WorkUnit::where('code', 'KG')->first();
        $s = app(DailyReportService::class);
        $reports = [];
        $plan = WorkPlan::create(['number' => 'WP-DEMO-001', 'plant_id' => $plant->id, 'company_id' => $plant->company_id, 'location_id' => $location->id, 'division_id' => $location->division_id, 'created_by' => $super->id, 'start_date' => today()->subDays(7), 'end_date' => today(), 'status' => 'ASSIGNED', 'notes' => 'Distribusi pupuk dan perawatan blok. Data demonstrasi.']);
        $plan->items()->create(['activity_code_id' => $activity->id, 'work_unit_id' => $unit->id, 'target_volume' => 20000, 'equipment_requirement' => 2]);
        foreach (Equipment::where('plant_id', $plant->id)->whereIn('meter_type', ['KM', 'HM'])->limit(4)->get() as $index => $e) {
            for ($day = 7; $day >= 1; $day--) {
                $date = today()->subDays($day)->format('Y-m-d');
                $start = $date . ' 07:00:00';
                $end = $date . ' 15:00:00';
                Auth::login($super);
                $assignment = app(AssignmentService::class)->create(['plant_id' => $plant->id, 'work_plan_id' => $plan->id, 'equipment_id' => $e->id, 'operator_id' => $operator->id, 'planned_start_at' => $start, 'planned_end_at' => $end, 'shift' => '1', 'override_reason' => 'Skenario demo paralel operator.'], $super);
                Auth::login($op);
                $meter = (float) $e->fresh()->last_meter;
                $delta = $e->meter_type === 'KM' ? 30 + $day * 3 + $index * 5 : 8;
                $r = $s->save(['plant_id' => $plant->id, 'equipment_id' => $e->id, 'main_operator_id' => $operator->id, 'location_id' => $location->id, 'assignment_id' => $assignment->id, 'report_date' => $date, 'shift' => '1', 'meter_start' => $meter, 'meter_end' => $meter + $delta, 'work_start_at' => $start, 'work_end_at' => $end, 'equipment_condition' => 'WORKING', 'description' => 'Distribusi pupuk blok ' . $location->code . ' — data demo.', 'activities' => [['activity_code_id' => $activity->id, 'work_unit_id' => $unit->id, 'volume' => 600 + $day * 50]], 'fuels' => [['fuel_type_id' => 1, 'fueling_location_id' => 1, 'quantity_filled' => 40, 'quantity_used' => $e->meter_type === 'KM' ? $day === 2 ? 40 : 12 + $day : 48, 'quantity_remaining' => 5]]], $op);
                $s->transition($r, 'submit', $op);
                Auth::login($super);
                $s->transition($r, 'verify', $super);
                $reports[] = $s->transition($r, 'approve', $super);
            }
        }
        Auth::login($op);
        $e = Equipment::where('plant_id', $plant->id)->where('meter_type', 'KM')->first();
        $today = today()->format('Y-m-d');
        $r = $s->save(['plant_id' => $plant->id, 'equipment_id' => $e->id, 'main_operator_id' => $operator->id, 'location_id' => $location->id, 'report_date' => $today, 'shift' => '1', 'meter_start' => $e->last_meter, 'meter_end' => $e->last_meter + 42, 'work_start_at' => $today . ' 07:00', 'work_end_at' => $today . ' 15:00', 'equipment_condition' => 'WORKING', 'description' => 'Laporan menunggu verifikasi — demo.', 'activities' => [['activity_code_id' => $activity->id, 'work_unit_id' => $unit->id, 'volume' => 750]]], $op);
        $s->transition($r, 'submit', $op);
        $machine = Equipment::where('plant_id', $plant->id)->where('meter_type', 'HOUR_KWH')->first();
        Auth::login($tech);
        $m = app(MaintenanceService::class);
        $b = $m->breakdown(['plant_id' => $plant->id, 'equipment_id' => $machine->id, 'location_id' => $location->id, 'occurred_at' => now()->subHours(6), 'meter' => $machine->last_meter, 'symptom' => 'Temperatur mesin tinggi', 'severity' => 'HIGH', 'description' => 'Pemeriksaan sistem pendingin — data demo.'], $tech);
        $wo = $m->create(['plant_id' => $plant->id, 'equipment_id' => $machine->id, 'breakdown_report_id' => $b->id, 'type' => 'CORRECTIVE', 'priority' => 'HIGH', 'technicians' => [$tech->personnel->id]], $tech);
        $m->transition($wo, 'start', [], $tech);
        $m->action($wo, ['technician_id' => $tech->personnel->id, 'description' => 'Pemeriksaan radiator dan filter.', 'labor_minutes' => 90, 'parts' => [['spare_part_id' => 1, 'quantity' => 1, 'unit_cost' => 150000]]], $tech);
        Auth::login($admin);
        $rows = [];
        foreach (array_slice($reports, 0, 7) as $i => $r) {
            $rows[] = ['equipment_code' => $r->equipment->code, 'order_number' => 'BS01MTB00079', 'operation_activity' => '0010', 'system_status' => 'PCNF REL', 'confirmation_number' => (string) (162280 + $i), 'confirmation_counter' => '1', 'work_center' => 'GA-015', 'plant_code' => 'BS01', 'personnel_number' => $operator->personnel_number, 'actual_work' => $r->meter_total + ($i === 2 ? 5 : 0), 'actual_work_unit' => 'KM', 'posting_date' => $r->report_date->format('d.m.Y'), 'reason_code' => '237', 'confirmation_text' => 'Data dummy IW47', 'work_start_at' => $r->work_start_at->format('Y-m-d H:i:s'), 'work_finish_at' => $r->work_end_at->format('Y-m-d H:i:s')];
        }
        $sap = app(SapService::class);
        $batch = $sap->stage($rows, $plant->id, 'demo-iw47.csv', $admin);
        $sap->commit($batch, $admin);
        $sap->reconcile($batch, $admin);
        Auth::logout();
        $this->command->info('Demo operational scenarios created.');
    }
}
