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
     * Soil heat flux density (G)
     * [42] assumed to be zero (Allen et al.:1989) for day or < 10 day period
     */
    const G_CONST = 0;

    private $meteoData;

    private $Tmean;
    private $u2;
    private $e_s;
    private $e_a;
    private $delta;
    private $Rn;
    private $g;

    /**
     * @param MeteoData $data
     *
     * @return float ETo [mm.day-1]
     * @throws Exception
     */
    public function EToPenmanMonteith(MeteoData $data)
    {
        $this->Tmean = $data->getTmean();
        $this->u2 = $data->getWind2();
        $this->e_s = $this->meanSaturationVapourPression(
            $data->getTmin(),
            $data->getTmax()
        );
        $this->e_a = $this->actualVaporPressionStrategy($data);
        $this->delta = $this->slopeOfSaturationVapourPressureCurve(
            $data->getTmean()
        );
        $this->Rn = (new RadiationCalc($data))->netRadiationFromMeteodata();
        $this->g = $this->psychrometricConstant($data);

        $ETo = $this->penmanMonteithFormula();

        return round($ETo, 1);
    }

    /**
     * mean saturation vapour pression (e_s).
     *
     * @param float $Tmin Tmin
     * @param float $Tmax Tmax
     *
     * @return float|int
     */
    private function meanSaturationVapourPression(
        float $Tmin,
        float $Tmax
    ): float {
        $e_s = ($this->saturationVapourPression($Tmax)
                + $this->saturationVapourPression($Tmin)) / 2;

        return round($e_s, 2); // KPa
    }

    /**
     * saturation vapour pression (e_o) as function of T° [11].
     *
     * @param $temperature float °C
     *
     * @return float e_o (kPa)
     */
    private function saturationVapourPression(float $temperature)
    {
        $e = 0.6108 * exp(17.27 * $temperature / ($temperature + 237.3));

        return $e; // todo new Pression()
    }

    /**
     * ACTUAL VAPOUR PRESSION (e_a).
     *
     * @param MeteoData $meteoData
     *
     * @return float|int
     * @throws Exception
     */
    public function actualVaporPressionStrategy(MeteoData $meteoData)
    {
        // todo get/set MeteoData ?
        if (isset($meteoData->actualVaporPression)
            && $meteoData->actualVaporPression !== null
        ) {
            return $meteoData->actualVaporPression;
        }
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
            ); // todo move to Meteodata ?
        }

        if ($meteoData->getRHmax() and $meteoData->getTmin()) {
            return $this->saturationVapourPression($meteoData->getTmin())
                * $meteoData->getRHmax() / 100;
        }

        // For RHmean defined
        if ($meteoData->getRHmean() && $meteoData->getTmean()) {
            return $this->saturationVapourPression($meteoData->getTmean())
                * $meteoData->getRHmean() / 100;
        }

        throw new Exception('Impossible to determine actual vapor pression');
    }

    private function vapourPressionFromTdew(float $Tdew): float
    {
        return $this->saturationVapourPression($Tdew);
    }

    // ---------- ESTIMATION Humidité de l'air (e_a) ---------
    // http://www.fao.org/nr/water/docs/ReferenceManualETo.pdf

    private function vapourPressionFromRHmax($RHmax, $RHmin, $Tmax, $Tmin)
    {
        return ($this->saturationVapourPression($Tmin) * $RHmax / 100
                + $this->saturationVapourPression($Tmax) * $RHmin / 100)
            / 2; // kPa
    }

    // Actual vapour pression (ea) derived from Tdew

    /**
     * Slope of saturation vapour pressure curve (∆, delta)
     *
     * @param float $Tmean
     *
     * @return float|int
     */
    private function slopeOfSaturationVapourPressureCurve(float $Tmean)
    {
        $delta = (4098 * (0.6108 * exp(17.27 * $Tmean / ($Tmean + 237.3))))
            / pow(($Tmean + 237.3), 2);

        return round($delta, 3);
    }

    // todo inject Meteodata

    /**
     * Psychrometric constant (g) [8]
     * The specific heat at constant pressure is the amount of energy required
     * to increase the temperature of a unit mass of air by one degree at
     * constant pressure.
     *
     * @param MeteoData $meteoData
     *
     * @return float
     */
    private function psychrometricConstant(MeteoData $meteoData): float
    {
        $P = $meteoData->getPression();

        return round(0.665 * 0.001 * $P, 3);
    }

    /**
     * EVAPOTRANSPIRATION
     * FAO Penman-Monteith equation (mm.day-1)
     * for the hypothetical grass reference crop (0.12m ) with aerodynamic and
     * surface resistances predetermined :
     * http://www.fao.org/docrep/X0490E/x0490e06.htm requires air temperature,
     * humidity, radiation and wind speed data for daily, weekly, ten-day or
     * monthly calculations. T°, wind taken at 2 meters.
     *
     * @param float $Tmean
     * @param float $u2
     * @param float $e_s
     * @param float $e_a
     * @param float $delta
     * @param float $Rn
     * @param float $g
     *
     * @return float ETo mm.day-1
     */
    private function penmanMonteithFormula(): float
    {
        $G = self::G_CONST;
        $ETo = (0.408 * $this->delta * ($this->Rn - $G) + $this->g * 900
                / ($this->Tmean + 273) * $this->u2 * ($this->e_s - $this->e_a))
            / ($this->delta + $this->g * (1 + 0.34 * $this->u2));

        return $ETo; // mm.day-1
    }

    /**
     * alternative equation for daily ETo when weather data are missing
     * When solar radiation data, relative humidity data and/or wind speed data
     * are missing, they should be estimated using the procedures presented in
     * this section. Equation 52 has a tendency to underpredict under high wind
     * conditions (u2 > 3 m/s) and to overpredict under conditions of high
     * relative humidity.
     * Optimisation regional/annual ETo = a + b * ETo
     * Equation [52].
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
        $ET = 0.0023 * ($Tmoyen + 17.8) * pow($Tmax - $Tmin, 0.5)
            * $Ra_mmPerDay;

        return round($ET, 1); // mm day-1
    }

    /**
     * Conversion from energy values to equivalent evaporation
     * equivalent evaporation from Eq. [20]
     * by using a conversion factor equal to the inverse of the latent heat
     * heat of vaporization.
     *
     * @param float Ra MJ.m-2.day-1
     *
     * @return float Ra mm/day
     */
    private function equivalentEvaporation(float $Ra): float
    {
        return round(1 / self::LATENT_HEAT_VAPORIZATION * $Ra, 1);
    }
}
