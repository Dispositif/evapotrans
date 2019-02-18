<?php

namespace Evapotrans;

use Evapotrans\ValueObjects\Unit;
use Evapotrans\ValueObjects\Wind;

date_default_timezone_set('Europe/Paris');
error_reporting(E_ALL); //^ E_NOTICE);

spl_autoload_register(
    function ($class) {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
        if (file_exists($file)) {
            require $file;

            return true;
        }

        return false;
    }
);

function dump($a, $b = null)
{
    echo '<pre style="background:black;color:lawngreen;font-size:0.8em;">';
    print_r($a);
    if ($b) {
        print_r($b);
    }
    echo '</pre>';
}


$calc = new MeteoCalculation();

$location = new Location(
    43.29504, 5.3865, 35, 'Marseille'
);

$meteoData = new MeteoData($location, new \DateTime('2019-02-15'));
$meteoData->setTmin(2.7);
$meteoData->setTmax(16.1);
$meteoData->setSunnyHours(7.2); // moyenne Mars à Marseille = 7.2
$wind = (new Wind(20, new Unit('km/h'), 2));
$meteoData->setWind2($wind->getSpeed2meters());

$u2 = $wind->getSpeed2meters();
$Tmin = $meteoData->getTmin();
$Tmax = $meteoData->getTmax();


// HUMIDITE

// Température dewpoint (point rosée) : Facultatif si RHmax/min ou RHmoyen
$meteoData->setTdew(9);

// RHmax, RHmin facultatif si Tdew
$meteoData->setRHmax(0.90);
$meteoData->setRHmin(0.38);


// Pression niveau mer : 1004 hPa : Facultatif, sinon calculé
$meteoData->setPression(1031);


$Tmoyen = $meteoData->getTmoy();


$ETcalc = new ETcalcStrategy();

$e_s = $ETcalc->meanSaturationVapourPression($Tmin, $Tmax);
$delta = $ETcalc->slopeOfSaturationVapourPressureCurve($Tmoyen);

/* ACTUAL VAPOUR PRESSION */

// Actual vapour pression (ea) derived from Tdew (dewpoint temperature, température point rosée)
// TODO e_a strategy
if ($meteoData->getTdew()) {
    $e_a = $ETcalc->vapourPressionFromTdew($meteoData->getTdew());
}


// Actual vapour pressure (ea) derived from relative humidity data
// Si RHmax (%) and RHmi (%) connues

elseif ($RHmax == true and $RHmin == true and $Tmin == true and $Tmax == true) {

    $e_a = $this->vapourPressionFromRHmax($RHmax, $RHmin, $Tmax, $Tmin);
}

/* todo continuer strategy vapourPression
// Si RHmax connue
elseif( $RHmax == true and $Tmin == true ) {
	$e_a = e_o( $Tmin) * $RHmax / 100;
	$res['e_a'] = $e_a;
}

// Si For RHmean (RHmoy) connue
elseif ( $RHmoy == true and $Tmoyen == true ) {
	$e_a = e_o( $Tmoyen ) * $RHmoy / 100;
	$res['e_a'] = $e_a;
}
*/

// --------- ESTIMATION RADIATION --------------

// albedo (a) (végétation 0.20-0.25) pour herbe verte = 0.23



$dayOfTheYear = $calc->daysOfYear($meteoData->getDate());

$N = $calc->daylightHours($dayOfTheYear, $location->getLat());

// TODO RaByMeteoData strategy
$Ra = $calc->extraterrestrialRadiationDailyPeriod($dayOfTheYear, $location->getLat());

// TODO calcul $n
$n = 7.3; //moyenne Mars pour Marseille
$Rs = $ETcalc->solarRadiationFromDurationSunshineAndRa($Ra, $n, $N);

$a_s = null;
$b_s = null;
$Rso = $ETcalc->clearSkySolarRadiation($Ra, $location->getAltitude(), $a_s, $b_s);
$Rns = $ETcalc->netSolarRadiation($Rs, $ETcalc->getAlbedo());
$Rnl = $ETcalc->netLongwaveRadiation($e_a, $Tmax, $Tmin, $Rs, $Rso);
$Rn = $ETcalc->netRadiation($Rns, $Rnl);
$g = $ETcalc->psychrometricConstant($location->getAltitude());

$ETo = $ETcalc->penmanMonteithFormula($Tmoyen, $u2, $e_s, $e_a, $delta, $Rn, $g);
dump("ETo = $ETo mm/day");


// ----------------------
// Formule simplifiée
$simplisticETo = $ETcalc->simplisticETo($Tmin, $Tmax, $Ra);
$simplistic_error = round(abs($simplisticETo - $ETo) * 100 / $ETo);
dump("Simplistic ETo = $simplisticETo error $simplistic_error %");



// DONNEES CULTURE -----------------
die();

$area = new Area();

// radis Kcini=0.7 Kcmid = 0.9 KCend=0.85 Maxhight=0.3
// Tableau KC plantes sur http://www.fao.org/docrep/X0490E/x0490e0b.htm#crop coefficients
// , ['initial', 'developpment', 'mid-season', 'late-season']
// radis KcIni=0.7, KcMid = 0.9, KcEnd = 0.85

$radis = new Plant('Radis', 0.9);
$area->setPlant($radis);
$area->setGrowStadeId(1);

// https://ucanr.edu/sites/UrbanHort/Water_Use_of_Turfgrass_and_Landscape_Plant_Materials/Turfgrass_Crop_Coefficients_Kc/
$turfgrassesCaliforniaCoolSeason = new Plant('california', 0.80);
$turfgrassesCaliforniaWarmSeason = new Plant('california', 0.60);

// Partial wetting by irragation
// Fraction arrosée : % terrain arrosé par irrigation/pluie TODO : (Kc = Fw * Kcini et Iw = I / Fw )
$area->setFractionWetted(1);

// $frequence_eau = 'journalier'; //=> +0.2kc TODO
// Radis : Maxrootdepth Zr = 0.3-0.5 , Depletion Fraction (ET>5mm) : p=0.30

$area->setFrequenceEau('journalier');

// Facteur stress (water, wind, isolé sans végétation autour)
$area->setKs(1.1); // 1.1 = potager dans vent, isolé, peu de réserve eau

// Typical soil water characteristics for different soil types : "Table 19" http://www.fao.org/docrep/X0490E/x0490e0c.htm#TopOfPage
$area->setKc(0.9);
