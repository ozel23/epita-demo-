<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class WorkPlan extends DomainModel
{
    use HasFactory;
    protected $table = 'work_plans';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function items()
    {
        return $this->hasMany(WorkPlanItem::class);
    }
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
