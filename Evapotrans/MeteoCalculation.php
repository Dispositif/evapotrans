<?php /** @noinspection ALL */

namespace Evapotrans;

/**
 * Class MeteoCalculation
 *
 * @package Evapotranspiration
 */
class MeteoCalculation
{

    /**
     * Convert degres to radian
     *
     * @param int|float $degres
     * @param int|null  $minutes
     *
     * @return float
     */
    function degres2radian($degres, int $minutes = null): float
    {
        $minutes = $minutes ?? 0;
        $decimal = $degres + $minutes / 60;

        return pi() / 180 * $decimal;
    }

    /**
     * Number of the day in the year
     * 1-01=>1, 27 mars => 85
     * @param \DateTime $dateTime
     *
     * @return int
     */
    public function daysOfYear(\DateTime $dateTime): int
    {
        return 1 + $dateTime->format('z');
    }

    // Degré jour de croissance (DJ)
    // DJ = (Tmax - Tmin) /2 - Tbase
    public function degre_jour($Tmin, $Tmax, $Tbase = 10, $Tmaxbase = 30)
    {
        $Tmin = max($Tmin, ($Tbase + $Tmin) / 2); // pondération car seulement T>Tbase compte
        $Tmax = min($Tmax, ($Tmaxbase + $Tmax) / 2); // pondération car seulement T<30°C compte
        $DJ = ($Tmax - $Tmin) / 2 - $Tbase;
        $DJ = max($DJ, 0);

        return $DJ;
    }


// indice d'aridité
// https://fr.wikipedia.org/wiki/Indice_d%27aridit%C3%A9

// indice aridité de De Martonne
// Pour un mois
    public function indice_mensuel_aridite($temperature_moyenne, $precipitations_totales)
    {
        // I = 12 * p / ( t+10)
        $indice = round(12 * $precipitations_totales / ($temperature_moyenne + 10));

        return $indice;
    }

    public function climat_by_indicearidite($temperature_moyenne, $precipitations_totales)
    {
        $climat = false;
        $indice = indice_mensuel_aridite($temperature_moyenne, $precipitations_totales);
        if ($indice < 5) {
            $climat = "hyperaride";
        }elseif ($indice < 10) {
            $climat = "aride";
        }elseif ($indice < 20) {
            $climat = "semi-aride";
        }elseif ($indice < 30) {
            $climat = "semi-humide";
        }elseif ($indice >= 30) {
            $climat = "humide";
        }

        return $climat;
    }


// indice du diagramme ombrothermique (par mois) de Henri Gaussen (P=2T)
// https://fr.wikipedia.org/wiki/Diagramme_climatique P = 2T
//http://www.mgm.fr/PUB/Mappemonde/M297/Charre.pdf
    public function indice_ombrothermique($temperature_moyenne, $precipitations_totales)
    {
        return round($precipitations_totales / $temperature_moyenne, 1);
        // si indice < 2 => sec (climat méditerranée), > 3 (humide)
    }

    public function climat_by_ombrothermique($temperature_moyenne, $precipitations_totales)
    {
        $indice = indice_ombrothermique($temperature_moyenne, $precipitations_totales);
        $climat = false;
        if ($indice < 2) {
            $climat = 'sec';
        }elseif ($indice < 3) {
            $climat = 'tempéré';
        }elseif ($indice >= 3) {
            $climat = 'humide';
        }

        return $climat;
    }

    /**
     * Extraterrestrial radiation for daily periods (Ra) [21]
     * TODO refactor : explode + injecte Location/Date ?
     *
     * @param int   $dayOfTheYear
     * @param float $latitude
     *
     * @return float|int MJ.m-2.d-1
     */
    public function extraterrestrialRadiationDailyPeriod(int $dayOfTheYear, float $latitude)
    {
        $rlat = $this->degres2radian($latitude);
        $dr = $this->inverseRelativeDistanceEarthSun($dayOfTheYear);
        $d = $this->solarDeclinaison($dayOfTheYear);
        $ws = $this->sunsetHourAngle($dayOfTheYear, $latitude);

        $Ra = 24 * 60 / pi() * 0.0820 * $dr * ($ws * sin($rlat) * sin($d) + cos($rlat) * cos($d) * sin($ws));

        return round($Ra, 1); // MJ.m-2.d-1
    }

    /**
     * @param int $dayOfTheYear
     *
     * @return float|int
     */
    protected function inverseRelativeDistanceEarthSun(int $dayOfTheYear)
    {
// inverse relative distance earth-sun
        $dr = 1 + 0.033 * cos(2 * pi() * $dayOfTheYear / 365);

        return $dr;
    }

    /**
     * @param int $dayOfTheYear
     *
     * @return float
     */
    protected function solarDeclinaison(int $dayOfTheYear)
    {
// solar declinaison
        $d = 0.409 * sin(2 * pi() * $dayOfTheYear / 365 - 1.39);

        return $d;
    }

    /**
     * sunset hour angle (ws) [25 or 26]
     * Error in algo writing on the FAO doc with J parameter
     * J latitude in radian / verified in http://edis.ifas.ufl.edu/ae459
     *
     * @param int $dayOfTheYear
     * @param     $latitude
     *
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
     * Daylight hours (N)
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
