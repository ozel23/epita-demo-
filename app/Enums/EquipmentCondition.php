<?php

namespace App\Enums;

final class EquipmentCondition
{
    const WORKING = 'WORKING';
    const STANDBY = 'STANDBY';
    const BREAKDOWN = 'BREAKDOWN';
    const MAINTENANCE = 'MAINTENANCE';
    const VALUES = [self::WORKING, self::STANDBY, self::BREAKDOWN, self::MAINTENANCE];
}
