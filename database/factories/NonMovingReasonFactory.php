<?php

namespace Database\Factories;

use App\Models\NonMovingReason;
use Illuminate\Database\Eloquent\Factories\Factory;
class NonMovingReasonFactory extends Factory
{
    protected $model = NonMovingReason::class;
    public function definition()
    {
        return ['code' => strtoupper($this->faker->unique()->bothify('REF-????##')), 'name' => $this->faker->words(2, true), 'active' => true];
    }
}
