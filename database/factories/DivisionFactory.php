<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;
class DivisionFactory extends Factory
{
    protected $model = Division::class;
    public function definition()
    {
        return ['plant_id' => Plant::factory(), 'code' => $this->faker->unique()->bothify('DIV###'), 'name' => $this->faker->city, 'active' => true];
    }
}
