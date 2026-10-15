<?php

namespace Database\Factories;

use App\Models\ActivityCode;
use Illuminate\Database\Eloquent\Factories\Factory;
class ActivityCodeFactory extends Factory
{
    protected $model = ActivityCode::class;
    public function definition()
    {
        return ['code' => strtoupper($this->faker->unique()->bothify('REF-????##')), 'name' => $this->faker->words(2, true), 'active' => true, 'productive' => true, 'requires_photo' => false];
    }
}
