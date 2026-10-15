<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class MaintenanceTechnician extends DomainModel
{
    use HasFactory;
    protected $table = 'maintenance_technicians';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
