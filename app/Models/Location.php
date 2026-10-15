<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Location extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'locations';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
