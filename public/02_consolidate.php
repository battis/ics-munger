<?php

use Battis\Calendar\Workflows\iCalendar;
use Battis\IcsMunger\ConsolidateRecurrences\ConsolidateRecurrencesCalendar;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::create(__DIR__ . '/..')->load();

$source = __DIR__ . '/' . getenv('SOURCE_NAME') . '.ics';
if (!file_exists($source)) {
    require_once __DIR__ . '/01_cache.php';
}
$source = realpath($source);

try {
    // consolidate
    echo "Parsing $source" . PHP_EOL;
    /** @var ConsolidateRecurrencesCalendar $calendar */
    $calendar = iCalendar::parseFile($source, ConsolidateRecurrencesCalendar::class);
    echo '    Events: ' . count($calendar->getAllEvents()) . PHP_EOL . PHP_EOL;

    $filename = getenv('SOURCE_NAME') . ' - consolidated.ics';
    echo "Consolidating to $filename" . PHP_EOL;
    $calendar->consolidate();
    echo '    Events: ' . count($calendar->getAllEvents()) . PHP_EOL . PHP_EOL;
    iCalendar::exportToFile($calendar, __DIR__ . '/' . $filename);

} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
