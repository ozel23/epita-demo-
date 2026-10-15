<?php

namespace Database\Factories;

use App\Models\SparePart;
use Illuminate\Database\Eloquent\Factories\Factory;
class SparePartFactory extends Factory
{
    protected $model = SparePart::class;
    public function definition()
    {
        return ['code' => strtoupper($this->faker->unique()->bothify('REF-????##')), 'name' => $this->faker->words(2, true), 'active' => true, 'unit' => 'pcs', 'unit_cost' => 100000];
    }
}
