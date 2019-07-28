<?php

use Battis\Calendar\Workflows\iCalendar;
use Battis\IcsMunger\RetainHistory\RetainCalendarHistory;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::create(__DIR__ . '/..')->load();

echo PHP_EOL;

try {
    $pdo = new PDO(
        getenv('PDO_DRIVER') .
        ':host=' . getenv('PDO_HOST') .
        ';port=' . getenv('PDO_PORT') .
        ';dbname=' . getenv('PDO_DATABASE'),
        getenv('PDO_USER'),
        getenv('PDO_PASSWORD')
    );

// clear old cached data
    if ($result = $pdo->query("SELECT * FROM `calendars` WHERE `name` = '" . getenv('SOURCE_NAME') . "'")) {
        if ($row = $result->fetch()) {
            echo 'Flushing old cached data' . PHP_EOL . PHP_EOL;
            $pdo->query("DELETE FROM `calendars` WHERE `id` = '{$row['id']}'");
            $pdo->query("DELETE FROM `events` WHERE `calendar` = '{$row['id']}'");
            $pdo->query("DELETE FROM `syncs` WHERE `calendar` = '{$row['id']}");
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}

$calendar = null;
try {
    // rebuild cache from files
    foreach (scandir($dir = __DIR__ . '/..' . getenv('SOURCE_PATH')) as $file) {
        if (preg_match('/.*' . getenv('SOURCE_PATH') . '\.ics$/', $file)) {
            echo 'Parsing ' . realpath("$dir/$file") . PHP_EOL;
            /** @var RetainCalendarHistory $calendar */
            $calendar = iCalendar::parseFile("$dir/$file", RetainCalendarHistory::class, $pdo, getenv('SOURCE_NAME'));
            $calendar->sync();
            echo '        ID: ' . $calendar->getId() . PHP_EOL;
            echo '      Name: ' . $calendar->getName() . PHP_EOL;
            echo '    Events: ' . count($calendar->getAllEvents()) . PHP_EOL . PHP_EOL;
        }
    }

    $filename = getenv('SOURCE_NAME') . '.ics';
    echo "Saving $filename" . PHP_EOL . PHP_EOL;
    iCalendar::exportToFile($calendar, $filename);
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
