<?php


namespace Battis\IcsMunger\Tests\Calendar;


use Battis\IcsMunger\Calendar\Calendar;
use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Tests\TestCalendar;

class CalendarTest extends AbstractCalendarTestCase
{
    /** @var string */
    protected static $calendarType = Calendar::class;

    /**
     * @param Calendar $calendar
     * @param string $message
     */
    protected function validateInstantiation(Calendar $calendar, string $message = ''): void
    {
        self::assertCalendarMatches(self::getBaseCalendar(), $calendar, $message);
    }

    /**
     * @param array $params
     * @return Calendar
     * @throws CalendarException
     */
    public function testInstantiation(...$params): Calendar
    {
        echo self::$calendarType . PHP_EOL;
        $this->validateInstantiation(
            new self::$calendarType(self::getBaseCalendarFileContents(), ...$params),
            'Instantiation from text data'
        );

        $this->validateInstantiation(
            new self::$calendarType(self::getCalendarFilePath(self::BASE), ...$params),
            'Instantiation from file path'
        );

        $this->validateInstantiation(
            new self::$calendarType(self::getCalendarUrl(self::BASE), ...$params),
            'Instantiation from URL'
        );

        $this->validateInstantiation(
            new self::$calendarType(
                [
                    'unique_id' => __CLASS__,
                    'url' => self::getCalendarUrl(self::BASE)
                ],
                ...$params),
            'Instantiation from configuration array'
        );

        $this->validateInstantiation(
            new self::$calendarType(self::getBaseCalendar(), ...$params),
            'Instantiation from vcalendar instance'
        );

        $this->validateInstantiation(
            new self::$calendarType(new TestCalendar(self::getBaseCalendar()), ...$params),
            'Instantiation from Calendar instance'
        );

        return new self::$calendarType(self::getBaseCalendar(), ...$params);
    }

    public function testInstantiationFromNull(...$params): void
    {
        $this->expectException(CalendarException::class);
        new self::$calendarType(null, ...$params);
    }

    public function testInstantiationFromInvalidData(...$params): void
    {
        $this->expectException(CalendarException::class);
        new self::$calendarType(123, ...$params);
    }
}
