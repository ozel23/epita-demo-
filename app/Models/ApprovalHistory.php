<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class ApprovalHistory extends DomainModel
{
    use HasFactory;
    protected $table = 'approval_histories';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean', 'snapshot' => 'array'];
}
