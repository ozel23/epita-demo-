<?php

namespace App\Models;

class MaintenanceAttachment extends DomainModel
{
    protected $guarded = ['id'];
    public function order()
    {
        return $this->belongsTo(MaintenanceWorkOrder::class, 'maintenance_work_order_id');
    }
}
