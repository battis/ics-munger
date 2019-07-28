<?php


namespace Battis\IcsMunger\Tests\Calendar;


use Battis\IcsMunger\PersistentCalendar\CalendarException;
use Battis\IcsMunger\PersistentCalendar\Event;
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
