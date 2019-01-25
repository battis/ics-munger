<?php


namespace Battis\IcsMunger\Tests\Filtered;


use Battis\IcsMunger\Filtered\FilteredCalendar;
use Battis\IcsMunger\Filtered\Tests\BooleanOperators\AndOp;
use Battis\IcsMunger\IcsMungerException;
use Battis\IcsMunger\Tests\CalendarTestCase;

class FilteredCalendarTest extends CalendarTestCase
{
    public function testInstantiateFromUrl(): void
    {
        $c = new FilteredCalendar(self::BASE_CALENDAR_URL, AndOp::expr([]));
        self::assertEquals(self::$base->countComponents(), $c->getData()->countComponents());
    }

    public function testInstantiateFromFilepath(): void
    {
        $c = new FilteredCalendar(self::BASE_CALENDAR_FILEPATH, AndOp::expr([]));
        self::assertEquals(self::$base->countComponents(), $c->getData()->countComponents());
    }

    public function testInstantiateFromVcalendar(): void
    {
        $c = new FilteredCalendar(clone self::$base, AndOp::expr([]));
        self::assertEquals(self::$base->countComponents(), $c->getData()->countComponents());
    }

    public function testInstantiateFromString(): void
    {
        $c = new FilteredCalendar(file_get_contents(self::BASE_CALENDAR_FILEPATH), AndOp::expr([]));
        self::assertEquals(self::$base->countComponents(), $c->getData()->countComponents());
    }

    public function testInvalidInstantiate(): void
    {
        $this->expectException(IcsMungerException::class);
        new FilteredCalendar(123, AndOp::expr([]));
    }
}
