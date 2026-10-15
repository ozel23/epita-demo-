<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class BreakdownAttachment extends DomainModel
{
    use HasFactory;
    protected $table = 'breakdown_attachments';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
}
