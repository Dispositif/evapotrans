<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file.
 */

namespace Evapotrans;

/**
 * Class Location
 * immutable.
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
     * *  for 'interior' locations, where land mass dominates and air masses
     * are
     *  not strongly influenced by a large water body, kRs @ 0.16;
     * · for 'coastal' locations, situated on or adjacent to the coast of a
     * large land mass and where air masses are influenced by a nearby water
     * body, kRs @ 0.19.
     *
     * @var float
     */
    private $kRs = 0.16;

    /**
     * Location constructor.
     *
     * @param float $latitude
     * @param float $longitude
     * @param int   $altitude
     */
    public function __construct(
        float $latitude,
        float $longitude,
        int $altitude = null
    ) {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        if ($altitude) {
            $this->altitude = $altitude;
        }
    }

    /**
     * @return float|int
     */
    public function getLatitudeRadian(): float
    {
        return pi() / 180 * $this->getLatitude();
    }

    /**
     * @return mixed
     */
    public function getLatitude(): float
    {
        return $this->latitude;
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
     * @return mixed
     */
    public function getKRs(): ?float
    {
        return $this->kRs;
    }

    /**
     * @param float $kRs
     *
     * @throws Exception
     */
    public function setKRs(float $kRs): void
    {
        if ($kRs >= 0.16 && $kRs <= 0.19) {
            $this->kRs = $kRs;

            return;
        }

        throw new Exception('kRs value not in range');
    }
}
