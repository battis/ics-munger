<?php


namespace Battis\IcsMunger\Tests;


use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Tests\Calendar\AbstractCalendarTestCase;
use kigkonsult\iCalcreator\vcalendar;

class CalendarTest extends AbstractCalendarTestCase
{
    /**
     * @throws CalendarException
     */
    public function testInstantiationFromNullData(): void
    {
        self::expectException(CalendarException::class);
        $c = new Calendar(null);
    }

    /**
     * @throws CalendarException
     */
    public function testInstantiationFromInvalidData(): void
    {
        self::expectException(CalendarException::class);
        $c = new Calendar(123);
    }

    /**
     * @throws CalendarException
     */
    public function testInstantiationFromTextData(): void
    {
        $c = new Calendar(self::getBaseCalendarFileContents());
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     */
    public function testInstantiationFromFilePath(): void
    {
        $c = new Calendar(self::getCalendarFilePath('base'));
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     */
    public function testInstantiationFromUrl(): void
    {
        $c = new Calendar(self::getCalendarUrl('base'));
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     */
    public function testInstantiationFromVcalendar(): void
    {
        $vcalendar = new vcalendar();
        $vcalendar->parse(self::getBaseCalendarFileContents());
        $c = new Calendar($vcalendar);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     */
    public function testInstantiationFromCalendar(): void
    {
        $test = new TestCalendar(self::getBaseCalendarFileContents());
        $c = new Calendar($test);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }
}
