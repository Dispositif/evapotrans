#Evapotrans

[![PHP](https://img.shields.io/badge/PHP-7.2-blue.svg)]()
[![GPL](https://img.shields.io/badge/license-GPL-yellow.svg)]()

PHP 7.2 implementation of evapotranspiration prediction. Various calculation procedures for estimating missing data are also provided : weather, climatological, physical and agronomic datas. 

GNU GPLv3 (c) 2014-2019 dispositif / Philippe Minelli

Computation of all data required for the calculation of the reference evapotranspiration (ETc) by means of the FAO Penman-Monteith method. 

Also computation of crop evapotranspiration (ETc) with crop and plant datas. 

Explanation : https://en.wikipedia.org/wiki/Evapotranspiration

Sources for algorithmes and test datas : http://www.fao.org/docrep/X0490E/x0490e07.htm

<img src="https://raw.githubusercontent.com/Dispositif/evapotrans/master/evapotrans.png?sanitize=true&raw=true">

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
$location = new Location(43.29, 5.38, 35);
$data = new MeteoData($location, new \DateTime('2019-02-15'));
$data->setTmin(new Temperature(2.7));
$data->setTmax(new Temperature(61, 'F'));
$data->setActualSunnyHours(7.2); 
$data->setWind2(new Wind2m(20, 'km/h', 2)); // mesured at 2 meters
$data->setRHmax(0.90);
$data->setRHmin(0.38);

$ETo = (new PenmanCalc())->EToPenmanMonteith($data);
// ETo = 1.1 (mm/day)


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
// ETc = 0.8 (mm/day)

```

