<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;
class PlantFactory extends Factory
{
    protected $model = Plant::class;
    public function definition()
    {
        return ['company_id' => Company::factory(), 'code' => strtoupper($this->faker->unique()->bothify('P???')), 'name' => 'Estate ' . $this->faker->city, 'active' => true];
    }
}
