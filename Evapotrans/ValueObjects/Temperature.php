<?php

namespace Evapotrans\ValueObjects;

use Evapotrans\Exception;

class Temperature extends AbstractMeasure
{
    const UNIT = '°C';

    protected function convertUnit(float $value, string $unit): float
    {
        if ($unit === self::UNIT) {
            return $value;
        }
        if ($unit === '°F' || $unit === 'F') {
            return round(5 / 9 * ($value - 32), 1);
        }
        throw new Exception("No unit conversion for $unit");
    }
}
