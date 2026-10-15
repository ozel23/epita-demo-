<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class MaintenanceWorkOrder extends DomainModel
{
    use HasFactory;
    protected $table = 'maintenance_work_orders';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
    public function breakdown()
    {
        return $this->belongsTo(BreakdownReport::class, 'breakdown_report_id');
    }
    public function actions()
    {
        return $this->hasMany(MaintenanceAction::class);
    }
    public function parts()
    {
        return $this->hasMany(MaintenancePartUsage::class);
    }
    public function technicians()
    {
        return $this->hasMany(MaintenanceTechnician::class);
    }
    public function attachments()
    {
        return $this->hasMany(MaintenanceAttachment::class);
    }
}
