<?php

namespace Database\Factories;

use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\Location;
use App\Models\Personnel;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
class DailyReportFactory extends Factory
{
    protected $model = DailyReport::class;
    public function definition()
    {
        return ['equipment_id' => Equipment::factory(), 'plant_id' => function (array $a) {
            return Equipment::findOrFail($a['equipment_id'])->plant_id;
        }, 'company_id' => function (array $a) {
            return Plant::findOrFail($a['plant_id'])->company_id;
        }, 'main_operator_id' => function (array $a) {
            return Personnel::factory()->create(['plant_id' => $a['plant_id']])->id;
        }, 'location_id' => function (array $a) {
            return Location::factory()->create(['plant_id' => $a['plant_id']])->id;
        }, 'report_number' => $this->faker->unique()->bothify('OP-FACT-########'), 'report_date' => '2026-10-14', 'meter_type' => 'KM', 'meter_start' => 1000, 'meter_end' => 1039, 'meter_total' => 39, 'work_start_at' => '2026-10-14 07:00:00', 'work_end_at' => '2026-10-14 16:00:00', 'work_minutes' => 540, 'equipment_condition' => 'WORKING', 'status' => 'DRAFT', 'created_by' => User::factory(), 'updated_by' => function (array $a) {
            return $a['created_by'];
        }];
    }
}
