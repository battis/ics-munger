<?php

use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\vevent;
use kigkonsult\iCalcreator\vtimezone;

require_once __DIR__ . '/../../vendor/autoload.php';

define('START_YEAR', 2015);
define('END_YEAR', 2025);
define('SNAPSHOTS', 3);

define('SNAPSHOT_MODE_SIMPLE', 0);
define('SNAPSHOT_MODE_RANDOM_DELETE', 1);
define('SNAPSHOT_MODE_RANDOM_DELETE_AND_RENAME', 2);
define('SNAPSHOT_MODES', 3);

function createBaseCalendar(): vcalendar
{
    echo 'Initializing base calendar' . PHP_EOL;
    $base = new vcalendar([
        'unique_id' => 'battis/ics-munger'
    ]);
    $base->setCalscale('GREGORIAN');
    $base->setMethod('PUBLISH');

    $tz = new vtimezone();
    $tz->parse('BEGIN:VTIMEZONE
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
');
    $base->addComponent($tz);

    $base->setConfig([
        'directory' => __DIR__ . '/calendars',
        'filename' => 'base.ics'
    ]);
    return $base;
}

function createSnapshotSet(vcalendar $base): array
{
    $snapshot = [];
    for ($i = 0; $i < SNAPSHOTS; $i++) {
        $snapshot[$i] = clone $base;
    }
    return $snapshot;
}

function generateRandomEvents(vcalendar &$baseCalendar)
{
    echo 'Generating random events between 1/1/' . START_YEAR . ' and 12/31/' . END_YEAR . PHP_EOL;
    $counter = 0;
    $first = true;
    $lastday = $lastmonth = $lastyear = 0;
    for ($year = START_YEAR; $year <= END_YEAR; $year++) {
        $lastyear = $year;
        for ($month = 1; $month <= 12; $month++) {
            $lastmonth = $month;
            for ($day = rand(1, 7); $day <= ($month < 8 && $month % 2 === 1 || $month > 7 && $month % 2 === 0 ? 31 : ($month !== 2 ? 30 : ($year % 4 === 0 ? 29 : 28))); $day += rand(1, 7)) {
                $lastday = $day;
                if ($first) {
                    $first = false;
                    echo "  $month/$day/$year thru ";
                }
                $e = new vevent();
                $e->setSummary(random_factor());
                $e->setDtstart($year, $month, $day, rand(0, 23), rand(0, 59));
                $e->setDuration(0, 0, rand(0, 3), rand(1, 45));
                $baseCalendar->addComponent($e);
                $counter++;
            }
        }
    }
    echo "$lastmonth/$lastday/$lastyear ($counter events)" . PHP_EOL;

    echo '  Saving base calendar file' . PHP_EOL;
    $baseCalendar->saveCalendar();
}

function saveSnapshots(vcalendar $baseCalendar, array $snapshotSet, int $mode = SNAPSHOT_MODE_SIMPLE): void
{
    echo 'Snapshotting overlapping windows from base calendar ';
    switch ($mode) {
        case SNAPSHOT_MODE_RANDOM_DELETE_AND_RENAME:
            echo 'with random deletions and modifications';
            break;
        case SNAPSHOT_MODE_RANDOM_DELETE:
            echo 'with random deletions';
            break;
        case SNAPSHOT_MODE_SIMPLE:
        default:
            // do nothing
    }
    echo PHP_EOL;
    $window = intdiv(END_YEAR - START_YEAR, SNAPSHOTS - 1);
    $step = intdiv(END_YEAR - START_YEAR, SNAPSHOTS + 1);
    for ($i = 0; $i < SNAPSHOTS; $i++) {
        $start = START_YEAR + ($i * $step);
        $end = START_YEAR + ($i * $step) + $window + 1;
        $counter = 0;
        echo "  1/1/$start thru 12/31/$end ";
        $baseCalendar->getComponent(0); // reset internal component counter
        if (($selection = $baseCalendar->selectComponents($start, 1, 1, $end, 12, 31)) !== false) {
            foreach ($selection as $year) {
                foreach ($year as $month) {
                    foreach ($month as $day) {
                        foreach ($day as $e) {
                            switch ($mode) {
                                case SNAPSHOT_MODE_RANDOM_DELETE_AND_RENAME:
                                    $include = rand(1, 10) !== 7;
                                    if (rand(1, 10) === 4) {
                                        $e->setSummary(random_factor());
                                    }
                                    break;
                                case SNAPSHOT_MODE_RANDOM_DELETE:
                                    $include = rand(1, 5) !== 3;
                                    break;
                                case SNAPSHOT_MODE_SIMPLE:
                                default:
                                    $include = true;
                            }
                            if ($include) {
                                $snapshotSet[$i]->addComponent($e);
                                $counter++;
                            }
                        }
                    }
                }
            }
        }
        echo "($counter events selected)" . PHP_EOL;
    }

    echo '  Saving snapshot calendar files' . PHP_EOL;
    for ($i = 0; $i < count($snapshotSet); $i++) {
        switch ($mode) {
            case SNAPSHOT_MODE_RANDOM_DELETE_AND_RENAME:
                $filename = "snapshot_randomRename_$i.ics";
                break;
            case SNAPSHOT_MODE_RANDOM_DELETE:
                $filename = "snapshot_randomFilter_$i.ics";
                break;
            case SNAPSHOT_MODE_SIMPLE:
            default:
                $filename = "snapshot_$i.ics";
        }
        echo "    Saving $filename" . PHP_EOL;
        $snapshotSet[$i]->setConfig(['filename' => $filename]);
        $snapshotSet[$i]->saveCalendar();
    }
}

$base = createBaseCalendar();
$snapshotSets = [];
for ($i = 0; $i < SNAPSHOT_MODES; $i++) {
    $snapshotSets[$i] = createSnapshotSet($base);
}
generateRandomEvents($base);
for ($i = 0; $i < SNAPSHOT_MODES; $i++) {
    saveSnapshots($base, $snapshotSets[$i], $i);
}

echo 'Done' . PHP_EOL;
