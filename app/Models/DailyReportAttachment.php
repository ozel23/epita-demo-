<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class DailyReportAttachment extends DomainModel
{
    use HasFactory;
    protected $table = 'daily_report_attachments';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
