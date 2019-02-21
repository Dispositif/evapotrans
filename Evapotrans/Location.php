<?php

namespace Evapotrans;

/**
 * Class Location
 */
class Location
{
    /**
     * @var float
     */
    private $latitude;
    /**
     * @var float
     */
    private $longitude;
    /**
     * @var int
     */
    private $altitude = 0;
    /**
     * @var string|null
     */
    private $name = null;

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
     * @param float $latitude
     * @param float $longitude
     * @param int $altitude
     * @param string|null $name
     */
    public function __construct(
        float $latitude,
        float $longitude,
        int $altitude = null,
        ?string $name = null
    ) {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
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
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @return float|int
     */
    public function getLat_radian(): float
    {
        return pi() / 180 * $this->getLatitude();
    }

    /**
     * @return mixed
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @return int
     */
    public function getAltitude(): int
    {
        return $this->altitude;
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
        if ($kRs >= 0.16 && $kRs <= 0.19) {
            $this->kRs = $kRs;
        }
    }
}
