<?php

use Battis\IcsMunger\Tests\data\CalendarGenerator;
use kigkonsult\iCalcreator\vevent;

require_once __DIR__ . '/../../vendor/autoload.php';

$calendarDir = __DIR__ . DIRECTORY_SEPARATOR . 'calendars';
$base = new CalendarGenerator();
$base->save($calendarDir, 'base.ics');
$snapshots = $base->snapshots(3);
$i = 0;
foreach ($snapshots as $snapshot) {
    $snapshot->save($calendarDir, "snapshot_$i.ics");
    $filtered = $snapshot->filter(function (vevent $e): bool {
        return rand(1, 10) != 3;
    });
    $filtered->save($calendarDir, "snapshot_randomFilter_$i.ics");
    $transformed = $snapshot->transform(function (vevent $e): vevent {
        if (rand(1, 5) == 3) {
            $e->setSummary(random_factor());
        }
        return $e;
    });
    $transformed = $transformed->filter(function (vevent $e): bool {
        return rand(1, 30) != 17;
    });
    $transformed->save($calendarDir, "snapshot_randomRename_$i.ics");
}
$base->filter(function (vevent $e): bool {
    return preg_match('/([a-z])\\1/i', $e->getProperty('summary')) === 1;
})->save($calendarDir, 'silly_aunt_sally.ics');
$base->filter(function (vevent $e): bool {
    return $e->getProperty('summary') > 'lemon';
})->save($calendarDir, 'after_lemon.ics');
$base->filter(function (vevent $e): bool {
    return $e->getProperty('summary') < 'mongoose';
})->save($calendarDir, 'before_mongoose.ics');
