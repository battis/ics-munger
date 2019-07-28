<?php

use Battis\Calendar\Components\Event;
use Battis\Calendar\Properties\Component\Descriptive\Location;
use Battis\Calendar\Properties\Component\Descriptive\Summary;
use Battis\Calendar\Values\Text;
use Battis\Calendar\Workflows\iCalendar;
use Battis\IcsMunger\Filtered\FilteredCalendar;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::create(__DIR__ . '/..')->load();

$source = __DIR__ . '/' . getenv('SOURCE_NAME') . ' - consolidated.ics';
if (!file_exists($source)) {
    require_once __DIR__ . '/02_consolidate.php';
}
$source = realpath($source);

try {
    echo "Parsing $source" . PHP_EOL;
    /** @var FilteredCalendar $calendar */
    $calendar = iCalendar::parseFile(
        $source,
        FilteredCalendar::class,
        function (Event $e): bool {
            if (($summary = $e->getProperty(Summary::class)) !== null) {
                return strpos($summary->getValue(), 'CSI:') !== false && strpos($summary->getValue(), 'FSO') === false;
            }
            return false;
        },
        function (Event $e): Event {
            if (($summary = $e->getProperty(Summary::class)) !== null) {
                preg_match('/.*(\([C-J] Block\)).*/', $summary->getValue(), $matches);
                $e->setProperty($summary, new Summary([], new Text('CS I ' . $matches[1])));
                switch ($matches[1]) {
                    case '(D Block)':
                        $e->setProperty(Location::class, new Location([], new Text('Room L005')));
                        break;
                    case '(G Block)':
                        $e->setProperty(Location::class, new Location([], new Text('Room 105')));
                        break;
                }
            }
            return $e;
        }
    );
    echo '    Events: ' . count($calendar->getAllEvents()) . PHP_EOL . PHP_EOL;


    $filename = getenv('SOURCE_NAME') . ' - CS I.ics';
    echo "Filtering to $filename" . PHP_EOL;
    $calendar->apply();

    echo '    Events: ' . count($calendar->getAllEvents()) . PHP_EOL . PHP_EOL;
    iCalendar::exportToFile($calendar, __DIR__ . '/' . $filename);
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
