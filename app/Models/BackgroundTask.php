<?php

namespace App\Models;

class BackgroundTask extends DomainModel
{
    protected $guarded = ['id'];
    protected $casts = ['parameters' => 'array'];
}
