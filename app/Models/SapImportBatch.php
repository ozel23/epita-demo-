<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class SapImportBatch extends DomainModel
{
    use HasFactory;
    protected $table = 'sap_import_batches';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean', 'mapping' => 'array'];
    public function rows()
    {
        return $this->hasMany(SapIw47StagingRow::class, 'sap_import_batch_id');
    }
}
