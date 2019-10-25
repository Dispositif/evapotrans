<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file.
 */

namespace Evapotrans\Tests;

use DateTime;
use Evapotrans\Exception;
use Evapotrans\ExtraRadiation;
use Evapotrans\Location;
use Evapotrans\MeteoData;
use Evapotrans\RadiationCalc;
use Evapotrans\ValueObjects\Temperature;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class RadiationCalcTest extends TestCase
{
    /**
     * @var ExtraRadiation
     */
    private $extraRadiation;

    /**
     * @var RadiationCalc
     */
    private $radiationCalc;

    public function setUp(): void
    {
        $meteoData = new MeteoData(
            new Location(-22.90, 0), new DateTime('2015-05-15')
        );
        $this->extraRadiation = new ExtraRadiation($meteoData);
        $this->radiationCalc = new RadiationCalc($meteoData);
    }

    /**
     * Example 16 http://www.fao.org/3/X0490E/x0490e07.htm
     * Calculate the net radiation for Bangkok (13°44'N) by using Tmax, and
     * Tmin. The station is located at the coast at 2 m above sea level. In
     * April, the monthly average of the daily maximum temperature, daily
     * minimum temperature and daily vapour pressure are 34.8°C, 25.6°C and
     * 2.85 kPa respectively.
     *
     * @throws Exception
     */
    public function testNetRadiationFromMeteodata()
    {
        $location = new Location(13.73, 0, 2);
        $location->setKRs(0.19); // coastal location

        $data = new MeteoData($location, new DateTime('2019-04-15'));
        $data->setTmin(new Temperature(25.6));
        $data->setTmax(new Temperature(34.8));
        $data->actualVaporPression = 2.85;

        $radiation = new RadiationCalc($data);

        self::assertEquals(13.9, $radiation->netRadiationFromMeteodata());
    }

    public function testSolarRadiation()
    {
        $actual
            = $this->invokeMethod(
            $this->radiationCalc,
            'solarRadiationFromDurationSunshineAndRa',
            [25.1, 7.1, 10.9]
        );
        self::assertEquals(14.4, $actual); // doc 14.5
    }

    /**
     * Allow TDD tests on private method.
     *
     * @param object $object
     * @param string $methodName
     * @param array  $parameters
     *
     * @return mixed
     * @throws ReflectionException*@throws \ReflectionException
     */
    private function invokeMethod(
        object &$object,
        string $methodName,
        array $parameters = []
    ) {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * EXAMPLE 15. Determination of solar radiation from temperature data.
     *
     * @group functional
     * @throws \Exception
     */
    public function testSolarRadiationFromTempData()
    {
        //45°43'N and at 200 m above sea level. In July, the mean monthly
        // maximum and minimum air temperatures are 26.6 and 14.8°C respectively.
        $loc = new Location(45.72, 0, 200);
        $loc->setKRs(0.16);
        $date = new DateTime('2018-07-15');
        $data = new MeteoData($loc, $date);
        $data->setTmax(new Temperature(26.6));
        $data->setTmin(new Temperature(14.8));
        $radiation = new RadiationCalc($data);

        $actual = $this->invokeMethod(
            $radiation,
            'solarRadiationStrategyFromMeteodata'
        );
        self::assertEquals(22.3, $actual);
    }

    public function testExtraterrestrialRadiation()
    {
        $data = new MeteoData(
            new Location(-20, 0), new DateTime('2018-09-03')
        );
        $rad = new RadiationCalc($data);
        $Ra = $this->invokeMethod(
            $rad,
            'extraterresRadiationFromMeteodata',
            [$data]
        );
        self::assertEquals(32.2, $Ra);
    }

    /**
     * @group functional
     */
    public function testNetLongwaveRadiation()
    {
        $Rnl = $this->invokeMethod(
            $this->radiationCalc,
            'netLongwaveRadiation',
            [
                2.1,
                25.1,
                19.1,
                14.5,
                18.8,
            ]
        );
        self::assertEquals(3.5, $Rnl);
    }
}
