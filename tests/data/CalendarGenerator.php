<?php


namespace Battis\IcsMunger\Tests\data;


use DateInterval;
use DateTime;
use Exception;
use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\vevent;
use kigkonsult\iCalcreator\vtimezone;

class CalendarGenerator
{
    const DEFAULT_EVENT_COUNT = 1000;
    const START_RELATIVE = '-5 years';
    const END_RELATIVE = '+5 years';

    private static $US_EASTERN_TIMEZONE = <<<EOT
BEGIN:VTIMEZONE
TZID:America/New_York
X-LIC-LOCATION:America/New_York
BEGIN:STANDARD
DTSTART:20191103T020000
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11
TZNAME:Eastern Standard Time
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:20190310T020000
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3
TZNAME:Eastern Daylight Time
END:DAYLIGHT
END:VTIMEZONE
EOT;

    /**
     * @var string
     */
    private static $calendarDirectory = null;

    /**
     * @var vcalendar
     */
    private $base;

    /**
     * @var DateTime
     */
    private $start;

    /**
     * @var DateTime
     */
    private $end;

    /**
     * @var string[]
     */
    private $summaries = [];

    /**
     * @var string[]
     */
    private $descriptions = [];

    /**
     * CalendarGenerator constructor.
     * @param DateTime|null $start
     * @param DateTime|null $end
     * @param int $eventCount
     * @throws Exception
     */
    public function __construct(DateTime $start = null, DateTime $end = null, int $eventCount = self::DEFAULT_EVENT_COUNT)
    {
        $this->base = self::getEmptyCalendar();
        if ($start === null) $start = new DateTime('-5 years');
        if ($end === null) $end = new DateTime('+5 years');
        if ($eventCount < 0) $eventCount = 0;
        $this->generateRandomEvents($start, $end, $eventCount);
    }

    private static function getEmptyCalendar(): vcalendar
    {
        $c = new vcalendar(['unique_id' => __CLASS__]);
        $c->setCalscale('GREGORIAN');
        $c->setMethod('PUBLISH');
        $c->setProperty('X-WR-CALNAME', 'ICS Munger Test Calenadar');
        $tz = new vtimezone();
        $tz->parse(self::$US_EASTERN_TIMEZONE);
        $c->addComponent($tz);
        return $c;
    }

    private function generateRandomEvents(DateTime $start, DateTime $end, int $eventCount): void
    {
        $this->start = $start;
        $this->end = $end;
        $start = $start->getTimestamp();
        $end = $end->getTimestamp();
        for ($i = 0; $i < $eventCount; $i++) {
            $e = new vevent();
            $summary = random_factor();
            $description = random_factor();
            $e->setSummary($summary);
            $e->setDescription($description);
            $e->setDtstart(date('c', rand($start, $end)));
            $e->setDuration(0, 0, rand(0, 3), rand(1, 45));
            $this->base->addComponent($e);
            array_push($this->summaries, $summary);
            array_push($this->descriptions, $description);
        }
    }

    public function setCalendarDirectory(string $directory): void
    {
        self::$calendarDirectory = $directory;
    }

    /**
     * @throws Exception
     */
    public function emptyCalendarDirectory(): void
    {
        if (self::$calendarDirectory === null) throw new Exception('Must set directory');
        foreach (scandir(self::$calendarDirectory) as $file) {
            if (is_file(self::$calendarDirectory . DIRECTORY_SEPARATOR . $file)) {
                unlink(self::$calendarDirectory . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

    /**
     * @param string $filename
     * @throws Exception
     */
    public function save(string $filename): void
    {
        if (self::$calendarDirectory === null) throw new Exception('Must set directory');
        if (!strpos($filename, '.ics')) $filename .= '.ics';
        $this->base->setConfig('directory', self::$calendarDirectory);
        $this->base->setConfig('filename', $filename);
        $this->base->saveCalendar();
    }

    /**
     * @param callable $filter callable(vevent): bool
     * @return CalendarGenerator
     * @throws Exception
     */
    public function filter(callable $filter): CalendarGenerator
    {
        $result = new CalendarGenerator(null, null, 0);
        $this->base->getComponent(0);
        while ($e = $this->base->getComponent('vevent')) {
            if ($filter($e)) {
                $result->base->addComponent($e);
            }
        }
        return $result;
    }

    /**
     * @param callable $transform callable(vevent): vevent
     * @return CalendarGenerator
     * @throws Exception
     */
    public function transform(callable $transform): CalendarGenerator
    {
        $result = new CalendarGenerator(null, null, 0);
        $this->base->getComponent(0);
        while ($e = $this->base->getComponent('vevent')) {
            $result->base->addComponent($transform($e));
        }
        return $result;
    }

    /**
     * @param int $count
     * @param float $overlapPercentage
     * @return CalendarGenerator[][]
     * @throws Exception
     */
    public function snapshots(int $count, float $overlapPercentage = 0.9): array
    {
        $result = [];
        $window = (int)(($this->end->getTimestamp() - $this->start->getTimestamp()) / ($overlapPercentage + (($count - 1) * (1 - $overlapPercentage))));
        $step = (int)((1 - $overlapPercentage) * $window);
        $start = $this->start;
        for ($i = 0; $i < $count; $i++) {
            $snapshot = new CalendarGenerator(null, null, 0);
            $end = clone $start;
            $end->add($this->interval($window));
            $this->base->getComponent(0);
            foreach ($this->base->selectComponents($start, $end, null, null, null, null, 'vevent', true, true, false) as $e) {
                $snapshot->base->addComponent($e);
            }
            array_push($result, $snapshot);
            $start->add($this->interval($step));
        }
        return $result;
    }

    /**
     * @param int $seconds
     * @return DateInterval
     * @throws Exception
     */
    private function interval(int $seconds): DateInterval
    {
        $years = (int)($seconds / (60 * 60 * 24 * 365));
        if ($years) $seconds = $seconds % ($years * 60 * 60 * 24 * 365);
        $days = (int)($seconds / (60 * 60 * 24));
        if ($days) $seconds = $seconds % ($days * 60 * 60 * 24);
        $hours = (int)($seconds / (60 * 60));
        if ($hours) $seconds = $seconds % ($hours * 60 * 60);
        $minutes = (int)($seconds / 60);
        if ($minutes) $seconds = $seconds % ($minutes * 60);
        return new DateInterval("P{$years}Y{$days}DT{$hours}H{$minutes}M{$seconds}S");
    }

    public function getRandomSummary(): string
    {
        return $this->summaries[rand(0, count($this->summaries) - 1)];
    }

    public function getRandomDescription(): string
    {
        return $this->descriptions[rand(0, count($this->descriptions) - 1)];
    }
}
