<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Equipment extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'equipment';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
    public function type()
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }
    public function reports()
    {
        return $this->hasMany(DailyReport::class);
    }
    public function statusHistories()
    {
        return $this->hasMany(EquipmentStatusHistory::class);
    }
    public function latestLocation()
    {
        return $this->hasOne(EquipmentLocationHistory::class)->latestOfMany();
    }
}
