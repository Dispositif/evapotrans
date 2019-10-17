## Evapotrans

[![Build Status](https://travis-ci.org/Dispositif/evapotrans.svg?branch=master)](https://travis-ci.org/Dispositif/evapotrans) [![PHP](https://img.shields.io/badge/PHP-7.2-blue.svg)]()
[![GPL](https://img.shields.io/badge/license-GPL-black.svg)]() 
[![Maintainability](https://api.codeclimate.com/v1/badges/6f0ad445bbafa41daaee/maintainability)](https://codeclimate.com/github/Dispositif/evapotrans/maintainability)
[![BCH compliance](https://bettercodehub.com/edge/badge/Dispositif/evapotrans?branch=master)]()
[![Codecov](https://img.shields.io/codecov/c/github/Dispositif/evapotrans)]()
[![StyleCI](https://github.styleci.io/repos/171043131/shield?branch=master)](https://github.styleci.io/repos/171043131)

PHP 7.2 implementation of evapotranspiration prediction. Various calculation procedures for estimating missing data are also provided : weather, climatological, physical and agronomic datas. 

GNU GPLv3 2014/2019 by Philippe M.

Computation of all data required for the calculation of the reference evapotranspiration (ETc) by means of the FAO Penman-Monteith method. 

Also computation of crop evapotranspiration (ETc) with crop and plant datas. 

Explanation : https://en.wikipedia.org/wiki/Evapotranspiration

Sources for algorithmes and test datas : http://www.fao.org/docrep/X0490E/x0490e07.htm

<img src="https://raw.githubusercontent.com/Dispositif/evapotrans/master/evapotrans.png?sanitize=true&raw=true">
<br>(Image (CC) Salsero35 / Wikimedia Commons)

----

Meteorological factors determining ET :
Solar radiation
Air temperature
Air humidity
Wind speed to determined height

Atmospheric parameters :
* Atmospheric pressure (P)
* Latent heat of vaporization (l)
* Psychrometric constant (g)

Estimation of missing datas :
* climatic data (wind speed, humidity)
* radiation data (solar, extraterrestrial)

Simplistic equation for ETo when weather data are missing

See also http://www.cesbio.ups-tlse.fr/multitemp/?p=4802

## Example 
```php
$location = new Location(43.29504, 5.3865, 35);
$data = new MeteoData($location, new \DateTime('2019-02-15'));
$data->setTmin(new Temperature(2.7));
$data->setTmax(new Temperature(61, 'F'));
$data->setActualSunnyHours(7.2); // mesured today
$data->setWind2(new Wind2m(20, 'km/h', 2));

// optional if Tdew defined
$data->setRHmax(0.90);
$data->setRHmin(0.38);

$ETcalc = new PenmanCalc();
$ETo = $ETcalc->EToPenmanMonteith($data);
dump("ETo $ETo mm/day"); // ETo 5.7 mm/day

// Crop evapotranspiration (work in progress)
$radish = new Plant(
    'Radish',
    ['initial' => 0.7, 'mid-season' => 0.9, 'late-season' => 0.85]
);
$area = new Area($radish);
$area->setGrowStade('initial');
$area->setFractionWetted(1);
$area->setStressFactor(1.1);

$ETc = (new CropEvapotrans($area, $ETo))->calcETc();
// ETc : 4.0 mm/day

```

