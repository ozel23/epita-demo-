<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class MaintenancePartUsage extends DomainModel
{
    use HasFactory;
    protected $table = 'maintenance_part_usages';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
