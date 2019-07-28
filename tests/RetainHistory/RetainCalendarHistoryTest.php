<?php


namespace Battis\IcsMunger\Tests\RetainHistory;


use Battis\IcsMunger\IcsMungerException;
use Battis\IcsMunger\PersistentCalendar\Calendar;
use Battis\IcsMunger\PersistentCalendar\CalendarException;
use Battis\IcsMunger\RetainHistory\RetainCalendarHistory;
use Battis\IcsMunger\Tests\Calendar\AbstractPersistentCalendarTestCase;
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
        self::$calendarType = RetainCalendarHistory::class;

        parent::setUp();

        $this->resetPersistence();
    }

    /**
     * @param Calendar $calendar
     * @param string $message
     * @throws CalendarException
     * @throws Exception
     */
    protected function validateInstantiation(Calendar $calendar, string $message = ''): void
    {
        self::assertCalendarMatches(self::getBaseCalendar(), $calendar, $message);
        self::assertInstanceOf(RetainCalendarHistory::class, $calendar);
        if ($calendar instanceof RetainCalendarHistory) {
            self::assertCalendarCached($calendar, $message);
        }
        $this->resetPersistence();
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiation(...$params): Calendar
    {
        parent::testInstantiation(self::getDatabase(), __FUNCTION__, ...$params);

        $this->validateInstantiation(
            new self::$calendarType(
                new RetainCalendarHistory(
                    self::getCalendarFilePath(self::BASE),
                    self::getDatabase(),
                    'RetainCalendarHistory without database'
                ),
                ...$params
            ),
            'Instantiation from RetainCalendarHistory instance without database'
        );

        // TODO instantiation as copy of full RetainCalendarHistory
        self::assertTrue(true, 'Instantiation from RetainCalendarHistory instance with database');

        return new self::$calendarType(self::getBaseCalendar(), self::getDatabase(), __FUNCTION__, ...$params);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testOverlappingSnapshots(): void
    {
        $calendar = null;
        for ($i = 0; $i < 3; $i++) {
            $calendar = new self::$calendarType(self::getCalendarFilePath("snapshot_$i"), self::getDatabase(), __FUNCTION__);
            self::assertCalendarCached($calendar);
        }
        $this->validateInstantiation($calendar, 'Calendar of retained overlapping snapshots');
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

    /**
     * @param mixed ...$params
     * @throws Exception
     */
    public function testInstantiationFromInvalidData(...$params): void
    {
        parent::testInstantiationFromInvalidData(self::getDatabase(), ...$params);
    }

    public function testInstantiationFromNull(...$params): void
    {
        parent::testInstantiationFromNull(self::getDatabase(), ...$params);
    }

    /**
     * @throws Exception
     */
    protected function resetPersistence(): void
    {
        self::getDatabase()->query('TRUNCATE TABLE `calendars`');
        self::getDatabase()->query('TRUNCATE TABLE `events`');
        self::getDatabase()->query('TRUNCATE TABLE `syncs');
    }
}
