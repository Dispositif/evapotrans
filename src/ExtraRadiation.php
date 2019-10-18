<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file
 */

/** @noinspection ALL */

namespace Evapotrans;

/**
 * Class MeteoCalculation.
 */
class ExtraRadiation
{
    use CalcTrait;

    /**
     * @var int
     */
    private $dayOfTheYear;

    /**
     * @var float|mixed
     */
    private $latitude;

    /**
     * ExtraRadiation constructor.
     *
     * @param int   $dayOfTheYear
     * @param float $latitude
     */
    public function __construct(MeteoData $meteoData)
    {
        $this->dayOfTheYear = $meteoData->getDaysOfYear();
        $this->latitude = $meteoData->getLocation()->getLatitude();
    }

    /**
     * Extraterrestrial radiation for daily periods (Ra) [21].
     *
     * @return float|int MJ.m-2.d-1
     */
    public function extraterrestrialRadiationDailyPeriod()
    {
        $rlat = $this->degres2radian($this->latitude);
        $dr = $this->inverseRelativeDistanceEarthSun($this->dayOfTheYear);
        $d = $this->solarDeclinaison($this->dayOfTheYear);
        $ws = $this->sunsetHourAngle($this->dayOfTheYear, $this->latitude);

        $Ra = 24 * 60 / pi() * 0.0820 * $dr * ($ws * sin($rlat) * sin($d) + cos($rlat) * cos($d) * sin($ws));

        return round($Ra, 1); // MJ.m-2.d-1
    }

    /**
     * @param int $dayOfTheYear
     *
     * @return float|int
     */
    private function inverseRelativeDistanceEarthSun(int $dayOfTheYear)
    {
        // inverse relative distance earth-sun
        $dr = 1 + 0.033 * cos(2 * pi() * $dayOfTheYear / 365);

        return $dr;
    }
}
