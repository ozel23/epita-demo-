<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class FuelRatioStandard extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'fuel_ratio_standards';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
