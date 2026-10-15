<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class SapReconciliation extends DomainModel
{
    use HasFactory;
    protected $table = 'sap_reconciliations';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function report()
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id');
    }
    public function row()
    {
        return $this->belongsTo(SapIw47StagingRow::class, 'sap_iw47_staging_row_id');
    }
}
