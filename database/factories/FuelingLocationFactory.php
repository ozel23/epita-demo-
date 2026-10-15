<?php

namespace Database\Factories;

use App\Models\FuelingLocation;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;
class FuelingLocationFactory extends Factory
{
    protected $model = FuelingLocation::class;
    public function definition()
    {
        return ['plant_id' => Plant::factory(), 'code' => $this->faker->unique()->bothify('FUEL-####'), 'name' => 'Tangki Estate', 'active' => true];
    }
}
