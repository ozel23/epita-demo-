<?php

namespace Database\Seeders;

use App\Models\ActivityCode;
use App\Models\Company;
use App\Models\Division;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\FuelingLocation;
use App\Models\FuelRatioStandard;
use App\Models\FuelType;
use App\Models\LoadType;
use App\Models\Location;
use App\Models\NonMovingReason;
use App\Models\OvertimeRule;
use App\Models\Personnel;
use App\Models\Plant;
use App\Models\SapMappingRule;
use App\Models\SparePart;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $matrix = ['operator' => ['equipment.view', 'daily-report.create', 'daily-report.submit', 'breakdown.create'], 'supervisor' => ['equipment.view', 'daily-report.view-all', 'daily-report.create', 'daily-report.submit', 'daily-report.verify', 'daily-report.approve', 'daily-report.override-meter', 'daily-report.revise', 'assignment.manage', 'assignment.override', 'report.export', 'breakdown.create', 'maintenance.close'], 'admin' => ['equipment.view', 'equipment.manage', 'daily-report.view-all', 'sap.import', 'sap.reconcile', 'report.export', 'user.manage', 'role.assign', 'audit.view'], 'maintenance' => ['equipment.view', 'maintenance.manage', 'maintenance.close', 'breakdown.create', 'report.export'], 'manager' => ['equipment.view', 'daily-report.view-all', 'daily-report.approve', 'daily-report.revise', 'report.export', 'audit.view']];
        $permissions = collect($matrix)->flatten()->unique()->values()->all();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        foreach ($matrix as $name => $perms) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
        $company = Company::firstOrCreate(['code' => 'BSP'], ['name' => 'PT Bakrie Sumatera Plantations Tbk', 'address' => 'Kisaran, Sumatera Utara', 'active' => true]);
        $plants = [];
        foreach (['BS01' => 'Sei Baleh', 'BS12' => 'Kisaran', 'BS13' => 'Bunut'] as $code => $name) {
            $plants[] = Plant::firstOrCreate(['code' => $code], ['name' => $name, 'company_id' => $company->id]);
        }
        $units = [];
        foreach (['ORANG' => 'Orang', 'KG' => 'Kg', 'POKOK' => 'Pokok', 'L' => 'Liter', 'PALLET' => 'Pallet', 'KM' => 'KM', 'H' => 'H'] as $code => $name) {
            $units[$code] = WorkUnit::firstOrCreate(['code' => $code], ['name' => $name]);
        }
        foreach (['Orang', 'Pupuk', 'Bibit', 'Glifosat', 'Material', 'Latex', 'Pallet'] as $name) {
            LoadType::firstOrCreate(['code' => strtoupper($name)], ['name' => $name]);
        }
        foreach (['Solar', 'Dexlite', 'Pertalite', 'Oli', 'Gemuk'] as $name) {
            FuelType::firstOrCreate(['code' => strtoupper($name)], ['name' => $name]);
        }
        foreach (['Libur', 'Rusak/Perbaikan', 'Tidak Ada Tugas', 'Cuaca'] as $i => $name) {
            NonMovingReason::firstOrCreate(['code' => 'NM' . ($i + 1)], ['name' => $name]);
        }
        $activity = [];
        foreach (['237' => 'Angkut Pupuk', '050' => 'Langsir Bibit', '000' => 'General'] as $code => $name) {
            $activity[$code] = ActivityCode::firstOrCreate(['code' => (string) $code], ['name' => $name, 'sap_reason_code' => (string) $code, 'productive' => $code !== '000']);
        }
        $types = [];
        foreach (['TRK' => ['Truk', 'KM'], 'SUV' => ['Kendaraan Ringan', 'KM'], 'BUS' => ['Bus Karyawan', 'KM'], 'PCP' => ['Excavator', 'HM'], 'MIB' => ['Mesin Pabrik', 'HOUR_KWH']] as $prefix => $meta) {
            $types[$prefix] = EquipmentType::firstOrCreate(['code' => $prefix], ['name' => $meta[0], 'prefix' => $prefix, 'meter_type' => $meta[1]]);
            FuelRatioStandard::firstOrCreate(['equipment_type_id' => $types[$prefix]->id, 'effective_date' => '2026-01-01'], ['lower_limit' => $meta[1] === 'KM' ? 0.1 : 2, 'upper_limit' => $meta[1] === 'KM' ? 0.6 : 20, 'unit' => $meta[1] === 'KM' ? 'liter/km' : 'liter/hour']);
        }
        foreach ($plants as $i => $plant) {
            $div = Division::firstOrCreate(['plant_id' => $plant->id, 'code' => 'DIV01'], ['name' => $plant->name]);
            foreach (['X-BS153C07' => 'Sei Baleh', 'TBM-2B13' => 'BARI', 'GC-800' => 'HO / KNO'] as $code => $name) {
                Location::firstOrCreate(['plant_id' => $plant->id, 'code' => $code], ['name' => $name, 'division_id' => $div->id, 'location_type' => strpos($code, 'TBM') === 0 ? 'TBM' : (strpos($code, 'GC') === 0 ? 'GC' : 'TM')]);
            }
            foreach (['SPBU Pulo Bering', 'SPBU Kisaran', 'Tangki Estate'] as $j => $name) {
                FuelingLocation::firstOrCreate(['code' => $plant->code . '-F' . ($j + 1)], ['name' => $name, 'plant_id' => $plant->id]);
            }
            OvertimeRule::firstOrCreate(['plant_id' => $plant->id], ['start_time' => '17:00', 'tariff' => 25000, 'max_helpers' => 4]);
            foreach (['TRK', 'SUV', 'BUS', 'PCP', 'MIB'] as $j => $prefix) {
                Equipment::firstOrCreate(['code' => $prefix . '880000000' . str_pad((string) (15 + $i * 5 + $j), 4, '0', STR_PAD_LEFT)], ['name' => $types[$prefix]->name . ' ' . $plant->name, 'plant_id' => $plant->id, 'equipment_type_id' => $types[$prefix]->id, 'registration_number' => 'BK ' . (8000 + $i * 10 + $j) . ' BSP', 'brand' => $prefix === 'PCP' ? 'Komatsu' : 'Mitsubishi', 'model' => 'Operasional', 'year' => 2022, 'ownership' => 'OWN', 'meter_type' => $types[$prefix]->meter_type, 'current_condition' => 'STANDBY', 'last_meter' => 1000]);
            }
            foreach ($activity as $a) {
                SapMappingRule::firstOrCreate(['plant_id' => $plant->id, 'activity_code_id' => $a->id], ['order_number' => $plant->code . 'MTB00079', 'operation_activity' => '0010', 'work_center' => 'GA-015', 'actual_work_unit' => 'KM', 'value_source' => 'meter_total', 'tolerance' => 0.1]);
            }
        }
        foreach ($matrix as $role => $perms) {
            $user = User::firstOrCreate(['email' => $role . '@vra.test'], ['username' => $role, 'name' => ['operator' => 'Budi Santoso', 'supervisor' => 'Siti Rahma', 'admin' => 'Admin Kerani', 'maintenance' => 'Agus Teknisi', 'manager' => 'Manager Estate'][$role], 'password' => bcrypt('DemoVra!2026'), 'active' => true]);
            $user->syncRoles([$role]);
            $user->plants()->sync(array_map(function ($p) {
                return $p->id;
            }, $plants));
            if (in_array($role, ['operator', 'maintenance'])) {
                Personnel::firstOrCreate(['personnel_number' => $role === 'operator' ? '100015' : '100025'], ['name' => $user->name, 'user_id' => $user->id, 'plant_id' => $plants[0]->id, 'type' => $role === 'operator' ? 'OPERATOR' : 'MECHANIC']);
            }
        }
        foreach ($plants as $i => $plant) {
            Personnel::firstOrCreate(['personnel_number' => 'H' . ($i + 1) . '001'], ['name' => 'Helper ' . $plant->name, 'plant_id' => $plant->id, 'type' => 'HELPER']);
            if ($i > 0) {
                Personnel::firstOrCreate(['personnel_number' => 'O' . ($i + 1) . '001'], ['name' => 'Operator ' . $plant->name, 'plant_id' => $plant->id, 'type' => 'DRIVER']);
            }
        }
        foreach (['FLT-001' => 'Filter Solar', 'OLI-001' => 'Oli Mesin', 'BLT-001' => 'Belt Mesin'] as $code => $name) {
            SparePart::firstOrCreate(['code' => $code], ['name' => $name, 'unit' => 'pcs', 'unit_cost' => 150000]);
        }
    }
}
