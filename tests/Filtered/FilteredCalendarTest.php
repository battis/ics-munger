<?php


namespace Battis\IcsMunger\Tests\Filtered;


use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\Filtered\FilteredCalendar;
use Battis\IcsMunger\Filtered\FilteredCalendarException;
use Battis\IcsMunger\Filtered\FilterException;
use Battis\IcsMunger\Filtered\Tests\BooleanOperators\AndOp;
use Battis\IcsMunger\Filtered\Tests\BooleanOperators\NotOp;
use Battis\IcsMunger\Filtered\Tests\BooleanOperators\OrOp;
use Battis\IcsMunger\Filtered\Tests\Comparisons\Equals;
use Battis\IcsMunger\Filtered\Tests\Comparisons\GreaterThan;
use Battis\IcsMunger\Filtered\Tests\Comparisons\LessThan;
use Battis\IcsMunger\Filtered\Tests\Comparisons\Like;
use Battis\IcsMunger\Tests\Calendar\AbstractCalendarTestCase;
use Battis\IcsMunger\Tests\TestCalendar;
use kigkonsult\iCalcreator\vcalendar;

class FilteredCalendarTest extends AbstractCalendarTestCase
{
    const BASE = 'base';
    const SUMMARY = 'summary';
    const CALENDAR = 'calendar';
    const KEYWORD = 'keyword';
    const KEYWORD_A = 'keywordA';
    const KEYWORD_B = 'keywordB';

    private static $filteredCalendars = [];

    protected function setUp()
    {
        parent::setUp();
        if (empty(self::$filteredCalendars)) {
            foreach (scandir(dirname(self::getCalendarFilePath(self::BASE))) as $filename) {
                if (preg_match('/^filtered_([^_]+)_([^_]+)\\.ics$/', $filename, $matches)) {
                    self::$filteredCalendars[$matches[1]] = [
                        self::CALENDAR => $filename,
                        self::KEYWORD => $matches[2]
                    ];
                } elseif (preg_match('/^filtered_([^_]+)_([^_]+)_([^_]+)\\.ics$/', $filename, $matches)) {
                    self::$filteredCalendars[$matches[1]] = [
                        self::CALENDAR => $filename,
                        self::KEYWORD_A => $matches[2],
                        self::KEYWORD_B => $matches[3]
                    ];
                }
            }
        }
    }

    protected static function getFilteredCalendar(string $filter): array
    {
        return self::$filteredCalendars[$filter];
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function testInstantiationFromNullData(): void
    {
        $this->expectException(CalendarException::class);
        new FilteredCalendar(null);
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function testInstantiationFromInvalidData(): void
    {
        $this->expectException(CalendarException::class);
        new FilteredCalendar(123);
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function testInstantiationFromTextData(): void
    {
        $c = new FilteredCalendar(self::getBaseCalendarFileContents());
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function testInstantiationFromFilePath(): void
    {
        $c = new FilteredCalendar(self::getCalendarFilePath(self::BASE));
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function testInstantiationFromUrl(): void
    {
        $c = new FilteredCalendar(self::getCalendarUrl(self::BASE));
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function testInstantiationFromVcalendar(): void
    {
        $vcalendar = new vcalendar();
        $vcalendar->parse(self::getBaseCalendarFileContents());
        $c = new FilteredCalendar($vcalendar);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function testInstantiationFromCalendar(): void
    {
        $test = new TestCalendar(self::getBaseCalendarFileContents());
        $c = new FilteredCalendar($test);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     */
    public function testComparisonFilters(): void
    {
        foreach (['Contains', 'Equals', 'GreaterThan', 'GreaterThanOrEquals', 'LessThan', 'LessThanOrEquals'] as $comparison) {
            $params = self::getFilteredCalendar($comparison);
            $class = "Battis\\IcsMunger\\Filtered\\Tests\\Comparisons\\$comparison";
            $c = new FilteredCalendar(
                self::getCalendarFilePath(self::BASE),
                new $class(self::SUMMARY, $params[self::KEYWORD]));
            self::assertCalendarMatches(self::getCalendar($params[self::CALENDAR]), $c);
        }
    }

    /**
     * @throws CalendarException
     * @throws FilterException
     * @throws FilteredCalendarException
     */
    public function testAndOperator(): void
    {
        $params = self::getFilteredCalendar('And');
        $c = new FilteredCalendar(
            self::getCalendarFilePath(self::BASE),
            AndOp::expr([
                GreaterThan::expr(self::SUMMARY, $params[self::KEYWORD_A]),
                LessThan::expr(self::SUMMARY, $params[self::KEYWORD_B])
            ])
        );
        self::assertCalendarMatches(self::getCalendar($params[self::CALENDAR]), $c);
    }

    /**
     * @throws CalendarException
     * @throws FilterException
     * @throws FilteredCalendarException
     */
    public function testOrOperator(): void
    {
        $params = self::getFilteredCalendar('Or');
        $c = new FilteredCalendar(
            self::getCalendarFilePath(self::BASE),
            OrOp::expr([
                LessThan::expr(self::SUMMARY, $params[self::KEYWORD_A]),
                GreaterThan::expr(self::SUMMARY, $params[self::KEYWORD_B])
            ])
        );
        self::assertCalendarMatches(self::getCalendar($params[self::CALENDAR]), $c);
    }

    /**
     * @throws CalendarException
     * @throws FilterException
     * @throws FilteredCalendarException
     */
    public function testNotOperator(): void
    {
        $params = self::getFilteredCalendar('Not');
        $c = new FilteredCalendar(
            self::getCalendarFilePath(self::BASE),
            NotOp::expr(Equals::expr(self::SUMMARY, $params[self::KEYWORD_A]))
        );
        self::assertCalendarMatches(self::getCalendar($params[self::CALENDAR]), $c);
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     * @throws FilterException
     */
    public function testLikeFilter(): void
    {
        $c = new FilteredCalendar(self::getCalendarFilePath(self::BASE), new Like(self::SUMMARY, '/([a-z])\\1/i'));
        self::assertCalendarMatches(self::getCalendar('silly_aunt_sally'), $c);
    }
}
