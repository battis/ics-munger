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
    const BASE = 'base';
    const CALENDARS_TABLE = 'calendars';
    const EVENTS_TABLE = 'events';

    /**
     * @param RetainCalendarHistory $c
     * @throws Exception
     */
    private function instantiationTests(RetainCalendarHistory $c): void
    {
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
        self::assertCalendarCached($c);
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
    public function testInstantiationFromTextData(): void
    {
        $c = new RetainCalendarHistory(self::getBaseCalendarFileContents(), self::getDatabase(), __FUNCTION__);
        $this->instantiationTests($c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromFilePath(): void
    {
        $c = new RetainCalendarHistory(self::getCalendarFilePath(self::BASE), self::getDatabase(), __FUNCTION__);
        $this->instantiationTests($c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromUrl(): void
    {
        $c = new RetainCalendarHistory(self::getCalendarUrl(self::BASE), self::getDatabase(), __FUNCTION__);
        $this->instantiationTests($c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromVcalendar(): void
    {
        $c = new RetainCalendarHistory(
            self::getCalendarFilePath(self::BASE),
            self::getDatabase(),
            __FUNCTION__
        );
        $this->instantiationTests($c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromCalendar(): void
    {
        $c = new RetainCalendarHistory(
            new TestCalendar(self::getCalendarFilePath(self::BASE)),
            self::getDatabase(),
            __FUNCTION__
        );
        $this->instantiationTests($c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromRetainCalendarHistoryWithoutDb(): void
    {
        $c = new RetainCalendarHistory(
            new RetainCalendarHistory(
                self::getCalendarFilePath(self::BASE),
                self::getDatabase(),
                __FUNCTION__
            )
        );
        $this->instantiationTests($c);
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
        $c->reset();
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
