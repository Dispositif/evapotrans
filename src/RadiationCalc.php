<?php

namespace Evapotrans;

class RadiationCalc
{
    /**
     * see Location
     */
    const DEFAULT_KRS = 0.16;
    /**
     * @var MeteoData
     */
    private $meteoData;

    /**
     * $albedo (a)(α) : albedo or canopy reflection coefficient for the
     * reference crop
     * (végétation 0.20-0.25) pour herbe verte = 0.23
     * todo get/move on Area.
     *
     * @var float
     */
    private $albedo = 0.23;

    /**
     * @var ExtraRadiation
     */
    private $meteoCalc;

    /**
     * RadiationCalc constructor.
     *
     * @param MeteoData $meteoData
     * @param float     $albedo
     */
    public function __construct(MeteoData $meteoData, $albedo = 0.23)
    {
        $this->meteoData = $meteoData;
        $this->meteoCalc = new ExtraRadiation($meteoData);
        $this->albedo = $albedo; // TODO (get from Area or MeteoData)
    }

    /**
     * Rn from data.
     *
     * @return float Rn
     * @throws Exception
     */
    public function netRadiationFromMeteodata(): float
    {
        // inutile : strategy for solarRadiation
        //        if (!$this->meteoData->getActualSunnyHours()) {
        //            throw new Exception('Actual sunny hours not defined');
        //        }
        $Ra = $this->extraterresRadiationFromMeteodata();
        $Rs = $this->solarRadiationStrategyFromMeteodata();
        $e_a = (new PenmanCalc($this->meteoData))->actualVaporPressionStrategy(
        );
        // todo refactor

        $a_s = null; // move a_s on MeteoData ?
        $b_s = null; //
        $Rso = $this->clearSkySolarRadiation(
            $Ra,
            $this->meteoData->getLocation()->getAltitude(),
            $a_s,
            $b_s
        );
        $Rns = $this->netSolarRadiation($Rs, $this->getAlbedo());
        $Rnl = $this->netLongwaveRadiation(
            $e_a,
            $this->meteoData->getTmax(),
            $this->meteoData->getTmin(),
            $Rs,
            $Rso
        );

        return $this->netRadiation($Rns, $Rnl);
    }

    /**
     * @return float|int
     */
    private function extraterresRadiationFromMeteodata()
    {
        $Ra = $this->meteoCalc->extraterrestrialRadiationDailyPeriod();

        return $Ra;
    }

    /**
     * Solar radiation (Rs) strategy of calculation
     * todo Refactor RaStrategy.
     *
     * @return float
     * @throws Exception
     */
    private function solarRadiationStrategyFromMeteodata()
    {
        $data = $this->meteoData;
        $Ra = $this->extraterresRadiationFromMeteodata();

        // Determination of solar radiation from measured duration of sunshine
        if ($data->getActualSunnyHours()) {
            $n = $data->getActualSunnyHours();
            $a_s = $data->a_s ?? null;
            $b_s = $data->b_s ?? null;

            $N = $data->getMaxDaylightHours();
            $Rs = $this->solarRadiationFromDurationSunshineAndRa(
                $Ra,
                $n,
                $N,
                $a_s,
                $b_s
            );

            return $Rs;
        }

        //  Determination of solar radiation from temperature data
        //The temperature difference method is recommended for locations where it is not appropriate to import radiation data from a regional station, either because homogeneous climate conditions do not occur, or because data for the region are lacking. For island conditions, the methodology of Equation 50 is not appropriate due to moderating effects of the surrounding water body.
        if ($data->getTmax() && $data->getTmin()) {
            $kRs = ($data->getLocation()->getKRs()) ?? self::DEFAULT_KRS;
            $Rs = $this->solarRadiationFromTemperatures(
                $Ra,
                $data->getTmin(),
                $data->getTmax(),
                $kRs
            );

            return $Rs;
        }

        throw new Exception('no strategy for solarRadiation');
    }

    /**
     * Rs
     * Angstrom formula [35] which relates solar radiation to extraterrestrial
     * radiation and relative sunshine duration Rs solar or shortwave radiation
     * [MJ m-2 day-1], n actual duration of sunshine [hour], N maximum possible
     * duration of sunshine or daylight hours [hour], n/N relative sunshine
     * duration [-], Ra extraterrestrial radiation [MJ m-2 day-1], a_s
     * regression constant, expressing the fraction of extraterrestrial
     * radiation reaching the earth on overcast days (n =0), as+bs fraction of
     * extraterrestrial radiation reaching the earth on clear days (n = N).
     *
     * @param float      $Ra
     * @param float      $n
     * @param float      $N
     * @param float|null $a_s
     * @param float|null $b_s
     *
     * @return float
     */
    private function solarRadiationFromDurationSunshineAndRa(
        float $Ra,
        float $n,
        float $N,
        ?float $a_s = 0.25,
        ?float $b_s = 0.50
    ): float {
        $a_s = ($a_s) ?? 0.25;
        $b_s = ($b_s) ?? 0.50;

        // TODO : strategy for using a parameter "$n/$N coefficient"
        $Rs = ($a_s + $b_s * $n / $N) * $Ra;

        return round($Rs, 1); // MJ m-2 day-1
    }

    /**
     * Solar Radiation data derived from air temperature differences
     * Equation [50]
     * The temperature difference method is recommended for locations where it
     * is not appropriate to import radiation data from a regional station,
     * either because homogeneous climate conditions do not occur, or because
     * data for the region are lacking. For island conditions, the methodology
     * of Equation 50 is not appropriate due to moderating effects of the
     * surrounding water body. Caution is required when daily computations of
     * ETo are needed. for 'interior' locations, where land mass dominates and
     * air masses are not strongly influenced by a large water body, kRs @
     * 0.16;
     * · for 'coastal' locations, situated on or adjacent to the coast of a
     * large land mass and where air masses are influenced by a nearby water
     * body, kRs @ 0.19.
     *
     * @param float $Ra
     * @param float $Tmin
     * @param float $Tmax
     * @param float $kRs adjustment coefficient
     *
     * @return float
     */
    private function solarRadiationFromTemperatures(
        float $Ra,
        float $Tmin,
        float $Tmax,
        ?float $kRs = 0.18
    ): float {
        $Rs = $kRs * sqrt(abs($Tmax - $Tmin)) * $Ra;

        return round($Rs, 1);
    }

    // Net solar or net shortwave radiation (Rns)

    /**
     * Clear-sky solar radiation (Rso)
     * required for computing net longwave radiation.
     *
     * @param float $Ra
     * @param int   $altitude
     * @param null  $a_s
     * @param null  $b_s
     *
     * @return float Rso [MJ m-2 day-1]
     */
    private function clearSkySolarRadiation(
        float $Ra,
        int $altitude,
        $a_s = null,
        $b_s = null
    ): float {
        if ($a_s && $b_s) {
            $Rso = ($a_s + $b_s) * $Ra;  // [36]
        }else {
            $Rso = (0.75 + 2 * pow(10, -5) * $altitude) * $Ra; // [37]
        }

        return round($Rso, 1);
    }

    // Net longwave radiation (Rnl)
    // TODO : option (TmaxKelvin^4+TminKelvin^4)/2 remplacé par TmoyenKelvin^4

    private function netSolarRadiation($Rs, $albedo)
    {
        return (1 - $albedo) * $Rs;
    }

    // Net radiation (Rn)

    /**
     * @return float
     */
    public function getAlbedo(): float
    {
        return $this->albedo;
    }

    /**
     * Net longwave radiation (Rnl)
     * Stefan-Boltzmann law [39].
     *
     * @param $e_a
     * @param $Tmax
     * @param $Tmin
     * @param $Rs
     * @param $Rso
     *
     * @return float|int
     */
    private function netLongwaveRadiation($e_a, $Tmax, $Tmin, $Rs, $Rso)
    {
        // Stefan-Boltzmann constant — see table 2.8 at http://www.fao.org/docrep/X0490E/x0490e0j.htm#TopOfPage
        $SBconstant = 4.903 * pow(10, -9);

        $Rnl = $SBconstant * (pow($this->deg2Kelvin($Tmax), 4) + pow(
                    $this->deg2Kelvin($Tmin),
                    4
                )) / 2 * (0.34 - 0.14 * sqrt($e_a)) * (1.35 * $Rs / $Rso
                - 0.35);

        return round($Rnl, 1);
    }

    /**
     * todo use Temperature->getTempKelvin().
     *
     * @param float $temp
     *
     * @return float
     */
    private function deg2Kelvin(float $temp): float
    {
        return $temp + 273.16;
    }

    private function netRadiation($Rns, $Rnl)
    {
        return round($Rns - $Rnl, 1);
    }
}
