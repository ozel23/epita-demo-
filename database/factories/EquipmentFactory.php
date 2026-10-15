<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;
    public function definition()
    {
        return ['plant_id' => Plant::factory(), 'equipment_type_id' => EquipmentType::factory(), 'code' => $this->faker->unique()->numerify('TRK880000000####'), 'name' => 'Truk Operasional', 'meter_type' => 'KM', 'ownership' => 'OWN', 'current_condition' => 'STANDBY', 'last_meter' => 1000, 'active' => true];
    }
}
