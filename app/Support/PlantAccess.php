<?php

namespace App\Support;

use App\Models\User;
class PlantAccess
{
    public static function ids(User $user): array
    {
        return $user->plants()->where('active', true)->pluck('plants.id')->all();
    }
    public static function check(User $user, $plant): void
    {
        abort_unless(in_array((int) $plant, array_map('intval', static::ids($user)), true), 403, 'Plant tidak diizinkan.');
    }
    public static function query($query, User $user)
    {
        $query->whereIn('plant_id', static::ids($user));
        $active = request()->hasSession() ? request()->session()->get('active_plant_id') : null;
        if ($active) {
            $query->where('plant_id', $active);
        }
        return $query;
    }
}
