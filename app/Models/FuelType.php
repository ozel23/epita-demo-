<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class FuelType extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'fuel_types';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
