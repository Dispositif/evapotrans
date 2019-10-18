<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file
 */

///** @noinspection PhpUndefinedClassInspection */

use Evapotrans\Location;
use Evapotrans\MeteoData;
use Evapotrans\ValueObjects\Temperature;
use Evapotrans\ValueObjects\Wind2m;
use Evapotrans\PenmanCalc;

date_default_timezone_set('Europe/Paris');

require_once __DIR__.'/vendor/autoload.php';

$location = new Location(43.29504, 5.3865, 35);

$data = new MeteoData($location, new DateTime('2019-02-15'));
$data->setTmin(new Temperature(2.7));
$data->setTmax(new Temperature(61, 'F'));
$data->setActualSunnyHours(7.2); // mesured full sunny hours
$data->setWind2(new Wind2m(20, 'km/h', 2));

// optional if Tdew defined
$data->setRHmax(0.90);
$data->setRHmin(0.38);

$ETcalc = new PenmanCalc($data);
$ETo = $ETcalc->EToPenmanMonteith();
echo 'ETo = '.$ETo.' mm/day';

include 'example_crop.php';
