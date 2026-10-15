<?php

namespace Database\Factories;

use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
class WorkUnitFactory extends Factory
{
    protected $model = WorkUnit::class;
    public function definition()
    {
        return ['code' => strtoupper($this->faker->unique()->bothify('REF-????##')), 'name' => $this->faker->words(2, true), 'active' => true];
    }
}
