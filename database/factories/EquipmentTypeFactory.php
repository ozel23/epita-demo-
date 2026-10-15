<?php

namespace Database\Factories;

use App\Models\EquipmentType;
use Illuminate\Database\Eloquent\Factories\Factory;
class EquipmentTypeFactory extends Factory
{
    protected $model = EquipmentType::class;
    public function definition()
    {
        return ['code' => strtoupper($this->faker->unique()->bothify('REF-????##')), 'name' => $this->faker->words(2, true), 'active' => true, 'prefix' => 'TRK', 'meter_type' => 'KM'];
    }
}
