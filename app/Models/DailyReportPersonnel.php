<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class DailyReportPersonnel extends DomainModel
{
    use HasFactory;
    protected $table = 'daily_report_personnels';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
