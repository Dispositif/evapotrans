<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file
 */

namespace Evapotrans;

trait CalcTrait
{
    /**
     * sunset hour angle (ws) [25 or 26]
     * Error in algo writing on the FAO doc with J parameter
     * J latitude in radian / verified in http://edis.ifas.ufl.edu/ae459.
     *
     * @param int $dayOfTheYear
     * @param     $latitude
     *
     * @return float radian
     */
    private function sunsetHourAngle(int $dayOfTheYear, $latitude)
    {
        $j = $this->degres2radian($latitude);
        $d = $this->solarDeclinaison($dayOfTheYear);
        $ws = acos(-tan($j) * tan($d)); // explication equation erronée ?

        return round($ws, 3);
    }

    /**
     * Convert degres to radian.
     *
     * @param int|float $degres
     * @param int|null  $minutes
     *
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
     *
     * @return float
     */
    private function solarDeclinaison(int $dayOfTheYear)
    {
        // solar declinaison
        $d = 0.409 * sin(2 * pi() * $dayOfTheYear / 365 - 1.39);

        return $d;
    }
}
