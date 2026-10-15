<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
class NotifyOverdue extends Command
{
    protected $signature = 'operations:notify-overdue';
    protected $description = 'Notify operators and supervisors about overdue assignments once daily';
    public function handle()
    {
        foreach (Assignment::with('operator.user', 'equipment')->whereIn('status', ['ASSIGNED', 'IN_PROGRESS'])->where('planned_end_at', '<', now())->get() as $a) {
            $users = User::whereHas('plants', function ($q) use ($a) {
                $q->where('plants.id', $a->plant_id);
            })->permission('assignment.manage')->get();
            if ($a->operator->user) {
                $users->push($a->operator->user);
            }
            foreach ($users->unique('id') as $u) {
                $exists = $u->notifications()->whereDate('created_at', today())->where('data', 'like', '%Penugasan #' . $a->id . ' overdue%')->exists();
                if (!$exists) {
                    app(NotificationService::class)->send($u, 'Penugasan #' . $a->id . ' overdue — ' . $a->equipment->code, '/assignments');
                }
            }
        }
        $this->info('Overdue notifications processed.');
        return 0;
    }
}
