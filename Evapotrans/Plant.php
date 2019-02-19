<?php

namespace Evapotrans;

class Plant
{
    private $plantName = '';

    // Tableau KC plantes sur http://www.fao.org/docrep/X0490E/x0490e0b.htm#crop coefficients
    // radish Kcini=0.7 Kcmid = 0.9 KCend=0.85 Maxhight=0.3

    /**
     * crop coefficient [dimensionless] by growStade.
     *
     * @var array
     */
    private $KcStade; // ['ini'=>, 'mid'=>, 'end'=>]

    /**
     * Plant constructor.
     *
     * @param string     $plantName
     * @param float|null $Kc
     */
    public function __construct(string $plantName, array $KcStade)
    {
        $this->plantName = $plantName;
        $this->KcStade = $KcStade;
    }

    /**
     * @return array
     */
    public function getKcStade(): array
    {
        return $this->KcStade;
    }

    /**
     * @return string|string
     */
    public function getPlantName()
    {
        return $this->plantName;
    }
}
