<?php

namespace Database\Factories;

use App\Models\LoadType;
use Illuminate\Database\Eloquent\Factories\Factory;
class LoadTypeFactory extends Factory
{
    protected $model = LoadType::class;
    public function definition()
    {
        return ['code' => strtoupper($this->faker->unique()->bothify('REF-????##')), 'name' => $this->faker->words(2, true), 'active' => true];
    }
}
