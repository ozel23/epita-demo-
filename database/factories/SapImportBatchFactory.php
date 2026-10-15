<?php

namespace Database\Factories;

use App\Models\Plant;
use App\Models\SapImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
class SapImportBatchFactory extends Factory
{
    protected $model = SapImportBatch::class;
    public function definition()
    {
        return ['plant_id' => Plant::factory(), 'created_by' => User::factory(), 'filename' => 'fixture-iw47.csv', 'status' => 'STAGED', 'row_count' => 0, 'error_count' => 0];
    }
}
