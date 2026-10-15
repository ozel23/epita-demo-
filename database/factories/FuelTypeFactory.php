<?php

namespace Database\Factories;

use App\Models\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;
class FuelTypeFactory extends Factory
{
    protected $model = FuelType::class;
    public function definition()
    {
        return ['code' => strtoupper($this->faker->unique()->bothify('REF-????##')), 'name' => $this->faker->words(2, true), 'active' => true];
    }
}
