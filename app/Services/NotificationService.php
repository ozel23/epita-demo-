<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class NotificationService
{
    public function send(User $u, string $message, string $url)
    {
        DB::table('notifications')->insert(['id' => (string) Str::uuid(), 'type' => 'operational', 'notifiable_type' => User::class, 'notifiable_id' => $u->id, 'data' => json_encode(['message' => $message, 'url' => $url]), 'created_at' => now(), 'updated_at' => now()]);
    }
    public function report(DailyReport $r)
    {
        $users = in_array($r->status, ['RETURNED', 'APPROVED']) ? User::whereKey($r->created_by)->get() : User::whereHas('plants', function ($q) use ($r) {
            $q->where('plants.id', $r->plant_id);
        })->permission($r->status === 'VERIFIED' ? 'daily-report.approve' : 'daily-report.verify')->get();
        foreach ($users as $u) {
            $this->send($u, $r->report_number . ' — ' . $r->status, '/daily-reports/' . $r->id);
        }
    }
}
