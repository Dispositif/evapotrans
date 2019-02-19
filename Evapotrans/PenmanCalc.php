<?php

namespace Evapotrans;

class PenmanCalc
{
    /**
     * latent heat of vaporization (l)
     * Simplified as constant (at 20°C) (Reference: Harrison 1963)
     * l = 2.501 - (2.361 x 10-3) x Temp.
     */
    const LATENT_HEAT_VAPORIZATION = 2.45;

    /**
     * Soil heat flux density (G), assumed to be zero (Allen et al.:1989).
     */
    const G_CONST = 0;

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
     * simplification of the ideal gas law, assuming 20°C for a standard atmosphere [7].
     *
     * @param int|null $altitude m
     *
     * @return float P (kPa)
     */
    private function atmosphericPressure(?int $altitude = 0)
    {
        return round(101.3 * pow((293 - 0.0065 * $altitude) / 293, 5.26), 1); // KPa
    }

    /**
     * @param MeteoData $data
     *
     * @return float ETo [mm.day-1]
     *
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
            (new RadiationCalc($data))->netRadiationFromMeteodata($data),
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
     * Equation [52].
     *
     * @param float $Tmin
     * @param float $Tmax
     * @param float $Ra   Ra MJ.m-2.day-1
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
     * by using a conversion factor equal to the inverse of the latent heat heat of vaporization.
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
     * mean saturation vapour pression (e_s).
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
     * saturation vapour pression (e_o) as function of T° [11].
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
     * ACTUAL VAPOUR PRESSION (e_a).
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
