<?php

namespace Evapotrans;

class Location
{
    private $lat;

    private $lon;

    private $altitude = 0;

    private $name = '';

    /**
     * *  for 'interior' locations, where land mass dominates and air masses are
     *  not strongly influenced by a large water body, kRs @ 0.16;
     * · for 'coastal' locations, situated on or adjacent to the coast of a large land mass
     *  and where air masses are influenced by a nearby water body, kRs @ 0.19.
     *
     * @var float
     */
    private $kRs = 0.16;

    /**
     * Location constructor.
     *
     * @param float       $lat
     * @param float       $lon
     * @param int         $altitude
     * @param string|null $name
     */
    public function __construct(float $lat, float $lon, int $altitude = null, ?string $name = null)
    {
        $this->lat = $lat;
        $this->lon = $lon;
        if ($altitude) {
            $this->altitude = $altitude;
        }
        if ($name) {
            $this->name = $name;
        }
    }

    /**
     * @return mixed
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @return float|int
     */
    public function getLat_radian()
    {
        return pi() / 180 * $this->getLat();
    }

    /**
     * @param mixed $lat
     */
    public function setLat(float $lat)
    {
        $this->lat = $lat;
    }

    /**
     * @return mixed
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * @param mixed $lon
     */
    public function setLon($lon)
    {
        $this->lon = $lon;
    }

    /**
     * @return int
     */
    public function getAltitude()
    {
        return $this->altitude;
    }

    /**
     * @param int $altitude
     */
    public function setAltitude(int $altitude)
    {
        $this->altitude = $altitude;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getKRs(): ?float
    {
        return $this->kRs;
    }

    /**
     * @param float $kRs
     */
    public function setKRs(float $kRs): void
    {
        $this->kRs = $kRs;
    }
}
