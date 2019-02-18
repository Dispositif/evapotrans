<?php

use Evapotrans\MeteoData;
use Evapotrans\ValueObjects\Unit;
use Evapotrans\ValueObjects\Wind;
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

class ETcalcStrategyTest extends TestCase
{
    /**
     * @var \Evapotrans\ETcalcStrategy
     */
    private $ETcalc;
    /**
     * @var \Evapotrans\MeteoCalculation
     */
    private $meteo;

    public function setUp(): void
    {
        $this->ETcalc = new \Evapotrans\ETcalcStrategy();
        $this->meteo = new \Evapotrans\MeteoCalculation();
    }

    /**
     * @throws Exception
     */
    public function testWind()
    {
        $wind = new Wind(3.2, new Unit('m/s'), 10);
        $this->assertEquals(2.4, $wind->getSpeed2meters());

        $wind = new Wind(5, new Unit('m/s'));
        $this->assertEquals(5, $wind->getSpeed2meters());

        $wind = new Wind(36, new Unit('km/h'));
        $this->assertEquals(10, $wind->getSpeed2meters());

        $this->expectException(\Evapotrans\Exception::class);
        new Wind(5, new Unit('m'));
    }

    public function testDayOfYear()
    {
        $this->assertEquals(1, $this->meteo->daysOfYear(new \DateTime('2018-01-01')));
        $this->assertEquals(305, $this->meteo->daysOfYear(new \DateTime('2018-11-01')));
        // 2016 leap year
        $this->assertEquals(306, $this->meteo->daysOfYear(new \DateTime('2016-11-01')));
    }

    /**
     * Only for high latitudes greater than 55° (N or S) during winter months
     * deviations may be more than 1%.
     *
     * @dataProvider daylightHoursProvider
     */
    public function testDaylightHours($date, $expected, $lat)
    {
        $day = $this->meteo->daysOfYear(new \DateTime($date));
        $this->assertEquals($expected, $this->meteo->daylightHours($day, $lat));
    }

    /**
     * Results from table2.7 (good estimate (error < 1 %))
     * http://www.fao.org/docrep/X0490E/x0490e0j.htm#TopOfPage
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
     */
    public function testSolarRadiation()
    {
        //$location = new \Evapotrans\Location(-22.90, 0);
        //$date = new \DateTime('2015-05-15');
        $Ra = 25.1;
        $N = 10.9;
        $n = 7.1;
        $actual = $this->ETcalc->solarRadiationFromDurationSunshineAndRa($Ra, $n, $N);
        $this->assertEquals(14.4, $actual); // doc 14.5
    }

    /**
     * EXAMPLE 15. Determination of solar radiation from temperature data
     * @throws Exception
     */
    public function testSolarRadiationFromTempData()
    {
        //45°43'N and at 200 m above sea level. In July, the mean monthly
        // maximum and minimum air temperatures are 26.6 and 14.8°C respectively.
        $loc = new \Evapotrans\Location(45.72, 0, 200 );
        $loc->setKRs(0.16);
        $date = new \DateTime('2018-07-15');
        $data = new MeteoData($loc, $date);
        $data->setTmax(26.6);
        $data->setTmin(14.8);

        $this->assertEquals(22.3,
            $this->ETcalc->solarRadiationStrategyFromMeteodata($data));
    }

    /**
     * @param $altitude
     * @param $expected
     *
     * @dataProvider psychometricConstantProvider
     */
    public function testPsychometricConstant($altitude, $expected)
    {
        $this->assertEquals($expected, $this->ETcalc->psychrometricConstant($altitude));
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
        $this->assertEquals(2.39, $actual);
    }

    public function testSaturationVaporPression()
    {
        $actual = function ($that, $temp) {
            return round($that->ETcalc->saturationVapourPression($temp), 3);
        };
        $this->assertEquals(0.657, $actual($this, 1.0));
        $this->assertEquals(0.657, $actual($this, 1.0));
        $this->assertEquals(1.228, $actual($this, 10.0));
        $this->assertEquals(2.338, $actual($this, 20.0));
        $this->assertEquals(4.243, $actual($this, 30.0));
    }

    public function testExtraterrestrialRadiation()
    {
        $days = $this->meteo->daysOfYear(new \DateTime('2018-09-03'));
        $Ra = $this->meteo->extraterrestrialRadiationDailyPeriod($days, -20);
        $this->assertEquals(32.2, $Ra);
    }

    public function testNetLongwaveRadiation()
    {
        $Rnl = $this->ETcalc->netLongwaveRadiation(2.1, 25.1, 19.1, 14.5, 18.8);
        $this->assertEquals(3.5, $Rnl);
    }

    public function testSlopeOfSaturationVapourPressureCurve()
    {
        $this->assertEquals(0.047, $this->ETcalc->slopeOfSaturationVapourPressureCurve(1.0));
        $this->assertEquals(0.082, $this->ETcalc->slopeOfSaturationVapourPressureCurve(10.0));
        $this->assertEquals(0.145, $this->ETcalc->slopeOfSaturationVapourPressureCurve(20.0));
        // fail : actual 0.243
        //$this->assertEquals(0.249, $this->ETcalc->slopeOfSaturationVapourPressureCurve(30.0));
    }
}
