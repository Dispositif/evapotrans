<?php

namespace Evapotrans;

/**
 * Données culture
 * Class PlantData.
 */
class Area
{
    /**
     * @var Plant
     */
    private $plant;

    /**
     * @var string|null
     */
    private $growStade = null;

    // Fraction of partial wetting by irrigation/rain
    // TODO : (Kc = Fw * Kcini et Iw = I / Fw )
    private $fractionWetted;

    // 'journalier'; //=> +0.2kc
    private $frequence_eau = 'journalier';

    /**
     * Stress factor (Ks)
     * 1.1 = in the wind, isolated, few water reserve.
     *
     * @var float
     */
    private $stressFactor = 1.1;

    // Typical soil water characteristics for different soil types :
    // "Table 19" http://www.fao.org/docrep/X0490E/x0490e0c.htm#TopOfPage
    private $Kc = 0.9;

    /**
     * Area constructor.
     *
     * @param Plant $plant
     */
    public function __construct(Plant $plant)
    {
        $this->plant = $plant;
    }

    /**
     * @return string
     */
    public function getGrowStade(): string
    {
        return $this->growStade;
    }

    /**
     * @param string $growStade
     */
    public function setGrowStade(string $growStade): void
    {
        $this->growStade = $growStade;
    }

    /**
     * @return float
     */
    public function getKc()
    {
        // TODO
        if ($this->getGrowStade()
            && isset($this->getPlant()->getKcStade()[$this->getGrowStade()])
        ) {
            return $this->getPlant()->getKcStade()[$this->getGrowStade()];
        }

        return $this->Kc;
    }

    /**
     * @param float $Kc
     */
    public function setKc($Kc)
    {
        $this->Kc = $Kc;
    }

    /**
     * @return mixed
     */
    public function getPlant(): Plant
    {
        return $this->plant;
    }

    public function getFractionWetted(): float
    {
        return $this->fractionWetted;
    }

    public function setFractionWetted(float $fractionWetted)
    {
        $this->fractionWetted = $fractionWetted;
    }

    /**
     * @return string
     */
    public function getFrequenceEau()
    {
        return $this->frequence_eau;
    }

    /**
     * @param string $frequence_eau
     */
    public function setFrequenceEau($frequence_eau)
    {
        $this->frequence_eau = $frequence_eau;
    }

    /**
     * Ks.
     *
     * @return float
     */
    public function getStressFactor(): float
    {
        return $this->stressFactor;
    }

    /**
     * @param float $stressFactor
     */
    public function setStressFactor(float $stressFactor)
    {
        $this->stressFactor = $stressFactor;
    }
}
