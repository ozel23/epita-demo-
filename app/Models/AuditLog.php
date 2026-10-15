<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class AuditLog extends DomainModel
{
    use HasFactory;
    protected $table = 'audit_logs';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean', 'before' => 'array', 'after' => 'array'];
}
