<?php

namespace Tests\Unit;

use App\Services\ReportCalculator;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class ReportCalculatorTest extends TestCase
{
    public function test_decimal_meter_calculation_is_exact()
    {
        $calculator = new ReportCalculator();
        $this->assertSame('0.01', $calculator->meter('9999999999998.99', '9999999999999.00'));
        $this->assertSame('0.10', $calculator->meter('0.20', '0.30'));
    }
    public function test_zero_denominator_has_no_fuel_ratio()
    {
        $this->assertNull((new ReportCalculator())->fuelRatio(10, 0));
    }
    public function test_meter_rejects_excess_decimal_precision()
    {
        $this->expectException(ValidationException::class);
        (new ReportCalculator())->meter('1.001', '2.00');
    }
    public function test_explicit_next_day_duration()
    {
        $this->assertSame(270, (new ReportCalculator())->minutes(Carbon::parse('2026-10-14 22:00'), Carbon::parse('2026-10-15 02:30')));
    }
}
