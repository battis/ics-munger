<?php


namespace Battis\IcsMunger\Tests\RetainHistory;


use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\IcsMungerException;
use Battis\IcsMunger\RetainHistory\RetainCalendarHistory;
use Battis\IcsMunger\Tests\Calendar\AbstractPersistentCalendarTestCase;
use Battis\IcsMunger\Tests\TestCalendar;
use Exception;

class RetainCalendarHistoryTest extends AbstractPersistentCalendarTestCase
{
    const CALENDARS_TABLE = 'calendars';
    const EVENTS_TABLE = 'events';
    const SYNCS_TABLE = 'syncs';

    /**
     * @throws Exception
     */
    public function setUp()
    {
        parent::setUp();
        self::getDatabase()->query('TRUNCATE TABLE `calendars`');
        self::getDatabase()->query('TRUNCATE TABLE `events`');
        self::getDatabase()->query('TRUNCATE TABLE `syncs');
    }

    /**
     * @param RetainCalendarHistory $c
     * @throws Exception
     */
    private function instantiationTests(RetainCalendarHistory $c, string $message = ''): void
    {
        self::assertCalendarMatches(self::getBaseCalendar(), $c, $message);
        self::assertCalendarCached($c, $message);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromNullData(): void
    {
        $this->expectException(CalendarException::class);
        new RetainCalendarHistory(null, self::getDatabase(), __FUNCTION__);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromInvalidData(): void
    {
        $this->expectException(CalendarException::class);
        new RetainCalendarHistory(123, self::getDatabase(), __FUNCTION__);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiation(): void
    {
        $this->instantiationTests(
            new RetainCalendarHistory(
                self::getBaseCalendarFileContents(),
                self::getDatabase(),
                "Text data"
            ),
            'Instantiation from text data'
        );

        $this->instantiationTests(
            new RetainCalendarHistory(
                self::getCalendarFilePath(self::BASE),
                self::getDatabase(),
                "File path"
            ),
            'Instantiation from file path'
        );

        $this->instantiationTests(
            new RetainCalendarHistory(
                self::getCalendarUrl(self::BASE),
                self::getDatabase(),
                'URL'
            ),
            'Instantiation from URL'
        );

        $this->instantiationTests(
            new RetainCalendarHistory(
                [
                    'unique_id' => __CLASS__,
                    'url' => self::getCalendarUrl(self::BASE)
                ],
                self::getDatabase(),
                'Configuration array'
            ),
            'Instantiation from configuration array'
        );

        $this->instantiationTests(
            new RetainCalendarHistory(
                self::getBaseCalendar(),
                self::getDatabase(),
                'vcalendar'
            ),
            'Instantiation from vcalendar instance'
        );

        $this->instantiationTests(
            new RetainCalendarHistory(
                new TestCalendar(self::getCalendarFilePath(self::BASE)),
                self::getDatabase(),
                'Calendar'
            ),
            'Instantiation from Calendar instance'
        );

        $this->instantiationTests(
            new RetainCalendarHistory(
                new RetainCalendarHistory(
                    self::getCalendarFilePath(self::BASE),
                    self::getDatabase(),
                    'RetainCalendarHistory without database'
                )
            ),
            'Instantiation from RetainCalendarHistory instance'
        );
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testOverlappingSnapshots(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $c = new RetainCalendarHistory(self::getCalendarFilePath("snapshot_$i"), self::getDatabase(), __FUNCTION__);
            self::assertCalendarCached($c);
        }
        $this->instantiationTests($c);
    }

    protected function loadFixture()
    {
    }

    protected function unloadFixture()
    {
    }

    /**
     * @param RetainCalendarHistory $c
     * @param string $message
     * @throws CalendarException
     * @throws Exception
     */
    public static function assertCalendarCached(RetainCalendarHistory $c, string $message = ''): void
    {
        self::assertRowExists(
            [
                'name' => $c->getName()
            ],
            self::CALENDARS_TABLE,
            $message
        );
        for ($count = 0; $e = $c->getEvent(); $count++) {
            self::assertRowExists(
                [
                    'calendar' => $c->getId(),
                    'uid' => $e->getUid(),
                    'vevent' => $e->createComponent()
                ],
                self::EVENTS_TABLE,
                $message
            );
        }
        self::assertQueryRowCount(
            $c->countComponents() - 1, // vtimezone not cached
            'SELECT * FROM `events` WHERE `calendar` = :calendar',
            ['calendar' => $c->getId()],
            $message
        );
    }
}
