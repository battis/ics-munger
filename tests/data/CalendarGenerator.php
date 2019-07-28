<?php


namespace Battis\IcsMunger\Tests\data;


use BlogArticleFaker\FakerProvider as BlogArticleFakerProvider;
use DateTime;
use Exception;
use Faker\Factory;
use Faker\Generator;
use joshtronic\LoremIpsum;
use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\vevent;
use kigkonsult\iCalcreator\vtimezone;

class CalendarGenerator extends vcalendar
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

    /** @var LoremIpsum */
    private static $loremIpsum = null;

    private static $faker = null;

    /**
     * CalendarGenerator constructor.
     * @param DateTime|null|string $start
     * @param DateTime|null $end
     * @param int $eventCount
     * @throws Exception
     */
    public function __construct($start = null, DateTime $end = null, int $eventCount = self::DEFAULT_EVENT_COUNT)
    {
        parent::__construct(['unique_id' => __CLASS__]);
        if (is_string($start) && realpath($start)) {
            $this->parse(file_get_contents(realpath($start)));
        } else {
            $this->initializeEmptyCalendar();
            if ($start === null) $start = new DateTime('-5 years');
            if ($end === null) $end = new DateTime('+5 years');
            if ($eventCount < 0) $eventCount = 0;
            $this->generateRandomEvents($start, $end, $eventCount);
        }
    }

    private function initializeEmptyCalendar()
    {
        $this->setCalscale('GREGORIAN');
        $this->setMethod('PUBLISH');
        $this->setProperty('X-WR-CALNAME', 'ICS Munger Test Calendar');
        $tz = new vtimezone();
        $tz->parse(self::$US_EASTERN_TIMEZONE);
        $this->addComponent($tz);
    }

    protected function generateRandomSummary(): string
    {
        $result = random_factor();
        return $result;
    }

    protected static function getLoremIpsum(): LoremIpsum
    {
        if (self::$loremIpsum === null) {
            self::$loremIpsum = new LoremIpsum();
        }
        return self::$loremIpsum;
    }

    protected static function getFaker(): Generator
    {
        if (self::$faker === null) {
            self::$faker = Factory::create();
            self::$faker->addProvider(new BlogArticleFakerProvider(self::$faker));
        }
        return self::$faker;
    }

    protected function generateRandomDescription(): string
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
        return $result;
    }

    private function generateRandomEvents(DateTime $start, DateTime $end, int $eventCount): void
    {
        $start = $start->getTimestamp();
        $end = $end->getTimestamp();
        for ($i = 0; $i < $eventCount; $i++) {
            $e = new vevent();
            $e->setSummary($this->generateRandomSummary());
            $e->setDescription($this->generateRandomDescription());
            $e->setDtstart(date('c', rand($start, $end)));
            $e->setDuration(0, 0, rand(0, 3), rand(1, 45));
            $this->addComponent($e);
        }
    }
}
