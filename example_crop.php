<?php

///** @noinspection PhpUndefinedClassInspection */

use Evapotrans\Area;
use Evapotrans\CropEvapotrans;
use Evapotrans\ValueObjects\Plant;

// http://www.fao.org/docrep/X0490E/x0490e0b.htm#crop coefficients
$radish = new Plant(
    'Radish', ['initial' => 0.7, 'mid-season' => 0.9, 'late-season' => 0.85]
);

//$california = new Plant(
//    'turfgrassesCalifornia',
//    ['midCoolSeason' => 0.80, 'midWarmSeason' => 0.60]
//);

$area = new Area($radish);
$area->setGrowStade('initial');
$area->setFractionWetted(1);
$area->setStressFactor(1.1);

$ETc = (new CropEvapotrans($area, $ETo))->calcETc();
var_dump($ETc);
