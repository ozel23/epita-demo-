<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class SapMappingRule extends DomainModel
{
    use HasFactory;
    protected $table = 'sap_mapping_rules';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
