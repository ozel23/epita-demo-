<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class SparePart extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'spare_parts';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
