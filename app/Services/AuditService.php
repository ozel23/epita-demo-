<?php

namespace App\Services;

use App\Models\AuditLog;
class AuditService
{
    public static function record(string $event, $subject = null, array $before = [], array $after = []): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'plant_id' => $subject->plant_id ?? null, 'event' => $event, 'subject_type' => $subject ? get_class($subject) : null, 'subject_id' => $subject->id ?? null, 'before' => $before, 'after' => $after, 'ip' => request()->ip()]);
    }
}
