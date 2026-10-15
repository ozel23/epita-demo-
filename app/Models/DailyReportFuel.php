<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class DailyReportFuel extends DomainModel
{
    use HasFactory;
    protected $table = 'daily_report_fuels';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
