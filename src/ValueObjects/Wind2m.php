<?php

namespace Evapotrans\ValueObjects;

use Evapotrans\Exception;

/**
 * In general, wind speed at 2 m, u2, should be limited to about u2 ³ 0.5 m/s
 * when used in the ETo equation (Equation 6). This is necessary to account for
 * the effects of boundary layer instability and buoyancy of air in promoting
 * exchange of vapour at the surface when air is calm. This effect occurs when
 * the wind speed is small and buoyancy of warm air induces air exchange at the
 * surface. Limiting u2 ³ 0.5 m/s in the ETo equation improves the estimation
 * accuracy under the conditions of very low wind speed. todo max 0.5 + value
 * "estimated/minimized" -> MeteoData ? Class Wind default unit : m/s
 */
class Wind2m extends AbstractMeasure
{
    const UNIT = 'm/s';

    public function __construct(
        float $speed,
        ?string $unit = 'm/s',
        ?int $altitudeM = 2
    ) {
        $speedMS = $this->convertSpeedToMS($speed, $unit);
        $this->value = $this->calcWind2meters($speedMS, $altitudeM);
    }

    /**
     * @param float $speed
     * @param string $unit
     *
     * @return float
     * @throws Exception
     */
    private function convertSpeedToMS(float $speed, string $unit): float
    {
        if ($unit === static::UNIT) {
            return abs($speed);
        }
        if ('km/h' === $unit) {
            return abs(round($speed / 3.6, 1));
        }
        // todo knot, US
        throw new Exception('Speed unit error');
    }

    /**
     * To adjust wind speed data obtained from instruments placed at elevations
     * other than the standard height of 2m, a logarithmic wind speed profile
     * may be used for measurements above a short grassed surface
     * [Eq. 47].
     *
     * @param float $speed
     * @param int   $altitude
     *
     * @return float
     */
    private function calcWind2meters(float $speed, int $altitude): float
    {
        if (2 === $altitude) {
            return $speed;
        }

        return round($speed * 4.87 / log(67.8 * $altitude - 5.42), 1);
    }
}
