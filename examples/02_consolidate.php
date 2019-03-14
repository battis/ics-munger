<?php

use Battis\IcsMunger\ConsolidateRecurrences\ConsolidateRecurrencesCalendar;
use Battis\IcsMunger\IcsMungerException;

require_once '01_cache.php';

try {
    // consolidate
    echo 'Saving ' . realpath(__DIR__ . "/{$c->source->name}.ics") . PHP_EOL;
    $calendar = new ConsolidateRecurrencesCalendar($calendar);
    echo 'Components: ' . $calendar->countComponents() . PHP_EOL . PHP_EOL;
    $calendar->setConfig([
        'directory' => __DIR__,
        'filename' => "{$c->source->name}.ics"
    ]);
    $calendar->saveCalendar();

} catch (IcsMungerException $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
