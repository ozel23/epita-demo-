<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class LoadType extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'load_types';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
