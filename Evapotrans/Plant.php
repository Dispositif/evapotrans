<?php
/**
 * Created by phil
 * 2019-02-09 23:52
 */

namespace Evapotrans;


class Plant
{
    private $plantName = '';
    private $growStadeNames;

    // Tableau KC plantes sur http://www.fao.org/docrep/X0490E/x0490e0b.htm#crop coefficients
    // radis Kcini=0.7 Kcmid = 0.9 KCend=0.85 Maxhight=0.3

    // table at http://www.fao.org/docrep/x0490e/x0490e0b.htm
    private $Kc;
    private $KcIni;
    private $KcMid;
    private $KcEnd;

    /**
     * Plant constructor.
     *
     * @param string     $plantName
     * @param float|null $Kc
     */
    public function __construct(string $plantName, ?float $Kc)
    {
        $this->plantName = $plantName;
        $this->Kc = $Kc;
    }

    /**
     * @return string|string
     */
    public function getPlantName()
    {
        return $this->plantName;
    }

    /**
     * @param string|string $plantName
     */
    public function setPlantName($plantName)
    {
        $this->plantName = $plantName;
    }

    /**
     * @return array
     */
    public function getGrowStadeNames()
    {
        return $this->growStadeNames;
    }

    /**
     * @param array $growStadeNames
     */
    public function setGrowStadeNames($growStadeNames)
    {
        $this->growStadeNames = $growStadeNames;
    }


}
