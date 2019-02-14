<?php


namespace Battis\IcsMunger\Tests;


use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Calendar\Event;
use Battis\IcsMunger\Tests\Calendar\AbstractCalendarTestCase;
use Exception;

class CalendarTest extends AbstractCalendarTestCase
{
    /**
     * @throws CalendarException
     * @throws Exception
     */
    public function testInstantiation(): Calendar
    {
        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new Calendar(self::getBaseCalendarFileContents()),
            'Instantiation from text data'
        );

        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new Calendar(self::getCalendarFilePath(self::BASE)),
            'Instantiation from file path'
        );

        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new Calendar(self::getCalendarUrl(self::BASE)),
            'Instantiation from URL'
        );

        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new Calendar([
                'unique_id' => __CLASS__,
                'url' => self::getCalendarUrl(self::BASE)
            ]),
            'Instantiation from configuration array'
        );

        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new Calendar(self::getBaseCalendar()),
            'Instantiation from vcalendar instance'
        );

        $test = new TestCalendar(self::getBaseCalendarFileContents());
        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new Calendar($test),
            'Instantiation from Calendar instance'
        );

        return $test;
    }

    /**
     * @throws CalendarException
     */
    public function testInstantiationFromNull(): void
    {
        $this->expectException(CalendarException::class);
        new Calendar(null);
    }

    /**
     * @throws CalendarException
     */
    public function testInstantiationFromInvalidData(): void
    {
        $this->expectException(CalendarException::class);
        new Calendar(123);
    }

    /**
     * @param Calendar $calendar
     * @depends testInstantiation
     * @throws CalendarException
     */
    public function testGetEvent(Calendar $calendar): void
    {
        $uid = [];
        while ($event = $calendar->getEvent()) {
            self::assertInstanceOf(Event::class, $event);
            array_push($uid, $event->getUid());
        }
        self::assertNotEmpty($uid);
        self::assertInstanceOf(Event::class, $calendar->getEvent($uid[rand(0, count($uid) - 1)]));
        self::assertFalse(array_search(__FUNCTION__, $uid));
        self::assertFalse($calendar->getEvent(__FUNCTION__));
    }
}
