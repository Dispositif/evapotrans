<?php

namespace Evapotrans;

/**
 * Données culture
 * Class PlantData
 *
 * @package Evapotranspiration
 */
class Area
{

    /**
     * @var Plant $plant
     */
    private $plant;

    private $growStadeId = 0;

    // Fraction arrosée : % terrain arrosé par irrigation/pluie (Kc = Fw * Kcini et Iw = I / Fw )
    private $fraction_wetted;

    // 'journalier'; //=> +0.2kc
    private $frequence_eau = 'journalier';

    // Facteur stress (water, wind, isolé sans végétation autour)
    // 1.1 = potager dans vent, isolé, peu de réserve eau
    private $Ks = 1.1;

    // Typical soil water characteristics for different soil types : "Table 19" http://www.fao.org/docrep/X0490E/x0490e0c.htm#TopOfPage
    private $Kc = 0.9;

    /**
     * @return float
     */
    public function getKc()
    {
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

    /**
     * @param mixed $plant
     */
    public function setPlant(Plant $plant)
    {
        $this->plant = $plant;
    }

    /**
     * @return int
     */
    public function getGrowStadeId()
    {
        return $this->growStadeId;
    }

    /**
     * @param int $growStadeId
     */
    public function setGrowStadeId($growStadeId)
    {
        $this->growStadeId = $growStadeId;
    }

    /**
     * @return mixed
     */
    public function getFractionWetted()
    {
        return $this->fraction_wetted;
    }

    /**
     * @param mixed $fraction_wetted
     */
    public function setFractionWetted($fraction_wetted)
    {
        $this->fraction_wetted = $fraction_wetted;
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
     * @return float
     */
    public function getKs()
    {
        return $this->Ks;
    }

    /**
     * @param float $Ks
     */
    public function setKs($Ks)
    {
        $this->Ks = $Ks;
    }





}
