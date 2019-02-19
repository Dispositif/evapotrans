<?php

namespace Evapotrans\ValueObjects;

use Evapotrans\Exception;

class Humidity extends AbstractMeasure
{
    // ratio expressed as %
    const UNIT = '%';

    protected function convertUnit(float $value, string $unit): float
    {
        if (self::UNIT === $unit && $value <= 100 && $value > 1) {
            return $value;
        }

        throw new Exception("No unit conversion for $unit");
    }
}
