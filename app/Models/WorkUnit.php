<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class WorkUnit extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'work_units';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
