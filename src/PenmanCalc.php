<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file.
 */

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
     * [42] assumed to be zero (Allen et al.:1989) for day or < 10 day period.
     */
    const G_CONST = 0;

    /**
     * @var MeteoData
     */
    private $meteoData;

    private $Tmean;

    private $u2;

    private $e_s;

    private $e_a;

    private $delta;

    private $Rn;

    private $g;

    /**
     * PenmanCalc constructor.
     *
     * @param $meteoData
     */
    public function __construct(MeteoData $meteoData)
    {
        $this->meteoData = $meteoData;
    }

    /**
     * @return float ETo [mm.day-1]
     *
     * @throws Exception
     */
    public function EToPenmanMonteith()
    {
        $this->Tmean = $this->meteoData->getTmean();
        $this->u2 = $this->meteoData->getWind2();
        $this->e_s = $this->meanSaturationVapourPression(
            $this->meteoData->getTmin(),
            $this->meteoData->getTmax()
        );
        $this->e_a = $this->actualVaporPressionStrategy();
        $this->delta = $this->slopeOfSaturationVapourPressureCurve(
            $this->meteoData->getTmean()
        );
        $this->Rn = (new RadiationCalc(
            $this->meteoData
        ))->netRadiationFromMeteodata();
        $this->g = $this->psychrometricConstant();

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
     * @return float|int
     *
     * @throws Exception
     */
    public function actualVaporPressionStrategy()
    {
        // move on get/set MeteoData ?
        if (isset($this->meteoData->actualVaporPression)
            && null !== $this->meteoData->actualVaporPression
        ) {
            return $this->meteoData->actualVaporPression;
        }
        // derived from Tdew (dewpoint temperature)
        if ($this->meteoData->getTdew()) {
            return $this->vapourPressionFromTdew($this->meteoData->getTdew());
        }

        // derived from relative humidity data
        if ($this->meteoData->getRHmax()
            && $this->meteoData->getRHmin()
            && $this->meteoData->getTmax()
            && $this->meteoData->getTmin()
        ) {
            return $this->vapourPressionFromRHmax(
                $this->meteoData->getRHmax(),
                $this->meteoData->getRHmin(),
                $this->meteoData->getTmax(),
                $this->meteoData->getTmin()
            ); // todo move to Meteodata ?
        }

        if ($this->meteoData->getRHmax() and $this->meteoData->getTmin()) {
            return $this->saturationVapourPression($this->meteoData->getTmin())
                * $this->meteoData->getRHmax() / 100;
        }

        // For RHmean defined
        if ($this->meteoData->getRHmean() && $this->meteoData->getTmean()) {
            return $this->saturationVapourPression($this->meteoData->getTmean())
                * $this->meteoData->getRHmean() / 100;
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
     * Slope of saturation vapour pressure curve (∆, delta).
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
     * @return float
     */
    private function psychrometricConstant(): float
    {
        $P = $this->meteoData->getPression();

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
     * @param float $Ra   Ra MJ.m-2.day-1
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
