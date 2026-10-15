<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Company extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'companies';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function plants()
    {
        return $this->hasMany(Plant::class);
    }
}
