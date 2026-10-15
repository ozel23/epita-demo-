<?php

namespace Database\Factories;

use App\Models\Personnel;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;
class PersonnelFactory extends Factory
{
    protected $model = Personnel::class;
    public function definition()
    {
        return ['plant_id' => Plant::factory(), 'personnel_number' => $this->faker->unique()->numerify('########'), 'name' => $this->faker->name, 'type' => 'OPERATOR', 'active' => true];
    }
}
