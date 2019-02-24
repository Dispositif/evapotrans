<?php

/** @noinspection ALL */

namespace Evapotrans;

/**
 * Class MeteoCalculation.
 */
class ExtraRadiation
{
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
     * Extraterrestrial radiation for daily periods (Ra) [21]
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
     * Convert degres to radian.
     *
     * @param int|float $degres
     * @param int|null  $minutes
     * @return float
     */
    private function degres2radian($degres, int $minutes = null): float
    {
        $minutes = $minutes ?? 0;
        $decimal = $degres + $minutes / 60;

        return pi() / 180 * $decimal;
    }

    /**
     * @param int $dayOfTheYear
     * @return float|int
     */
    private function inverseRelativeDistanceEarthSun(int $dayOfTheYear)
    {
        // inverse relative distance earth-sun
        $dr = 1 + 0.033 * cos(2 * pi() * $dayOfTheYear / 365);

        return $dr;
    }

    /**
     * @param int $dayOfTheYear
     * @return float
     */
    private function solarDeclinaison(int $dayOfTheYear)
    {
        // solar declinaison
        $d = 0.409 * sin(2 * pi() * $dayOfTheYear / 365 - 1.39);

        return $d;
    }

    /**
     * sunset hour angle (ws) [25 or 26]
     * Error in algo writing on the FAO doc with J parameter
     * J latitude in radian / verified in http://edis.ifas.ufl.edu/ae459.
     *
     * @param int $dayOfTheYear
     * @param     $latitude
     * @return float
     */
    private function sunsetHourAngle(int $dayOfTheYear, $latitude)
    {
        $j = $this->degres2radian($latitude);
        $d = $this->solarDeclinaison($dayOfTheYear);
        $ws = acos(-tan($j) * tan($d)); // explication equation erronée ?

        return round($ws, 3);
    }

    /**
     * Daylight hours or maximum possible duration of sunshine (N).
     * todo move to MeteoData::daylightHours ?
     *
     * @param int $dayOfTheYear
     * @param     $latitude
     * @return float
     */
    public function daylightHours(int $dayOfTheYear, $latitude): float
    {
        $ws = $this->sunsetHourAngle($dayOfTheYear, $latitude);
        $N = round(24 / pi() * $ws, 1);

        return round($N, 1);
    }
}
