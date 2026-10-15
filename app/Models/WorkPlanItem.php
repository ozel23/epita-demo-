<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class WorkPlanItem extends DomainModel
{
    use HasFactory;
    protected $table = 'work_plan_items';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
