<?php

namespace Evapotrans;

class EvapotransCalc
{
    /**
     * latent heat of vaporization (l)
     * Simplified as constant (at 20°C) (Reference: Harrison 1963)
     * l = 2.501 - (2.361 x 10-3) x Temp
     */
    const LATENT_HEAT_VAPORIZATION = 2.45;

    /**
     * Soil heat flux density (G), assumed to be zero (Allen et al.:1989)
     */
    const G_CONST = 0;

    // $albedo (α) : albedo or canopy reflection coefficient for the reference crop
    // albedo (a) (végétation 0.20-0.25) pour herbe verte = 0.23
    // todo move on Area
    private $albedo = 0.23;


    /**
     * Psychrometric constant (g) [8]
     * The specific heat at constant pressure is the amount of energy required to increase the temperature of a unit mass of air by one degree at constant pressure.
     *
     * @param int|null $altitude
     *
     * @return float
     */
    public function psychrometricConstant(?int $altitude = 0): float
    {
        $P = $this->atmosphericPressure($altitude);

        return round(0.665 * 0.001 * $P, 3);
    }

    /**
     * Atmospheric pressure (P) [7]
     * simplification of the ideal gas law, assuming 20°C for a standard atmosphere [7]
     *
     * @param int|null $altitude m
     *
     * @return float P (kPa)
     */
    private function atmosphericPressure(?int $altitude = 0)
    {
        return round(101.3 * pow((293 - 0.0065 * $altitude) / 293, 5.26), 1); // KPa
    }

    // todo Refactor RaStrategy

    /**
     * @param MeteoData $data
     *
     * @return float
     * @throws Exception
     */
    public function solarRadiationStrategyFromMeteodata(MeteoData $data)
    {
        // Determination of solar radiation from measured duration of sunshine
        if ($data->actualSunshineHours) {
            $n = $data->actualSunshineHours;
            $a_s = $data->a_s ?? null;
            $b_s = $data->b_s ?? null;

            if ($data->actualSunshineHours) {
                $N = $data->actualSunshineHours;
            }else {
                $N = (new MeteoCalculation())->daylightHours(
                    $data->getDaysOfYear(),
                    $data->getLocation()->getLat()
                );
            }

            if ($data->Ra) {
                $Ra = $data->Ra;
            }else {
                $Ra = (new MeteoCalculation())->extraterrestrialRadiationDailyPeriod(
                    $data->getDaysOfYear(),
                    $data->getLocation()->getLat()
                );
            }

            $Rs = $this->solarRadiationFromDurationSunshineAndRa($Ra, $n, $N, $a_s, $b_s);

            return $Rs;
        }

        //  Determination of solar radiation from temperature data
        //The temperature difference method is recommended for locations where it is not appropriate to import radiation data from a regional station, either because homogeneous climate conditions do not occur, or because data for the region are lacking. For island conditions, the methodology of Equation 50 is not appropriate due to moderating effects of the surrounding water body.
        if ($data->getTmax() && $data->getTmin()) {
            if ($data->Ra) {
                $Ra = $data->Ra;
            }else {
                $Ra = (new MeteoCalculation())->extraterrestrialRadiationDailyPeriod(
                    $data->getDaysOfYear(),
                    $data->getLocation()->getLat()
                );
            }
            $kRs = ($data->getLocation()->getKRs()) ?? 0.18;
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
     * Solar Radiation data derived from air temperature differences
     * Equation [50]
     * The temperature difference method is recommended for locations where it is not appropriate to import radiation data from a regional station, either because homogeneous climate conditions do not occur, or because data for the region are lacking. For island conditions, the methodology of Equation 50 is not appropriate due to moderating effects of the surrounding water body.
     *  Caution is required when daily computations of ETo are needed.
     *  for 'interior' locations, where land mass dominates and air masses are not strongly influenced by a large water body, kRs @ 0.16;
     * · for 'coastal' locations, situated on or adjacent to the coast of a large land mass and where air masses are influenced by a nearby water body, kRs @ 0.19.
     *
     * @param float $Ra
     * @param float $Tmin
     * @param float $Tmax
     * @param float $kRs adjustment coefficient
     *
     * @return float
     */
    public function solarRadiationFromTemperatures(float $Ra, float $Tmin, float $Tmax, ?float $kRs = 0.18): float
    {
        $Rs = $kRs * sqrt(abs($Tmax - $Tmin)) * $Ra;

        return round($Rs, 1);
    }

    /**
     * Rs
     * Angstrom formula [35] which relates solar radiation to extraterrestrial radiation and relative sunshine duration
     * Rs solar or shortwave radiation [MJ m-2 day-1],
     * n actual duration of sunshine [hour],
     * N maximum possible duration of sunshine or daylight hours [hour],
     * n/N relative sunshine duration [-],
     * Ra extraterrestrial radiation [MJ m-2 day-1],
     * a_s regression constant, expressing the fraction of extraterrestrial radiation reaching the earth on overcast days (n =0),
     * as+bs fraction of extraterrestrial radiation reaching the earth on clear days (n = N).
     *
     * @param float      $Ra
     * @param float      $n
     * @param float      $N
     * @param float|null $a_s
     * @param float|null $b_s
     *
     * @return float
     */
    public function solarRadiationFromDurationSunshineAndRa(
        float $Ra,
        float $n,
        float $N,
        ?float $a_s = 0.25,
        ?float $b_s = 0.50
    ): float {
        // TODO : strategy for using a parameter "$n/$N coefficient"
        $Rs = ($a_s + $b_s * $n / $N) * $Ra;

        return round($Rs, 1); // MJ m-2 day-1
    }

    // Net solar or net shortwave radiation (Rns)
    public function netSolarRadiation($Rs, $albedo)
    {
        return (1 - $albedo) * $Rs;
    }

    /**
     * @return float
     */
    public function getAlbedo()
    {
        return $this->albedo;
    }

    /**
     * @param float $albedo
     */
    public function setAlbedo($albedo)
    {
        $this->albedo = $albedo;
    } // grass // Increased reflectance (higher albedo)


    // Net longwave radiation (Rnl)
    // TODO : option (TmaxKelvin^4+TminKelvin^4)/2 remplacé par TmoyenKelvin^4
    /**
     * Net longwave radiation (Rnl)
     * Stefan-Boltzmann law [39]
     *
     * @param $e_a
     * @param $Tmax
     * @param $Tmin
     * @param $Rs
     * @param $Rso
     *
     * @return float|int
     */
    public function netLongwaveRadiation($e_a, $Tmax, $Tmin, $Rs, $Rso)
    {
        // Stefan-Boltzmann constant — see table 2.8 at http://www.fao.org/docrep/X0490E/x0490e0j.htm#TopOfPage
        $SBconstant = 4.903 * pow(10, -9);

        $Rnl = $SBconstant * (pow($this->deg2Kelvin($Tmax), 4) + pow($this->deg2Kelvin($Tmin), 4)) / 2 * (0.34 - 0.14
                * sqrt($e_a)) * (1.35 * $Rs / $Rso - 0.35);

        return round($Rnl, 1);
    }

    /**
     * @param float $temp
     *
     * @return float
     */
    private function deg2Kelvin(float $temp): float
    {
        return $temp + 273.16;
    }

    // Net radiation (Rn)
    public function netRadiation($Rns, $Rnl)
    {
        return $Rns - $Rnl;
    }

    /**
     * Rn from data
     *
     * @param MeteoData $meteoData
     *
     * @return float Rn
     * @throws Exception
     */
    public function netRadiationFromMeteodata(MeteoData $meteoData): float
    {
        if( !$meteoData->getActualSunnyHours() ) {
            throw new Exception('Actual sunny hours not defined');
        }
        $n = $meteoData->getActualSunnyHours();

        $calc = new MeteoCalculation();
        // todo move on MeteoData
        $N = $calc->daylightHours($meteoData->getDaysOfYear(), $meteoData->getLocation()->getLat());

        // TODO RaByMeteoData strategy
        $Ra = $calc->extraterrestrialRadiationDailyPeriod($meteoData->getDaysOfYear(), $meteoData->getLocation()->getLat());

        $Rs = $this->solarRadiationFromDurationSunshineAndRa($Ra, $n, $N);

        $e_a = $this->actualVaporPressionStrategy($meteoData);

        $a_s = null; // move a_s on MeteoData ?
        $b_s = null; //
        $Rso = $this->clearSkySolarRadiation($Ra, $meteoData->getLocation()->getAltitude(), $a_s, $b_s);
        $Rns = $this->netSolarRadiation($Rs, $this->getAlbedo());
        $Rnl = $this->netLongwaveRadiation($e_a, $meteoData->getTmax(), $meteoData->getTmin(), $Rs, $Rso);

        return $this->netRadiation($Rns, $Rnl);
    }

    /**
     * @param MeteoData $data
     *
     * @return float ETo [mm.day-1]
     * @throws Exception
     */
    public function EToPenmanMonteith(MeteoData $data)
    {
        return $this->penmanMonteithFormula(
            $data->getTmean(),
            $data->getWind2(),
            $this->meanSaturationVapourPression($data->getTmin(), $data->getTmax()),
            $this->actualVaporPressionStrategy($data),
            $this->slopeOfSaturationVapourPressureCurve($data->getTmean()),
            $this->netRadiationFromMeteodata($data),
            $this->psychrometricConstant($data->getLocation()->getAltitude())
        );
    }

    /**
     * EVAPOTRANSPIRATION
     * FAO Penman-Monteith equation (mm.day-1)
     * for the hypothetical grass reference crop (0.12m ) with aerodynamic and surface resistances predetermined :
     * http://www.fao.org/docrep/X0490E/x0490e06.htm
     * requires air temperature, humidity, radiation and wind speed data for daily, weekly, ten-day or monthly calculations.
     * T°, wind taken at 2 meters.
     *
     * @param float $Tmoyen
     * @param float $u2
     * @param float $e_s
     * @param float $e_a
     * @param float $delta
     * @param float $Rn
     * @param float $g
     *
     * @return float ETo mm.day-1
     */
    public function penmanMonteithFormula(
        float $Tmoyen,
        float $u2,
        float $e_s,
        float $e_a,
        float $delta,
        float $Rn,
        float $g
    ): float {
        $G = self::G_CONST;
        $ETo = (0.408 * $delta * ($Rn - $G) + $g * 900 / ($Tmoyen + 273) * $u2 * ($e_s - $e_a)) / ($delta + $g * (1
                    + 0.34 * $u2));

        return round($ETo, 1); // mm.day-1
    }

    /**
     * alternative equation for daily ETo when weather data are missing
     * When solar radiation data, relative humidity data and/or wind speed data are missing, they should be estimated using the procedures presented in this section.
     * Equation 52 has a tendency to underpredict under high wind conditions (u2 > 3 m/s) and to overpredict under conditions of high relative humidity.
     * Optimisation regionale/annuelle par ETo = a + b * ETo
     * Equation [52]
     *
     * @param float $Tmin
     * @param float $Tmax
     * @param float $Ra Ra MJ.m-2.day-1
     *
     * @return float mm/day
     */
    public function simplisticETo(float $Tmin, float $Tmax, float $Ra): float
    {
        $Ra_mmPerDay = $this->equivalentEvaporation($Ra);
        // Avec $Ra équivalent en mm/day !!
        $Tmoyen = ($Tmax + $Tmin) / 2;
        $ET = 0.0023 * ($Tmoyen + 17.8) * pow($Tmax - $Tmin, 0.5) * $Ra_mmPerDay;

        return round($ET, 1); // mm day-1
    }

    /**
     * Conversion from energy values to equivalent evaporation
     * equivalent evaporation from Eq. [20]
     * by using a conversion factor equal to the inverse of the latent heat heat of vaporization
     *
     * @param float Ra MJ.m-2.day-1
     *
     * @return float Ra mm/day
     */
    public function equivalentEvaporation(float $Ra): float
    {
        return round(1 / self::LATENT_HEAT_VAPORIZATION * $Ra, 1);
    }


    // ---------- ESTIMATION Humidité de l'air (e_a) ---------
    // http://www.fao.org/nr/water/docs/ReferenceManualETo.pdf

    /**
     * mean saturation vapour pression (e_s)
     *
     * @param float $Tmin Tmin
     * @param float $Tmax Tmax
     *
     * @return float|int
     */
    public function meanSaturationVapourPression(float $Tmin, float $Tmax): float
    {
        $e_s = ($this->saturationVapourPression($Tmax) + $this->saturationVapourPression($Tmin)) / 2;

        return round($e_s, 2); // KPa
    }

    // Actual vapour pression (ea) derived from Tdew (dewpoint temperature, température point rosée)
    public function vapourPressionFromTdew(float $Tdew): float
    {
        return $this->saturationVapourPression($Tdew);
    }

    // todo inject Meteodata
    public function vapourPressionFromRHmax($RHmax, $RHmin, $Tmax, $Tmin)
    {
        return ($this->saturationVapourPression($Tmin) * $RHmax / 100 + $this->saturationVapourPression($Tmax) * $RHmin
                / 100) / 2; // kPa
    }

    /**
     * saturation vapour pression (e_o) as function of T° [11]
     *
     * @param $temperature float °C
     *
     * @return float e_o (kPa)
     */
    public function saturationVapourPression(float $temperature)
    {
        $e = 0.6108 * exp(17.27 * $temperature / ($temperature + 237.3));

        return $e; // todo new Pression()
    }


    // Slope of saturation vapour pressure curve (∆, delta)
    // T = Tmoyen ? à vérifier
    /**
     * @param float $Tmoyen
     *
     * @return float|int
     */
    public function slopeOfSaturationVapourPressureCurve(float $Tmoyen)
    {
        $delta = (4098 * (0.6108 * exp(17.27 * $Tmoyen / ($Tmoyen + 237.3)))) / pow(($Tmoyen + 237.3), 2);

        return round($delta, 3);
    }

    public function delta(float $T)
    {
        return $this->slopeOfSaturationVapourPressureCurve($T);
    }

    /**
     * Clear-sky solar radiation (Rso)
     * required for computing net longwave radiation
     *
     * @param float $Ra
     * @param int   $altitude
     * @param null  $a_s
     * @param null  $b_s
     *
     * @return float Rso [MJ m-2 day-1]
     */
    public function clearSkySolarRadiation(float $Ra, int $altitude, $a_s = null, $b_s = null): float
    {
        if ($a_s && $b_s) {
            $Rso = ($a_s + $b_s) * $Ra;  // [36]
        }else {
            $Rso = (0.75 + 2 * pow(10, -5) * $altitude) * $Ra; // [37]
        }

        return round($Rso, 1);
    }

    /**
     * ACTUAL VAPOUR PRESSION (e_a)
     *
     * @param MeteoData $meteoData
     *
     * @return float|int
     */
    public function actualVaporPressionStrategy(MeteoData $meteoData)
    {
        // derived from Tdew (dewpoint temperature)
        if ($meteoData->getTdew()) {
            return $this->vapourPressionFromTdew($meteoData->getTdew());
        }

        // derived from relative humidity data
        if ($meteoData->getRHmax()
            && $meteoData->getRHmin()
            && $meteoData->getTmax()
            && $meteoData->getTmin()
        ) {
            return $this->vapourPressionFromRHmax(
                $meteoData->getRHmax(),
                $meteoData->getRHmin(),
                $meteoData->getTmax(),
                $meteoData->getTmin()
            ); // todo inject Meteodata
        }

        if ($meteoData->getRHmax() and $meteoData->getTmin()) {
            return $this->saturationVapourPression($meteoData->getTmin()) * $meteoData->getRHmax() / 100;
        }

        // For RHmean defined
        if ($meteoData->getRHmean() && $meteoData->getTmean()) {
            return $this->saturationVapourPression($meteoData->getTmean()) * $meteoData->getRHmean() / 100;
        }

        throw new Exception('Impossible to determine actual vapor pression');
    }


}
