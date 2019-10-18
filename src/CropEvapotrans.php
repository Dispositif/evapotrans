<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file
 */

namespace Evapotrans;

/*
    Evaluation selon crop (culture et plantes) : ETc
    http://www.fao.org/docrep/X0490E/x0490e0a.htm

    ETc = Kc * ETo
    ETo : lié à conditions climatiques (calculé avec Penman)
    Kc : crop coefficient, lié à plante/maturité/terrain
        * crop height
        * albedo
        * canopy resistance
        * evaporation from soil

    Kc augmente (>1) avec : plante petite (-herbe), serrées, aridité, vent / surface sol humide (kc=1.1)
    Kc baisse (<1) : avec espace terre découvert, control transpiration (citron), humidité, calme, / surface sol sèche (kc=0.1)

    stade : initial, crop development, mid-season and late season
     if the soil surface is dry, Kc = 0.5 corresponds to about 25-40% of the ground surface covered by vegetation due to the effects of shading and due to microscale transport of sensible heat from the soil into the vegetation.
                             A Kc = 0.7 often corresponds to about 40-60% ground cover.


    Avec stress (water/wind)
    ETc adj = Ks * Kc * ETo

    ET = I + P - RO - DP
    Irrigation (I) and rainfall (P) add water to the root zone.
    Part of I and P might be lost by surface runoff (RO) and by deep percolation (DP)


*/

/**
 * Due to differences in albedo, crop height, aerodynamic properties, and leaf and stomata properties, the evapotranspiration from full grown, well-watered crops differs from ETo.
 * http://www.fao.org/docrep/X0490E/x0490e0a.htm.
 * Class CropEvapoTranspiration.
 */
class CropEvapotrans
{
    /**
     * @var Area
     */
    private $area;

    /**
     * @var float|null
     */
    private $ETo = null;

    /**
     * refactor
     * crop coefficient [dimensionless]
     * http://www.fao.org/docrep/x0490e/x0490e0b.htm.
     *
     * @var float|null
     */
    private $Kc = null;

    /**
     * @var @var float|null
     */
    private $Kcb = null;

    /**
     * @var @var float|null
     */
    private $Ke = null;

    /**
     * CropEvapoTranspiration constructor.
     *
     * @param Area  $area
     * @param float $ETo
     */
    public function __construct(Area $area, float $ETo)
    {
        $this->area = $area;
        $this->ETo = $ETo;
        $this->Kc = $area->getKc(); // todo refactor
    }

    /**
     * @return float
     *
     * @throws Exception
     */
    public function calcETc()
    {
        if (false === $this->ETo) {
            throw new Exception('ETo not defined');
        }
        $Kc = $this->strategyKc();

        return round($Kc * $this->ETo, 1);
    }

    /**
     * @return float
     *
     * @throws Exception
     */
    private function strategyKc(): float
    {
        if ($this->Kc) {
            return $this->Kc;
        }
        // TODO
        // calc Kc with KcINI, KcMID, KcEND with climatic and crop ref
        // calc Kc as Kc = Kcb + Ke
        throw new Exception('no Kc determined');
    }

    public function setKcb(float $Kcb): void
    {
        $this->Kcb = $Kcb;
    }

    public function setKe(float $Ke): void
    {
        $this->Ke = $Ke;
    }

    // Degré jour de croissance (DJ)
    // DJ = (Tmax - Tmin) /2 - Tbase
    public function degre_jour($Tmin, $Tmax, $Tbase = 10, $Tmaxbase = 30)
    {
        $Tmin = max(
            $Tmin,
            ($Tbase + $Tmin) / 2
        ); // pondération car seulement T>Tbase compte
        $Tmax = min(
            $Tmax,
            ($Tmaxbase + $Tmax) / 2
        ); // pondération car seulement T<30°C compte
        $DJ = ($Tmax - $Tmin) / 2 - $Tbase;
        $DJ = max($DJ, 0);

        return $DJ;
    }
}
