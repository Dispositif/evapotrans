<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file.
 */

namespace Evapotrans\ValueObjects;

use Evapotrans\Exception;

abstract class AbstractMeasure
{
    const UNIT = 'UNDEFINED';

    protected $value;

    /**
     * Measurement constructor.
     *
     * @param float       $value
     * @param string|null $unit
     *
     * @throws Exception
     */
    public function __construct(float $value, string $unit = null)
    {
        $unit = ($unit) ?? static::UNIT;
        $value = $this->convertUnit($value, $unit);
        $this->value = $value;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getUnit(): string
    {
        return static::UNIT;
    }

    /**
     * @param float  $value
     * @param string $unit
     *
     * @return float
     *
     * @throws Exception
     */
    protected function convertUnit(float $value, string $unit): float
    {
        if (mb_strtolower($unit) === mb_strtolower(static::UNIT)) {
            return $value;
        }

        throw new Exception("No unit conversion for $unit");
    }
}
