<?php


namespace Battis\IcsMunger\Tests\Calendar;


use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Calendar\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    /**
     * @throws CalendarException
     */
    public function testInstantiatonFromInvalidData(): void
    {
        $this->expectException(CalendarException::class);
        new Event(123);
    }
}
