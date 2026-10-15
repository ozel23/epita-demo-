<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Division extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'divisions';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
