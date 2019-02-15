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
use Battis\IcsMunger\Filtered\Transformations\RegexReplace;
use Battis\IcsMunger\Filtered\Transformations\RenderMarkdown;
use Battis\IcsMunger\Filtered\Transformations\Replace;
use Battis\IcsMunger\Tests\Calendar\AbstractCalendarTestCase;
use Battis\IcsMunger\Tests\TestCalendar;
use Exception;

class FilteredCalendarTest extends AbstractCalendarTestCase
{
    const SUMMARY = 'summary';
    const DESCRIPTION = 'description';
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
    public function testInstantiation(): void
    {
        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new FilteredCalendar(self::getBaseCalendarFileContents()),
            'Instantiation from text data'
        );

        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new FilteredCalendar(self::getCalendarFilePath(self::BASE)),
            'Instantiation from file path'
        );

        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new FilteredCalendar(self::getCalendarUrl(self::BASE)),
            'Instantiation from URL'
        );

        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new FilteredCalendar([
                'unique_id' => __CLASS__,
                'url' => self::getCalendarUrl(self::BASE)
            ]),
            'Instantiation from configuration array'
        );

        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new FilteredCalendar(self::getBaseCalendar()),
            'Instantiation from vcalendar instance'
        );

        $test = new TestCalendar(self::getBaseCalendarFileContents());
        self::assertCalendarMatches(
            self::getBaseCalendar(),
            new FilteredCalendar($test),
            'Instantiation from Calendar instance'
        );
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
            self::assertCalendarMatches(
                self::getCalendar($params[self::CALENDAR]),
                new FilteredCalendar(
                    self::getCalendarFilePath(self::BASE),
                    new $class(self::SUMMARY, $params[self::KEYWORD])
                ),
                "$comparison filter"
            );
        }

        self::assertCalendarMatches(
            self::getCalendar('silly_aunt_sally'),
            new FilteredCalendar(
                self::getCalendarFilePath(self::BASE),
                new Like(self::SUMMARY, '/([a-z])\\1/i')
            ),
            'Like filter'
        );
    }

    /**
     * @throws CalendarException
     * @throws FilterException
     * @throws FilteredCalendarException
     */
    public function testLogicalOperators(): void
    {
        $params = self::getFilteredCalendar('And');
        self::assertCalendarMatches(
            self::getCalendar($params[self::CALENDAR]),
            new FilteredCalendar(
                self::getCalendarFilePath(self::BASE),
                AndOp::expr([
                    GreaterThan::expr(self::SUMMARY, $params[self::KEYWORD_A]),
                    LessThan::expr(self::SUMMARY, $params[self::KEYWORD_B])
                ])
            ),
            'Logical And filter'
        );
    }

    /**
     * @throws CalendarException
     * @throws FilterException
     * @throws FilteredCalendarException
     */
    public function testOrOperator(): void
    {
        $params = self::getFilteredCalendar('Or');
        self::assertCalendarMatches(
            self::getCalendar($params[self::CALENDAR]),
            new FilteredCalendar(
                self::getCalendarFilePath(self::BASE),
                OrOp::expr([
                    LessThan::expr(self::SUMMARY, $params[self::KEYWORD_A]),
                    GreaterThan::expr(self::SUMMARY, $params[self::KEYWORD_B])
                ])
            ),
            'Logical Or filter'
        );
    }

    /**
     * @throws CalendarException
     * @throws FilterException
     * @throws FilteredCalendarException
     */
    public function testNotOperator(): void
    {
        $params = self::getFilteredCalendar('Not');
        self::assertCalendarMatches(
            self::getCalendar($params[self::CALENDAR]),
            new FilteredCalendar(
                self::getCalendarFilePath(self::BASE),
                NotOp::expr(Equals::expr(self::SUMMARY, $params[self::KEYWORD_A]))
            ),
            'Logical Not filter'
        );
    }

    /**
     * @throws CalendarException
     * @throws FilterException
     * @throws FilteredCalendarException
     */
    public function testRegexReplaceFilter(): void
    {
        self::assertCalendarMatches(
            self::getCalendar('transform_RegexReplace'),
            new FilteredCalendar(
                self::getCalendarFilePath(self::BASE),
                [],
                new RegexReplace(
                    self::DESCRIPTION,
                    '/[aeiou]/i',
                    '_'
                )
            ),
            'RegexReplace transformation'
        );
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     * @throws Exception
     */
    public function testReplaceFilter(): void
    {
        self::assertCalendarMatches(
            self::getCalendar('transform_Replace'),
            new FilteredCalendar(
                self::getCalendarFilePath(self::BASE),
                [],
                new Replace(self::DESCRIPTION, ' ', '-')
            ),
            'Replace transformation'
        );
    }

    /**
     * @throws CalendarException
     * @throws FilteredCalendarException
     * @throws Exception
     */
    public function testRenderMarkdownFilter(): void
    {
        self::assertCalendarMatches(
            self::getCalendar('transform_Markdown'),
            new FilteredCalendar(
                self::getCalendar(self::BASE),
                [],
                new RenderMarkdown(self::DESCRIPTION)
            ),
            'Render Markdown transformation'
        );
    }
}
