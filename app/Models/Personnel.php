<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Personnel extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'personnels';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
}
