<?php

use Evapotrans\Location;
use Evapotrans\MeteoData;
use Evapotrans\RadiationCalc;
use Evapotrans\ValueObjects\Temperature;
use PHPUnit\Framework\TestCase;

class RadiationCalcTest extends TestCase
{
    /**
     * @var \Evapotrans\ExtraRadiation
     */
    private $extraRadiation;

    /**
     * @var RadiationCalc
     */
    private $radiationCalc;

    public function setUp(): void
    {
        $meteoData = new MeteoData(
            new Location(-22.90, 0), new \DateTime('2015-05-15')
        );
        $this->extraRadiation = new \Evapotrans\ExtraRadiation($meteoData);
        $this->radiationCalc = new RadiationCalc($meteoData);
    }

    /**
     * Allow TDD tests on private method
     *
     * @throws ReflectionException
     */
    private function invokeMethod(Object &$object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testSolarRadiation()
    {
        $actual = $this->radiationCalc->solarRadiationFromDurationSunshineAndRa(25.1, 7.1, 10.9);
        self::assertEquals(14.4, $actual); // doc 14.5
    }

    /**
     * EXAMPLE 15. Determination of solar radiation from temperature data.
     *
     * @group functional
     * @throws Exception
     */
    public function testSolarRadiationFromTempData()
    {
        //45°43'N and at 200 m above sea level. In July, the mean monthly
        // maximum and minimum air temperatures are 26.6 and 14.8°C respectively.
        $loc = new \Evapotrans\Location(45.72, 0, 200);
        $loc->setKRs(0.16);
        $date = new \DateTime('2018-07-15');
        $data = new MeteoData($loc, $date);
        $data->setTmax(new Temperature(26.6));
        $data->setTmin(new Temperature(14.8));

        self::assertEquals(
            22.3,
            (new RadiationCalc($data))->solarRadiationStrategyFromMeteodata($data)
        );
    }

    public function testExtraterrestrialRadiation()
    {
        $data = new MeteoData(
            new Location(-20, 0), new \DateTime('2018-09-03')
        );
        //        $data->setRa(null);  // Todo setter/getter Ra
        $Ra = (new RadiationCalc($data))->extraterresRadiationFromMeteodata($data);
        self::assertEquals(32.2, $Ra);
    }

    /**
     * @group functional
     */
    public function testNetLongwaveRadiation()
    {
        $Rnl = $this->radiationCalc->netLongwaveRadiation(2.1, 25.1, 19.1, 14.5, 18.8);
        self::assertEquals(3.5, $Rnl);
    }
}
