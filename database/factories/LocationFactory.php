<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;
class LocationFactory extends Factory
{
    protected $model = Location::class;
    public function definition()
    {
        return ['plant_id' => Plant::factory(), 'code' => $this->faker->unique()->bothify('BLK-####'), 'name' => 'Blok ' . $this->faker->word, 'location_type' => 'TM', 'active' => true];
    }
}
