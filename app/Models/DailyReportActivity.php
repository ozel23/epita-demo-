<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class DailyReportActivity extends DomainModel
{
    use HasFactory;
    protected $table = 'daily_report_activities';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function activity()
    {
        return $this->belongsTo(ActivityCode::class, 'activity_code_id');
    }
    public function unit()
    {
        return $this->belongsTo(WorkUnit::class, 'work_unit_id');
    }
}
