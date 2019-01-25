<?php


namespace Battis\IcsMunger\Tests;


use kigkonsult\iCalcreator\vcalendar;
use PHPUnit\Framework\TestCase;

abstract class CalendarTestCase extends TestCase
{
    const SEP = DIRECTORY_SEPARATOR;
    const BASE_CALENDAR_FILEPATH = __DIR__ . self::SEP . 'data' . self::SEP . 'calendars' . self::SEP . 'base.ics';
    const BASE_CALENDAR_URL = 'https://raw.githubusercontent.com/battis/ics-munger/master/tests/data/calendars/base.ics';

    /**
     * @var vcalendar
     */
    protected static $base = null;

    abstract public function testInstantiateFromUrl(): void;

    abstract public function testInstantiateFromFilepath(): void;

    abstract public function testInstantiateFromVcalendar(): void;

    abstract public function testInstantiateFromString(): void;

    abstract public function testInvalidInstantiate(): void;

    protected function setUp()
    {
        if (self::$base == null) {
            self::$base = new vcalendar();
            self::$base->parse(file_get_contents(self::BASE_CALENDAR_FILEPATH));
        }
    }
}
