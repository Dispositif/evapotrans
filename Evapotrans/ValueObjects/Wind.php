<?php

namespace Evapotrans\ValueObjects;

use Evapotrans\Exception;

/**
 * In general, wind speed at 2 m, u2, should be limited to about u2 ³ 0.5 m/s when used in the ETo equation (Equation 6). This is necessary to account for the effects of boundary layer instability and buoyancy of air in promoting exchange of vapour at the surface when air is calm. This effect occurs when the wind speed is small and buoyancy of warm air induces air exchange at the surface. Limiting u2 ³ 0.5 m/s in the ETo equation improves the estimation accuracy under the conditions of very low wind speed.
 * todo max 0.5 + value "estimated/minimized" -> MeteoData ?
 *
 * Class Wind
 * default unit : m/s
 *
 * @package Evapotranspiration
 */
class Wind
{
    /**
     * Wind speed at 2meter in m.s-1
     *
     * Where no wind data are available within the region, a value of 2 m/s
     * can be used as a temporary estimate. This value is the average over
     * 2000 weather stations around the globe.
     *
     * light wind => 1.0 m/s
     * light to moderate wind => 1 - 3 m/s
     * moderate to strong wind => 3 - 5 m/s
     * strong wind => 5.0 m/s
     *
     * @var float m.s-1
     */
    public $speed2meters = 2.0;

    //public $speed;
    //public $altitude;

    /**
     * Wind constructor.
     *
     * @param float $speed
     * @param Unit  $unit
     * @param int   $altitudeM
     *
     * @throws Exception
     */
    public function __construct(float $speed, Unit $unit, ?int $altitudeM = 10)
    {
        $speedMS = $this->convertSpeedToMS($speed, $unit);
        //$this->altitude = $altitudeM;
        //$this->speed = $speedMS;
        $this->speed2meters = $this->calcWind2meters($speedMS, $altitudeM);
    }

    /**
     * @return mixed
     */
    public function getSpeed2meters()
    {
        return $this->speed2meters;
    }

    /**
     * @param float $speed
     * @param Unit  $unit
     *
     * @return float
     * @throws Exception
     */
    private function convertSpeedToMS(float $speed, Unit $unit): float
    {
        if ($unit->equal(new Unit('m/s'))) {
            return abs($speed);
        }
        if ($unit->equal(new Unit('km/h'))) {
            return abs(round($speed / 3.6, 1));
        }
        // todo knot, US
        throw new Exception('Speed unit error');
    }

    /**
     * To adjust wind speed data obtained from instruments placed at elevations other than the standard height of 2m, a logarithmic wind speed profile may be used for measurements above a short grassed surface
     * [Eq. 47]
     *
     * @param float $speed
     * @param int   $altitude
     *
     * @return float
     */
    private function calcWind2meters(float $speed, int $altitude):float
    {
        if ($altitude === 2) {
            return $speed;
        }

        return round($speed * 4.87 / log(67.8 * $altitude - 5.42), 1);
    }

}
