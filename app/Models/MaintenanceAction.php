<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class MaintenanceAction extends DomainModel
{
    use HasFactory;
    protected $table = 'maintenance_actions';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
