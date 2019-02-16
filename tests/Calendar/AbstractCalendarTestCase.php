<?php


namespace Battis\IcsMunger\Tests\Calendar;


use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\vevent;
use PHPUnit\Framework\TestCase;

abstract class AbstractCalendarTestCase extends TestCase
{
    const BASE = 'base';
    const BASE_FILEPATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data';
    const BASE_URL = 'https://raw.githubusercontent.com/battis/ics-munger/master/tests/data';
    /**
     * @var vcalendar
     */
    private static $base = null;
    /**
     * @var null string
     */
    private static $baseFile = null;

    public static function assertCalendarMatches(vcalendar $expected, vcalendar $actual, string $message = ''): void
    {
        while ($component = $expected->getComponent()) {
            $comparison = $actual->getComponent($component->getProperty('uid'));
            if ($component instanceof vevent && $comparison instanceof vevent) {
                self::assertEventMatches($component, $comparison, $message);
            } else {
                self::assertEquals($component->createComponent(), $comparison->createComponent());
            }
        }
        self::assertEquals($expected->countComponents(), $actual->countComponents(), $message);
    }

    public static function assertEventMatches(vevent $expected, vevent $actual, string $message = ''): void
    {
        self::assertEquals($expected->createUid(), $actual->createUid(), $message);
        self::assertEquals($expected->createSummary(), $actual->createSummary(), $message);
        self::assertEquals($expected->createDescription(), $actual->createDescription(), $message);
        self::assertEquals($expected->createDtstart(), $actual->createDtstart(), $message);
        self::assertEquals($expected->createDtend(), $actual->createDtend(), $message);
    }

    protected static function getBaseCalendar(): vcalendar
    {
        if (self::$base === null) {
            self::$base = new vcalendar();
            self::$base->parse(self::getBaseCalendarFileContents());
        }
        return self::$base;
    }

    protected static function getBaseCalendarFileContents(): string
    {
        if (self::$baseFile === null) {
            self::$baseFile = file_get_contents(self::getCalendarFilePath('base'));
        }
        return self::$baseFile;
    }

    protected static function getCalendarFilePath(string $name): string
    {
        if (strpos($name, '.ics') === false) {
            $name .= '.ics';
        }
        return self::getFilepath('calendars' . DIRECTORY_SEPARATOR . $name);
    }

    protected static function getFilepath(string $name): string
    {
        return realpath(self::BASE_FILEPATH . DIRECTORY_SEPARATOR . $name);
    }

    protected static function getCalendar(string $name): vcalendar
    {
        $c = new vcalendar();
        $c->parse(file_get_contents(self::getCalendarFilePath($name)));
        return $c;
    }

    protected static function getCalendarUrl(string $name): string
    {
        if (strpos($name, 'ics') === false) {
            $name .= '.ics';
        }
        return self::getUrl("calendars/$name");
    }

    protected static function getUrl(string $filename): string
    {
        return self::BASE_URL . "/$filename";
    }
}
