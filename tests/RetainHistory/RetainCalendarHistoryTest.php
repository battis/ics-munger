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

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromNullData(): void
    {
        $this->expectException(CalendarException::class);
        $c = new RetainCalendarHistory(null, self::getDatabase(), __FUNCTION__);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromInvalidData(): void
    {
        $this->expectException(CalendarException::class);
        $c = new RetainCalendarHistory(123, self::getDatabase(), __FUNCTION__);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromTextData(): void
    {
        $c = new RetainCalendarHistory(self::getBaseCalendarFileContents(), self::getDatabase(), __FUNCTION__);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromFilePath(): void
    {
        $c = new RetainCalendarHistory(self::getCalendarFilePath('base'), self::getDatabase(), __FUNCTION__);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromUrl(): void
    {
        $c = new RetainCalendarHistory(self::getCalendarUrl('base'), self::getDatabase(), __FUNCTION__);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromVcalendar(): void
    {
        $c = new RetainCalendarHistory(self::getBaseCalendar(), self::getDatabase(), __FUNCTION__);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromCalendar(): void
    {
        $test = new TestCalendar(self::getBaseCalendar());
        $c = new RetainCalendarHistory($test, self::getDatabase(), __FUNCTION__);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    /**
     * @throws IcsMungerException
     * @throws Exception
     */
    public function testInstantiationFromRetainCalendarHistoryWithoutDb(): void
    {
        $test = new RetainCalendarHistory(self::getBaseCalendar(), self::getDatabase(), __FUNCTION__ . '-test');
        $c = new RetainCalendarHistory($test);
        self::assertCalendarMatches(self::getBaseCalendar(), $c);
    }

    protected function loadFixture()
    {
        // TODO: Implement loadFixture() method.
    }

    protected function unloadFixture()
    {
        // TODO: Implement unloadFixture() method.
    }
}
