<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class NonMovingReason extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'non_moving_reasons';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
