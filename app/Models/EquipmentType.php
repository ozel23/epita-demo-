<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class EquipmentType extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'equipment_types';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
