<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class BreakdownReport extends DomainModel
{
    use HasFactory;
    protected static function booted() { static::creating(function ($report) { if (!$report->client_uuid) $report->client_uuid=(string) \Illuminate\Support\Str::uuid(); }); }
    protected $table = 'breakdown_reports';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean', 'occurred_at'=>'datetime'];
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
    public function attachments()
    {
        return $this->hasMany(BreakdownAttachment::class);
    }
}
