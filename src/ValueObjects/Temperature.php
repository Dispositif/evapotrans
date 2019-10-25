<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file.
 */

namespace Evapotrans\ValueObjects;

use Evapotrans\Exception;

class Temperature extends AbstractMeasure
{
    const UNIT = '°C';

    protected function convertUnit(float $value, string $unit): float
    {
        if (self::UNIT === $unit) {
            return $value;
        }
        if ('°F' === $unit || 'F' === $unit) {
            return round(5 / 9 * ($value - 32), 1);
        }

        throw new Exception("No unit conversion for $unit");
    }
}
