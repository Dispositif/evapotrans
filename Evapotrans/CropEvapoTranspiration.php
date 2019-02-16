<?php
/**
 * Created by phil
 * 2019-02-16 12:58
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
 * http://www.fao.org/docrep/X0490E/x0490e0a.htm
 *
 * Class CropEvapoTranspiration
 *
 * @package Evapotrans
 */
class CropEvapoTranspiration
{
    /**
     * @var float|null
     */
    private $ETo = null;
    /**
     * crop coefficient [dimensionless]
     * http://www.fao.org/docrep/x0490e/x0490e0b.htm
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
     * @param float $ETo
     */
    public function __construct(float $ETo)
    {
        $this->ETo = $ETo;
    }

    /**
     * @return float
     * @throws \Exception
     */
    public function calcETo()
    {
        if( $this->ETo === false ) {
            throw new \Exception('ETo not defined');
        }
        $Kc = $this->strategyKc();

        return round($Kc * $this->ETo, 1);
    }

    private function strategyKc():float
    {
        if($this->Kc) {
            return $this->Kc;
        }
        // TODO
        // calc Kc with KcINI, KcMID, KcEND with climatic and crop ref
        // calc Kc as Kc = Kcb + Ke
        throw new \Exception('no Kc determined');
    }
    /**
     * @param mixed $ETo
     */
    public function setETo($ETo): void
    {
        $this->ETo = $ETo;
    }

    /**
     * @param bool $Kc
     */
    public function setKc(bool $Kc): void
    {
        $this->Kc = $Kc;
    }

    /**
     * @param bool $Kcb
     */
    public function setKcb(bool $Kcb): void
    {
        $this->Kcb = $Kcb;
    }

    /**
     * @param bool $Ke
     */
    public function setKe(bool $Ke): void
    {
        $this->Ke = $Ke;
    }


}
