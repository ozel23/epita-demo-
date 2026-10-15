<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class ActivityCode extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'activity_codes';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
