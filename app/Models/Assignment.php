<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class Assignment extends DomainModel
{
    use HasFactory;
    protected $table = 'assignments';
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean', 'planned_start_at' => 'datetime', 'planned_end_at' => 'datetime'];
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
    public function operator()
    {
        return $this->belongsTo(Personnel::class, 'operator_id');
    }
    public function helpers()
    {
        return $this->belongsToMany(Personnel::class, 'assignment_personnels');
    }
}
