<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Division;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Personnel;
class FilterOptions
{
    public static function forUser($user): array
    {
        return ['companies' => Company::whereIn('id', $user->plants()->pluck('company_id'))->get(), 'divisions' => PlantAccess::query(Division::where('active', true), $user)->get(), 'types' => EquipmentType::where('active', true)->get(), 'equipment' => PlantAccess::query(Equipment::withTrashed(), $user)->get(), 'operators' => PlantAccess::query(Personnel::withTrashed()->whereIn('type', ['DRIVER', 'OPERATOR']), $user)->get()];
    }
}
