<?php


namespace Battis\IcsMunger\Tests\data;


use BlogArticleFaker\FakerProvider as BlogArticleFakerProvider;
use DateInterval;
use DateTime;
use Exception;
use Faker\Factory;
use Faker\Generator;
use joshtronic\LoremIpsum;
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

    /** @var string */
    private static $calendarDirectory = null;

    /** @var LoremIpsum */
    private static $loremIpsum = null;

    private static $faker = null;

    /** @var vcalendar */
    private $base;

    /** @var DateTime */
    private $start;

    /** @var DateTime */
    private $end;

    /** @var string[] */
    private $summaries = [];

    /** @var string[] */
    private $descriptions = [];

    /**
     * CalendarGenerator constructor.
     * @param DateTime|null|string $start
     * @param DateTime|null $end
     * @param int $eventCount
     * @throws Exception
     */
    public function __construct($start = null, DateTime $end = null, int $eventCount = self::DEFAULT_EVENT_COUNT)
    {
        if (is_string($start) && realpath($start)) {
            $this->base = new vcalendar();
            $this->base->parse(file_get_contents(realpath($start)));
            // FIXME set start and end fields
        } else {
            $this->base = self::getEmptyCalendar();
            if ($start === null) $start = new DateTime('-5 years');
            if ($end === null) $end = new DateTime('+5 years');
            if ($eventCount < 0) $eventCount = 0;
            $this->generateRandomEvents($start, $end, $eventCount);
        }
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

    private function generateRandomSummary(): string
    {
        $result = random_factor();
        array_push($this->summaries, $result);
        return $result;
    }

    private static function getLoremIpsum(): LoremIpsum
    {
        if (self::$loremIpsum === null) {
            self::$loremIpsum = new LoremIpsum();
        }
        return self::$loremIpsum;
    }

    private static function getFaker(): Generator
    {
        if (self::$faker === null) {
            self::$faker = Factory::create();
            self::$faker->addProvider(new BlogArticleFakerProvider(self::$faker));
        }
        return self::$faker;
    }

    private function generateRandomDescription(): string
    {
        $result = '';
        switch (rand(1, 5)) {
            case 1:
            case 2:
            case 3: // plaintext
                $result = self::getLoremIpsum()->words(rand(20, 100));
                break;
            case 4: // html
                switch (rand(1, 4)) {
                    case 1: // tagged paragraphs
                        $result = self::getLoremIpsum()->paragraphs(rand(1, 3), 'p');
                        break;
                    case 2: // unordered list
                        $result = '<ul>' . self::getLoremIpsum()->words(rand(3, 10), '<li>$1</li>') . '</ul>';
                        break;
                    case 3: // ordered list of links
                        $result = '<ol>' . self::getLoremIpsum()->words(rand(3, 10), '<li><a href="$1">$1</a></li>') . '</ol>';
                        break;
                    case 4: // Wordpress-style markup (no p tags, but double line breaks and formatting)
                        $text = self::getLoremIpsum()->paragraphs(rand(1, 3));
                        $paragraphs = explode("\n", $text);
                        foreach ($paragraphs as $i => $paragraph) {
                            $words = explode(' ', $paragraph);
                            foreach ($words as $j => $word) {
                                switch (rand(1, 10)) {
                                    case 3:
                                        $words[$j] = "<b>$word</b>";
                                        break;
                                    case 7:
                                        $words[$j] = "<i>$word</i>";
                                        break;
                                }
                            }
                            $paragraphs[$i] = implode(' ', $words);
                        }
                        $result = implode("\n\n", $paragraphs);
                        break;
                }
                break;
            case 5: // markdown
                $result = self::getFaker()->articleContentMarkdown();
                break;
        }
        array_push($this->descriptions, $result);
        return $result;
    }

    private function generateRandomEvents(DateTime $start, DateTime $end, int $eventCount): void
    {
        $this->start = $start;
        $this->end = $end;
        $start = $start->getTimestamp();
        $end = $end->getTimestamp();
        for ($i = 0; $i < $eventCount; $i++) {
            $e = new vevent();
            $e->setSummary($this->generateRandomSummary());
            $e->setDescription($this->generateRandomDescription());
            $e->setDtstart(date('c', rand($start, $end)));
            $e->setDuration(0, 0, rand(0, 3), rand(1, 45));
            $this->base->addComponent($e);
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
