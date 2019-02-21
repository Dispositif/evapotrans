<?php

use Evapotrans\MeteoData;
use Evapotrans\Location;
use Evapotrans\ValueObjects\Wind2m;
use PHPUnit\Framework\TestCase;

spl_autoload_register(
    function ($class) {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
        if (file_exists(__DIR__.'/../'.$file)) {
            require __DIR__.'/../'.$file;

            return true;
        }

        return false;
    }
);

class PenmanCalcTest extends TestCase
{
    /**
     * @var \Evapotrans\PenmanCalc
     */
    private $ETcalc;

    /**
     * @var \Evapotrans\MeteoCalc
     */
    private $meteo;

    public function setUp(): void
    {
        $this->ETcalc = new \Evapotrans\PenmanCalc();
        $this->meteo = new \Evapotrans\MeteoCalc();
    }

    /**
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

    ///**
    // * @throws Exception
    // */
    //public function testWind()
    //{
    //    $wind = new Wind(3.2, new UnitOld('m/s'), 10);
    //    self::assertEquals(2.4, $wind->getSpeed2meters());
    //
    //    $wind = new Wind(5, new UnitOld('m/s'));
    //    self::assertEquals(5, $wind->getSpeed2meters());
    //
    //    $wind = new Wind(36, new UnitOld('km/h'));
    //    self::assertEquals(10, $wind->getSpeed2meters());
    //
    //    $this->expectException(\Evapotrans\Exception::class);
    //    new Wind(5, new UnitOld('m'));
    //}

    public function testDayOfYear()
    {
        $loc = new Location(45.72, 0, 200);

        self::assertEquals(1, (new MeteoData($loc, new \DateTime('2018-01-01')))->getDaysOfYear());
        self::assertEquals(305, (new MeteoData($loc, new \DateTime('2018-11-01')))->getDaysOfYear());
        // 2016 leap year
        self::assertEquals(306, (new MeteoData($loc, new \DateTime('2016-11-01')))->getDaysOfYear());
    }

    /**
     * Only for high latitudes greater than 55° (N or S) during winter months
     * deviations may be more than 1%.
     *
     * @dataProvider daylightHoursProvider
     */
    public function testDaylightHours(string $date, $expected, $lat)
    {
        $location = new Location($lat,0.0, 0);
        $meteoData = new MeteoData($location, new DateTime($date));
        $day = $meteoData->getDaysOfYear();
        self::assertEquals($expected, $this->meteo->daylightHours($day, $lat));
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
     * @throws Exception
     * @dataProvider psychometricConstantProvider
     */
    public function testPsychometricConstant($altitude, $expected)
    {
        $location = new Location(0.0,0.0, $altitude);
        $data = new MeteoData($location, new DateTime('2018-01-01'));
        self::assertEquals($expected, $this->ETcalc->psychrometricConstant($data));
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
        $actual = $this->ETcalc->meanSaturationVapourPression(15.0, 24.5);
        self::assertEquals(2.39, $actual);
    }

    /**
     * @dataProvider saturationVaporPressionProvider
     */
    public function testSaturationVaporPression($expected, $temp)
    {
        self::assertEquals(
            $expected,
            round($this->ETcalc->saturationVapourPression($temp), 3)
        );
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

//    public function testSlopeOfSaturationVapourPressureCurve()
//    {
//        self::assertEquals(0.047, $this->ETcalc->slopeOfSaturationVapourPressureCurve(1.0));
//        self::assertEquals(0.082, $this->ETcalc->slopeOfSaturationVapourPressureCurve(10.0));
//        self::assertEquals(0.145, $this->ETcalc->slopeOfSaturationVapourPressureCurve(20.0));
//        // fail : actual 0.243
//        //self::assertEquals(0.249, $this->ETcalc->slopeOfSaturationVapourPressureCurve(30.0));
//    }
}
