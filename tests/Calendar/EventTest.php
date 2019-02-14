<?php


namespace Battis\IcsMunger\Tests\Calendar;


use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Calendar\Event;
use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    /**
     * @throws CalendarException
     */
    public function testInstantiaton(): void
    {
        self::assertInstanceOf(Event::class, new Event(['format' => 'iCal']));

        $this->expectException(CalendarException::class);
        new Event(123);
    }

    /**
     * @throws CalendarException
     * @throws Exception
     */
    public function testGetStart(): void
    {
        $event = new Event();
        $event->setDtstart(2019, 1, 31, 0, 0, 0);
        self::assertEquals(new DateTime('2019-01-31 00:00:00'), $event->getStart());
        self::assertFalse((new Event())->getStart());
    }
}
