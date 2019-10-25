<?php
/**
 * This file is part of dispositif/evapotrans library
 * 2014-2019 (c) Philippe M. <dispositif@gmail.com>
 * For the full copyright and license information, please view the LICENSE file.
 */

namespace Evapotrans\Tests;

use Evapotrans\Location;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    public function testCorrectSet()
    {
        $location = new Location(43.29504, 5.3865, 35);
        $this::assertInstanceOf(Location::class, $location);

        $this::assertEquals(43.29504, $location->getLatitude());
        $this::assertEquals(5.3865, $location->getLongitude());
        $this::assertEquals(35, $location->getAltitude());
        $this::assertEquals(0.76, round($location->getLatitudeRadian(), 2));
    }

    public function testExceptionConstructor()
    {
        $this::expectException('ArgumentCountError');
        new Location();
    }
}
