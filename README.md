#Evapotrans
By Dispositif alias Philippe M.

PHP 7.2 implementation of evapotranspiration prediction. Various calculation procedures for estimating missing data are also provided : weather, climatological, physical and agronomic datas. 

Computation of all data required for the calculation of the reference evapotranspiration (ET)c by means of the FAO Penman-Monteith method. Also computation of crop evapotranspiration (ETc) with others algos. 

Explanation : https://en.wikipedia.org/wiki/Evapotranspiration

Sources for algorithmes and test datas : http://www.fao.org/docrep/X0490E/x0490e07.htm

Meteorological factors determining ET :
Solar radiation
Air temperature
Air humidity
Wind speed to determined height

Atmospheric parameters :
* Atmospheric pressure (P)
* Latent heat of vaporization (l)
* Psychrometric constant (g)

Estimation of extraterrestrial radiation

Estimating missing climatic data
Estimating missing humidity data
Estimating missing radiation data
Missing wind speed data

alternative equation for ETo when weather data are missing

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

$ETo = (new EvapotransCalc())->EToPenmanMonteith($data); // 1.1 mm/day
```

