<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Equipment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;
    public function definition()
    {
        return ['equipment_id' => Equipment::factory(), 'plant_id' => function (array $a) {
            return Equipment::findOrFail($a['equipment_id'])->plant_id;
        }, 'operator_id' => function (array $a) {
            return Personnel::factory()->create(['plant_id' => $a['plant_id']])->id;
        }, 'created_by' => User::factory(), 'planned_start_at' => '2026-10-14 07:00:00', 'planned_end_at' => '2026-10-14 16:00:00', 'shift' => '1', 'status' => 'ASSIGNED'];
    }
}
