<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
final class ReportCalculator
{
    public function meter($start, $end): string
    {
        foreach ([$start, $end] as $value) {
            if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', (string) $value)) {
                throw ValidationException::withMessages(['meter_start' => 'Meter maksimal 13 digit dan dua desimal.']);
            }
        }
        if (bccomp((string) $end, (string) $start, 2) < 0) {
            throw ValidationException::withMessages(['meter_end' => 'Meter akhir tidak boleh lebih kecil dari meter awal.']);
        }
        return bcsub((string) $end, (string) $start, 2);
    }
    public function minutes(Carbon $start, Carbon $end): int
    {
        if ($end->lte($start) || $start->diffInMinutes($end) > 1440) {
            throw ValidationException::withMessages(['work_end_at' => 'Durasi harus lebih dari nol dan maksimal 24 jam.']);
        }
        return $start->diffInMinutes($end);
    }
    public function fuelRatio($used, $denominator): ?float
    {
        return $denominator > 0 ? round($used / $denominator, 4) : null;
    }
}
