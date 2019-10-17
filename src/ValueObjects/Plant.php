<?php

namespace Evapotrans\ValueObjects;

class Plant
{
    private $plantName = '';

    /**
     * crop coefficient [dimensionless] by growStade.
     * http://www.fao.org/docrep/X0490E/x0490e0b.htm#crop coefficients
     *
     * @var array
     */
    private $KcStade; // ['ini'=>, 'mid'=>, 'end'=>]

    /**
     * Plant constructor.
     *
     * @param string $plantName
     * @param array  $KcStade
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
