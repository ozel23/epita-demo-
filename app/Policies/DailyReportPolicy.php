<?php

namespace App\Policies;

use App\Models\DailyReport;
use App\Models\User;
use App\Support\PlantAccess;
class DailyReportPolicy
{
    public function view(User $u, DailyReport $r)
    {
        return in_array($r->plant_id, PlantAccess::ids($u)) && ($u->can('daily-report.view-all') || $r->created_by === $u->id);
    }
    public function update(User $u, DailyReport $r)
    {
        return $this->view($u, $r) && $r->created_by === $u->id && $u->can('daily-report.create') && in_array($r->status, ['DRAFT', 'RETURNED']);
    }
    public function submit(User $u, DailyReport $r)
    {
        return $this->update($u, $r) && $u->can('daily-report.submit');
    }
    public function verify(User $u, DailyReport $r)
    {
        return $this->view($u, $r) && $r->created_by !== $u->id && $u->can('daily-report.verify') && $r->status === 'SUBMITTED';
    }
    public function approve(User $u, DailyReport $r)
    {
        return $this->view($u, $r) && $r->created_by !== $u->id && $u->can('daily-report.approve') && $r->status === 'VERIFIED';
    }
    public function revise(User $u, DailyReport $r)
    {
        return $this->view($u, $r) && $u->can('daily-report.revise') && $r->status === 'APPROVED';
    }
}
