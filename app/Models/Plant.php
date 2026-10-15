<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Plant extends DomainModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'plants';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
