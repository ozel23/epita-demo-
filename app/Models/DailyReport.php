<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class DailyReport extends DomainModel
{
    use HasFactory;
    protected static function booted()
    {
        static::creating(function ($report) { if (!$report->client_uuid) $report->client_uuid = (string) \Illuminate\Support\Str::uuid(); });
        static::saved(function ($report) { \Illuminate\Support\Facades\DB::table('sync_changes')->insert(['daily_report_id'=>$report->id,'plant_id'=>$report->plant_id,'owner_id'=>$report->created_by,'server_version'=>$report->server_version ?: 1,'created_at'=>now(),'updated_at'=>now()]); });
        static::updating(function ($report) { $report->server_version = ((int) $report->getOriginal('server_version')) + 1; });
    }
    protected $table = 'daily_reports';
    protected $guarded = ['id'];
    protected $casts = ['server_version' => 'integer', 'active' => 'boolean', 'work_start_at' => 'datetime', 'work_end_at' => 'datetime', 'approved_at' => 'datetime', 'submitted_at' => 'datetime', 'verified_at' => 'datetime', 'report_date' => 'date:Y-m-d', 'meter_start' => 'decimal:2', 'meter_end' => 'decimal:2', 'meter_total' => 'decimal:2'];
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
    public function operator()
    {
        return $this->belongsTo(Personnel::class, 'main_operator_id');
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    public function activities()
    {
        return $this->hasMany(DailyReportActivity::class);
    }
    public function fuels()
    {
        return $this->hasMany(DailyReportFuel::class);
    }
    public function helpers()
    {
        return $this->hasMany(DailyReportPersonnel::class);
    }
    public function attachments()
    {
        return $this->hasMany(DailyReportAttachment::class);
    }
    public function approvals()
    {
        return $this->hasMany(ApprovalHistory::class);
    }
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}
