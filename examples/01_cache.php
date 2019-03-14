<?php

use Battis\IcsMunger\IcsMungerException;
use Battis\IcsMunger\RetainHistory\RetainCalendarHistory;

require_once __DIR__ . '/../vendor/autoload.php';

echo PHP_EOL;

try {
    $c = json_decode(__DIR__ . '/config.json');
    $pdo = new PDO("mysql:host={$c->pdo->host};port={$c->pdo->port};dbname={$c->pdo->database}", $c->pdo->user, $c->pdo->password);

// clear old cached data
    if ($result = $pdo->query("SELECT * FROM `calendars` WHERE `name` = '{$c->source->name}'")) {
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
    foreach (scandir($dir = __DIR__ . '/..' . $c->source->path) as $file) {
        if (preg_match("/.*{$c->source->name}\.ics$/", $file)) {
            echo 'Parsing ' . realpath("$dir/$file") . PHP_EOL;
            $calendar = new RetainCalendarHistory("$dir/$file", $pdo, $c->source->name);
            echo '        ID: ' . $calendar->getId() . PHP_EOL;
            echo '      Name: ' . $calendar->getName() . PHP_EOL;
            echo 'Components: ' . $calendar->countComponents() . PHP_EOL . PHP_EOL;
        }
    }
} catch (IcsMungerException $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
