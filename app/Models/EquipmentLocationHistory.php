<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class EquipmentLocationHistory extends DomainModel
{
    use HasFactory;
    protected $table = 'equipment_location_histories';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
