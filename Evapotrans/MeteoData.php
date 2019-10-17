<?php

/** @noinspection ALL */

namespace Evapotrans;

use Evapotrans\ValueObjects\Temperature;
use Evapotrans\ValueObjects\Wind2m;

class MeteoData
{
    use CalcTrait;
    const DEFAULT_PRESSION = 100.4;

    /**
     * Ra extraterrestrial radiation [MJ m-2 day-1]
     */
    public $Ra;
    /**
     * a_s regression constant, expressing the fraction of extraterrestrial
     * radiation reaching the earth on overcast days (n =0),
     * todo
     *
     * @var float
     */
    public $a_s;
    /**
     * as+bs fraction of extraterrestrial radiation reaching the earth on clear
     * days (n = N).
     */
    public $b_s;

    /**
     * @var Location
     */
    private $location;

    // n (hours)
    /**
     * @var \DateTimeImmutable
     */
    private $date;

    // N (hours)
    /**
     * @var float Tmax
     */
    private $Tmax;
    /**
     * @var float
     */
    private $Tmean;
    /**
     * @var float
     */
    private $Tmin;

    /**
     * theorical maximal hours of light (N)
     * aka "daylightHours"
     *
     * @var float|null hours
     */
    private $maxDaylightHours = null;

    /**
     * actual duration of sunshine in the day (recorded) (n)
     * Effective hours of sun
     * Opposed to "Daylight hours" = maximum possible duration of sunshine (N).
     *
     * @var float [hours/day]
     */
    private $actualSunnyHours;

    /**
     * @var float millimetres
     */
    private $precipitation;
    /**
     * Where no wind data are available within the region, a value of 2 m/s can
     * be used as a temporary estimate. This value is the average over 2000
     * weather stations around the globe. In general, wind speed at 2 m, u2,
     * should be limited to about u2 ³ 0.5 m/s when used in the ETo equation
     * (Equation 6). This is necessary to account for the effects of boundary
     * layer instability and buoyancy of air in promoting exchange of vapour at
     * the surface when air is calm. This effect occurs when the wind speed is
     * small and buoyancy of warm air induces air exchange at the surface.
     * Limiting u2 ³ 0.5 m/s in the ETo equation improves the estimation
     * accuracy under the conditions of very low wind speed. todo max 0.5 +
     * value "estimated/minimized"
     *
     * @var float Wind speed at 2meter in m.s-1
     */
    private $wind2 = 2.0; // wind 2meter m.s-1

    private $wind2origin = 'default';

    /**
     * Dewpoint temperature
     */
    private $Tdew;

    /**
     * Pression
     * at sea level ?
     *
     * @var float KPa
     */
    private $pression; // TODO getters : propriété ou calcul pression/altitude

    /**
     * Maximum relative humidity (%).
     *
     * @var float
     */
    private $RHmax;

    /**
     * Minimum relative humidity (%).
     *
     * @var float
     */
    private $RHmin;

    /**
     * Mean relative humidity (%).
     *
     * @var float RHmean
     */
    private $RHmean;
    public $actualVaporPression;

    /**
     * MeteoData constructor.
     *
     * @param Location  $location
     * @param \DateTime $date
     */
    public function __construct(Location $location, \DateTimeInterface $date)
    {
        $this->location = clone $location;

        if ($date instanceof \DateTimeImmutable) {
            $this->date = $date;
        }elseif ($date instanceof \DateTime) {
            $this->date = \DateTimeImmutable::createFromMutable($date);
        }else {
            throw new Exception('date instance error');
        }
    }

    /**
     * @return float N
     */
    public function getMaxDaylightHours(): float
    {
        if ($this->maxDaylightHours) {
            return $this->maxDaylightHours;
        }

        return $this->calcDaylightHours(
            $this->getDaysOfYear(),
            $this->getLocation()->getLatitude()
        );
    }

    /**
     * Daylight hours or maximum possible duration of sunshine (N).
     *
     * @param int $dayOfTheYear
     * @param     $latitude
     *
     * @return float
     */
    private function calcDaylightHours(
        int $dayOfTheYear,
        float $latitude
    ): float {
        $ws = $this->sunsetHourAngle($dayOfTheYear, $latitude);
        $N = 24 / pi() * $ws;

        return round($N, 1);
    }

    /**
     * Number of the day in the year
     * 1-01=>1, 27 mars => 85.
     *
     * @return int
     */
    public function getDaysOfYear(): int
    {
        return 1 + $this->date->format('z');
    }

    /**
     * @return mixed
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * @return float|null
     */
    public function getRHmean(): ?float
    {
        return $this->RHmean;
    }

    /**
     * @param float $RHmean
     */
    public function setRHmean(float $RHmean): void
    {
        $this->RHmean = $RHmean;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getTmax(): ?float
    {
        return $this->Tmax;
    }

    /**
     * @param mixed $Tmax
     *
     * @return MeteoData
     */
    public function setTmax(Temperature $Tmax): self
    {
        $this->Tmax = $Tmax->getValue();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTmean(): ?float
    {
        if (isset($this->Tmean)) {
            return $this->Tmean;
        }

        if (isset($this->Tmin) && isset($this->Tmax)) {
            return round(($this->Tmax + $this->Tmin) / 2, 1);
        }

        return null; // exception ?
    }

    /**
     * @param mixed $Tmean
     *
     * @return MeteoData
     */
    public function setTmean(Temperature $Tmean): self
    {
        $this->Tmean = $Tmean->getValue();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTmin(): ?float
    {
        return $this->Tmin;
    }

    /**
     * @param mixed $Tmin
     *
     * @return MeteoData
     */
    public function setTmin(Temperature $Tmin): self
    {
        $this->Tmin = $Tmin->getValue();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getActualSunnyHours(): ?float
    {
        return $this->actualSunnyHours;
    }

    /**
     * @param mixed $actualSunnyHours hours
     *
     * @return MeteoData
     */
    public function setActualSunnyHours(float $actualSunnyHours): self
    {
        $this->actualSunnyHours = min(24, max(0, $actualSunnyHours));

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrecipitation()
    {
        return $this->precipitation;
    }

    /**
     * @param mixed $precipitation
     *
     * @return MeteoData
     */
    public function setPrecipitation(float $precipitation): self
    {
        $this->precipitation = max(0, $precipitation);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWind2(): ?float
    {
        return $this->wind2;
    }

    /**
     * @param mixed  $wind2
     * @param string $origin
     *
     * @return MeteoData
     */
    public function setWind2(Wind2m $wind2, $origin = 'set'): self
    {
        $this->wind2 = $wind2->getValue();
        $this->wind2origin = $origin;

        return $this;
    }

    /**
     * @return float
     */
    public function getWind2origin(): float
    {
        return $this->wind2origin;
    }

    /**
     * @param string $wind2origin
     *
     * @return MeteoData
     */
    public function setWind2origin($wind2origin): self
    {
        $this->wind2origin = $wind2origin;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTdew(): ?float
    {
        return $this->Tdew;
    }

    /**
     * @param mixed $Tdew
     *
     * @return MeteoData
     */
    public function setTdew($Tdew): self
    {
        $this->Tdew = $Tdew;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRHmax(): ?float
    {
        return $this->RHmax;
    }

    /**
     * @param mixed $RHmax
     *
     * @return MeteoData
     * @throws Exception
     * @throws Exception
     */
    public function setRHmax(float $RHmax): self
    {
        if (self::isValidRH($RHmax)) {
            $this->RHmax = $RHmax;

            return $this;
        }

        throw new Exception('RHmax error');
    }

    /**
     * @param float $RH
     *
     * @return bool
     */
    private static function isValidRH(float $RH): bool
    {
        return $RH <= 1 && $RH > 0;
    }

    /**
     * @return float|null
     */
    public function getRHmin(): ?float
    {
        return $this->RHmin;
    }

    /**
     * @param mixed $RHmin
     *
     * @return MeteoData
     * @throws Exception
     * @throws Exception
     */
    public function setRHmin(float $RHmin): self
    {
        if (self::isValidRH($RHmin)) {
            $this->RHmin = $RHmin;

            return $this;
        }

        throw new Exception('RHmin error');
    }

    /**
     * @return float
     */
    public function getPression(): float
    {
        if ($this->pression) {
            return $this->pression;
        }
        $alt = $this->location->getAltitude();

        return $this->atmosphericPressureByAltitude($alt);
    }

    /**
     * @param float $pression
     *
     * @return MeteoData
     */
    public function setPression(float $pression): self
    {
        $this->pression = $pression;

        return $this;
    }

    /**
     * Atmospheric pressure (P) [7]
     * simplification of the ideal gas law, assuming 20°C for a standard
     * atmosphere.
     *
     * @param int|null $altitude m
     *
     * @return float P (kPa)
     */
    private function atmosphericPressureByAltitude(?int $altitude = 0): float
    {
        return round(101.3 * pow((293 - 0.0065 * $altitude) / 293, 5.26), 1);
    }

    /**
     * @param float $sunshineHours MaxHours
     */
    private function setMaxDaylightHours(float $sunshineHours): void
    {
        $this->maxDaylightHours = $sunshineHours;
    }
}
