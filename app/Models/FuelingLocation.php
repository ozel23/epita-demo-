<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class FuelingLocation extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'fueling_locations';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
