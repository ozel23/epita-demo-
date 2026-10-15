<?php

namespace App\Support;

use App\Models;
class MasterRegistry
{
    public static function all(): array
    {
        return ['companies' => ['Perusahaan', Models\Company::class, ['code', 'name', 'address', 'active']], 'plants' => ['Plant / Estate', Models\Plant::class, ['company_id', 'code', 'name', 'active']], 'divisions' => ['Divisi', Models\Division::class, ['plant_id', 'code', 'name', 'active']], 'locations' => ['Lokasi / Blok', Models\Location::class, ['plant_id', 'division_id', 'code', 'name', 'location_type', 'active']], 'personnels' => ['Personel', Models\Personnel::class, ['plant_id', 'user_id', 'personnel_number', 'name', 'type', 'active']], 'equipment-types' => ['Jenis Alat', Models\EquipmentType::class, ['code', 'name', 'prefix', 'meter_type', 'active']], 'equipment' => ['Alat & Kendaraan', Models\Equipment::class, ['plant_id', 'equipment_type_id', 'default_operator_id', 'code', 'name', 'registration_number', 'brand', 'model', 'year', 'capacity', 'ownership', 'meter_type', 'performance_unit', 'active']], 'activities' => ['Aktivitas', Models\ActivityCode::class, ['code', 'name', 'parent_id', 'sap_reason_code', 'productive', 'requires_photo', 'active']], 'load-types' => ['Jenis Muatan', Models\LoadType::class, ['code', 'name', 'active']], 'work-units' => ['Satuan Kerja', Models\WorkUnit::class, ['code', 'name', 'active']], 'fuels' => ['Jenis BBM', Models\FuelType::class, ['code', 'name', 'active']], 'fueling-locations' => ['Tempat Pengisian', Models\FuelingLocation::class, ['plant_id', 'code', 'name', 'active']], 'fuel-ratios' => ['Standar Rasio BBM', Models\FuelRatioStandard::class, ['equipment_type_id', 'lower_limit', 'upper_limit', 'effective_date', 'unit']], 'non-moving-reasons' => ['Alasan Tidak Bergerak', Models\NonMovingReason::class, ['code', 'name', 'active']], 'spare-parts' => ['Suku Cadang', Models\SparePart::class, ['code', 'name', 'unit', 'unit_cost', 'active']], 'overtime-rules' => ['Aturan Lembur', Models\OvertimeRule::class, ['plant_id', 'start_time', 'tariff', 'max_helpers']]];
    }
    public static function get($key)
    {
        abort_unless(isset(self::all()[$key]), 404);
        return self::all()[$key];
    }
    public static function foreign(): array
    {
        return ['company_id' => Models\Company::class, 'plant_id' => Models\Plant::class, 'division_id' => Models\Division::class, 'user_id' => Models\User::class, 'equipment_type_id' => Models\EquipmentType::class, 'default_operator_id' => Models\Personnel::class, 'parent_id' => Models\ActivityCode::class];
    }
    public static function label($field)
    {
        return ['code' => 'Kode', 'name' => 'Nama', 'active' => 'Aktif', 'company_id' => 'Perusahaan', 'plant_id' => 'Plant', 'division_id' => 'Divisi', 'user_id' => 'Akun', 'personnel_number' => 'Nomor SAP', 'equipment_type_id' => 'Jenis Alat', 'default_operator_id' => 'Operator Default', 'registration_number' => 'Nomor Polisi', 'brand' => 'Merek', 'model' => 'Model', 'year' => 'Tahun', 'capacity' => 'Kapasitas', 'ownership' => 'Kepemilikan', 'meter_type' => 'Jenis Meter', 'performance_unit' => 'Satuan Kinerja', 'location_type' => 'Jenis Lokasi', 'type' => 'Jenis Personel', 'prefix' => 'Prefix', 'parent_id' => 'Aktivitas Induk', 'sap_reason_code' => 'Reason SAP', 'productive' => 'Produktif', 'requires_photo' => 'Foto Wajib', 'lower_limit' => 'Batas Bawah', 'upper_limit' => 'Batas Atas', 'effective_date' => 'Tanggal Berlaku', 'unit' => 'Satuan', 'unit_cost' => 'Harga Satuan', 'start_time' => 'Mulai Lembur', 'tariff' => 'Tarif', 'max_helpers' => 'Maksimal Helper'][$field] ?? ucwords(str_replace('_', ' ', $field));
    }
}
