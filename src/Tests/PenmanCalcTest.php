<?php

namespace Evapotrans\Tests;

use DateTime;
use Evapotrans\ExtraRadiation;
use Evapotrans\Location;
use Evapotrans\MeteoData;
use Evapotrans\PenmanCalc;
use Evapotrans\ValueObjects\Wind2m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PenmanCalcTest extends TestCase
{
    /**
     * @var PenmanCalc
     */
    private $ETcalc;

    /**
     * @var ExtraRadiation
     */
    private $meteo;

    public function setUp(): void
    {
        $loc = new Location(45.72, 0, 200);
        $meteoData = new MeteoData($loc, new DateTime('2018-01-01'));
        $this->ETcalc = new PenmanCalc($meteoData);
        $this->meteo = new ExtraRadiation($meteoData);
    }

    public function testCalcWind2meters()
    {
        $wind = new Wind2m(55.0);
        $actual = $this->invokeMethod(
            $wind,
            'calcWind2meters',
            [3.2, 10]
        );
        self::assertEquals(2.4, $actual);
    }

    /**
     * TDD : Allow tests on private method
     *
     * @param Object $object
     * @param string $methodName
     * @param array  $parameters
     *
     * @return mixed
     * @throws ReflectionException
     */
    private function invokeMethod(
        Object &$object,
        string $methodName,
        array $parameters = []
    ) {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @group functional
     * @throws Exception
     */
    public function testWind2m()
    {
        $wind = new Wind2m(3.2, 'm/s', 10);
        self::assertEquals(2.4, $wind->getValue());

        $wind = new Wind2m(5, 'm/s', 2);
        self::assertEquals(5, $wind->getValue());

        $wind = new Wind2m(36, 'km/h', 2);
        self::assertEquals(10, $wind->getValue());

        $this->expectException(\Evapotrans\Exception::class);
        new Wind2m(5, 'm');
    }

    public function testDayOfYear()
    {
        $loc = new Location(45.72, 0, 200);

        self::assertEquals(
            1,
            (new MeteoData(
                $loc, new DateTime('2018-01-01')
            ))->getDaysOfYear()
        );
        self::assertEquals(
            305,
            (new MeteoData(
                $loc, new DateTime('2018-11-01')
            ))->getDaysOfYear()
        );
        // 2016 leap year
        self::assertEquals(
            306,
            (new MeteoData(
                $loc, new DateTime('2016-11-01')
            ))->getDaysOfYear()
        );
    }

    /**
     * Only for high latitudes greater than 55° (N or S) during winter months
     * deviations may be more than 1%.
     *
     * @dataProvider daylightHoursProvider
     *
     * @param string $date
     * @param        $expected
     * @param        $lat
     *
     * @throws \Evapotrans\Exception
     */
    public function testCalcDaylightHours(string $date, $expected, $lat)
    {
        $location = new Location($lat, 0.0, 0);
        $meteoData = new MeteoData($location, new DateTime($date));
        self::assertEquals($expected, $meteoData->getMaxDaylightHours());
    }

    /**
     * Results from table2.7 (good estimate (error < 1 %))
     * http://www.fao.org/docrep/X0490E/x0490e0j.htm#TopOfPage.
     *
     * @return array
     */
    public function daylightHoursProvider()
    {
        return [
            ['2018-02-15', 9.8, 50],
            ['2018-02-15', 10.5, 40],
            ['2018-02-15', 13.5, -40],
            ['2018-02-15', 11.0, 30],
            ['2018-02-15', 11.3, 20],
            ['2018-07-15', 14.6, 40],
            ['2018-07-15', 14.8, 42],
        ];
    }

    /**
     * @param $altitude
     * @param $expected
     *
     * @throws Exception
     * @dataProvider psychometricConstantProvider
     */
    public function testPsychometricConstant($altitude, $expected)
    {
        $location = new Location(0.0, 0.0, $altitude);
        $data = new MeteoData($location, new DateTime('2018-01-01'));
        $calc = new PenmanCalc($data);
        self::assertEquals(
            $expected,
            $this->invokeMethod(
                $calc,
                'psychrometricConstant'
            )
        );
    }

    public function psychometricConstantProvider()
    {
        return [
            [0, 0.067],
            [1000, 0.060],
            [1800, 0.054],
            [2000, 0.053],
            [3000, 0.047],
        ];
    }

    public function testMeanSaturationVaporPression()
    {
        $actual = $this->invokeMethod(
            $this->ETcalc,
            'meanSaturationVapourPression',
            [15.0, 24.5]
        );
        self::assertEquals(2.39, $actual);
    }

    /**
     * @dataProvider saturationVaporPressionProvider
     * @param $expected
     * @param $temp
     * @throws ReflectionException
*/
    public function testSaturationVaporPression($expected, $temp)
    {
        $actual = $this->invokeMethod(
            $this->ETcalc,
            'saturationVapourPression',
            [$temp]
        );
        self::assertEquals($expected, round($actual, 3));
    }

    public function saturationVaporPressionProvider()
    {
        return [
            [0.657, 1.0],
            [0.657, 1.0],
            [1.228, 10.0],
            [2.338, 20.0],
            [4.243, 30.0],
        ];
    }

    /**
     * @param $Tmean
     * @param $expected
     *
     * @throws ReflectionException
     * @dataProvider slopeOfSaturationVapourPressureCurveProvider
     */
    public function testSlopeOfSaturationVapourPressureCurve($Tmean, $expected)
    {
        $actual = $this->invokeMethod(
            $this->ETcalc,
            'slopeOfSaturationVapourPressureCurve',
            [$Tmean]
        );
        self::assertEquals($expected, $actual);
    }

    public function slopeOfSaturationVapourPressureCurveProvider()
    {
        return [
            [1.0, 0.047],
            [10.0, 0.082],
            [20.0, 0.145],
            //            [30.0, 0.249],
        ];
    }
}
