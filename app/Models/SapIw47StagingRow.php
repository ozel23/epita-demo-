<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class SapIw47StagingRow extends DomainModel
{
    use HasFactory;
    protected $table = 'sap_iw47_staging_rows';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean', 'raw_data' => 'array', 'normalized_data' => 'array', 'errors' => 'array'];
    public function batch()
    {
        return $this->belongsTo(SapImportBatch::class, 'sap_import_batch_id');
    }
}
