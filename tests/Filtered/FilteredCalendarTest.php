<?php


namespace Battis\IcsMunger\Tests\Filtered;


use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\Event;
use Battis\IcsMunger\Filtered\FilteredCalendar;
use Battis\IcsMunger\Filtered\FilteredCalendarException;
use Battis\IcsMunger\Tests\Calendar\CalendarTest;
use kigkonsult\iCalcreator\vevent;

class FilteredCalendarTest extends CalendarTest
{
    protected function setUp()
    {
        self::$calendarType = FilteredCalendar::class;
        parent::setUp();
    }

    /**
     * @param Calendar $calendar
     * @param string $message
     */
    protected function validateInstantiation(Calendar $calendar, string $message = ''): void
    {
        parent::validateInstantiation($calendar, $message);
        self::assertInstanceOf(FilteredCalendar::class, $calendar, $message);
        self::assertIsCallable($calendar->getTest(), $message);
        self::assertIsCallable($calendar->getTransformation(), $message);
    }

    public function testInstantiation(...$params): Calendar
    {
        parent::testInstantiation(
            function () {
                return true;
            },
            function (Event $e) {
                return $e;
            },
            ...$params
        );

        // TODO instantiate as a copy of another FilteredCalendar
        self::assertTrue(true, 'Instantiation from FilteredCalendar instance');

        return new self::$calendarType(
            self::getBaseCalendar(),
            function () {
                return true;
            },
            function (Event $e) {
                return $e;
            },
            ...$params
        );
    }

    public function testInstantiationWithInvalidTestCallbackSignature(): void
    {
        $this->expectException(FilteredCalendarException::class);
        new self::$calendarType(self::getBaseCalendar(), function () {
            return 0;
        }, null);
    }

    public function testInstantiationWithInvalidTransformationCallbackSignature(): void
    {
        $this->expectException(FilteredCalendarException::class);
        new self::$calendarType(self::getBaseCalendar(), null, function () {
            return new vevent();
        });
    }
}
