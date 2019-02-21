<?php

namespace Evapotrans;

include __DIR__.'/../Evapotrans/Location.php';

class LocationTest extends \PHPUnit\Framework\TestCase
{

    public function testCorrectSet()
    {
        $location = new Location(43.29504, 5.3865, 35);
        $this::assertInstanceOf(Location::class, $location );

        $this::assertEquals(43.29504, $location->getLatitude());
        $this::assertEquals(5.3865, $location->getLongitude());
        $this::assertEquals(35, $location->getAltitude());
    }
    public function testExceptionConstructor()
    {
        $this::expectException('ArgumentCountError');
        new Location();
    }

}
