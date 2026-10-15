<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class OvertimeRule extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'overtime_rules';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
