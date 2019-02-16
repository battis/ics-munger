#!/usr/bin/env php
<?php

use Battis\IcsMunger\Tests\data\CalendarGenerator;
use kigkonsult\iCalcreator\vevent;
use Michelf\Markdown;

require_once __DIR__ . '/../../vendor/autoload.php';

define('EVENT_COUNT', 200);
define('SNAPSHOT_BASIC', 'basic');
define('SNAPSHOT_RANDOM_FILTER', 'filter');
define('SNAPSHOT_RANDOM_RENAME', 'rename');
define('SNAPSHOT_COUNT', 3);
define('DATE_BEGIN', '-5 years');
define('DATE_END', '+5 years');

define('SUMMARY', 'summary');
define('DESCRIPTION', 'description');

try {
    $eventCount = EVENT_COUNT;
    $generateSnapshots = [
        SNAPSHOT_BASIC => false,
        SNAPSHOT_RANDOM_FILTER => false,
        SNAPSHOT_RANDOM_RENAME => false
    ];
    $snapshotCount = SNAPSHOT_COUNT;
    $calendarDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'calendars';
    $flushDirectory = false;
    $regenerateBase = false;
    $beginDate = new DateTime(DATE_BEGIN);
    $endDate = new DateTime(DATE_END);
    $generateComparisons = false;
    $generateLogicalExpressions = false;
    $generateTransformations = false;

    foreach ($argv as $i => $argument) {
        switch ($argument) {
            case '--help':
            case '-h':
                echo
                    'Usage' . PHP_EOL .
                    '  -r | --regenerate' . PHP_EOL .
                    '      Regenerate the base calendar (defaults to reloading, if possible)' . PHP_EOL .
                    '  -f | --flush' . PHP_EOL .
                    '      Flush the current calendar directory contents before saving output' . PHP_EOL .
                    '      calendars (defaults to not flushing)' . PHP_EOL .
                    '  -d | --directory <output directory>' . PHP_EOL .
                    '      Set the output directory for the script (defaults to a "calendars"' . PHP_EOL .
                    '      directory in the current working directory)' . PHP_EOL .
                    '  -c | --count <event count>' . PHP_EOL .
                    '      Set the number of events generated in the base calendar (defaults' . PHP_EOL .
                    '      to ' . EVENT_COUNT . ')' . PHP_EOL .
                    '  -b | --begindate <begin date>' . PHP_EOL .
                    '      The starting date for generated events (defaults to "' . DATE_BEGIN . '")' . PHP_EOL .
                    '  -e | --enddate <end date>' . PHP_EOL .
                    '      The ending date for generated events (defaults to "' . DATE_END . '")' . PHP_EOL .
                    '  -m | --comparisons' . PHP_EOL .
                    '      Generate calendars for FilterCalendar comparison Filter testing from base' . PHP_EOL .
                    '      calendar (defaults to no generation)' . PHP_EOL .
                    '  -x | --logicalexpressions' . PHP_EOL .
                    '      Generate calendars for FilterCalendar logical expression Filter testing' . PHP_EOL .
                    '      from base calendar (defaults to no generation)' . PHP_EOL .
                    '  -t | --transformations' . PHP_EOL .
                    '      Generate calendars for FilterCalendar transformation Filter testing from' . PHP_EOL .
                    '      base calendar (defaults to no generation)' . PHP_EOL .
                    '  -s | --snapshots {basic, filter, rename}' . PHP_EOL .
                    '      Which calendar snapshot sets should be generated for testing' . PHP_EOL .
                    '      RetainCalendarHistory:' . PHP_EOL .
                    '        basic:  n overlapping snapshots of the base calendar, unmodified' . PHP_EOL .
                    '        filter: n overlapping snapshots of the base calendar, with randomly' . PHP_EOL .
                    '                removed events from each snapshot' . PHP_EOL .
                    '        rename: n overlapping snapshots of the base calendar, with randomly' . PHP_EOL .
                    '                removed or modified events in each snapshot' . PHP_EOL .
                    '  -l | --snapshotslices <slice count>' . PHP_EOL .
                    '      How many overlapping snapshots to slice the base calendar into (defaults' . PHP_EOL .
                    '      to ' . SNAPSHOT_COUNT . ' snapshots)' . PHP_EOL;
                break;
            case '--directory':
            case '-d':
                if (!realpath($argv[$i + 1])) {
                    if (realpath(dirname($argv[$i + 1]))) {
                        mkdir($argv[$i + 1]);
                        $calendarDirectory = realpath($argv[$i + 1]);
                    }
                } else {
                    $calendarDirectory = realpath($argv[$i + 1]);
                }
                break;
            case '--flush':
            case '-f':
                $flushDirectory = true;
                break;
            case '--regenerate':
            case '-r':
                $regenerateBase = true;
                break;
            case '--count':
            case '-c':
                $eventCount = (integer)$argv[$i + 1];
                break;
            case '--snapshot':
            case '-s':
                for ($n = $i + 1; key_exists(strtolower($argv[$n]), $generateSnapshots); $n++) {
                    $generateSnapshots[$argv[$n]] = true;
                }
                break;
            case '--snapshotslices':
            case '-l':
                $snapshotCount = (integer)$argv[$i + 1];
                break;
            case '--begindate':
            case '-b':
                $beginDate = new DateTime($argv[$i + 1]);
                break;
            case '--enddate':
            case '-e':
                $endDate = new DateTime($argv[$i + 1]);
                break;
            case '--comparisons':
            case '-m':
                $generateComparisons = true;
                break;
            case '--logicalexpressions':
            case '-x':
                $generateLogicalExpressions = true;
                break;
            case '--transformations':
            case '-t':
                $generateTransformations = true;
                break;
        }
    }

    $base = null;
    if ($regenerateBase) {
        echo 'Regenerating base calendar...' . PHP_EOL;
        $base = new CalendarGenerator($beginDate, $endDate, $eventCount);
    } else {
        $base = new CalendarGenerator($calendarDirectory . DIRECTORY_SEPARATOR . 'base.ics');
    }
    $base->setCalendarDirectory($calendarDirectory);
    if ($flushDirectory) {
        echo 'Flushing calendar directory...' . PHP_EOL;
        $base->emptyCalendarDirectory();
    }
    if ($regenerateBase || $flushDirectory) {
        $base->save('base');
        echo "\tbase.ics" . PHP_EOL;
    }

    if (array_search(true, $generateSnapshots)) {
        echo 'Generating snapshot calendars...' . PHP_EOL;
        /** @var CalendarGenerator[] $snapshots */
        $snapshots = $base->snapshots($snapshotCount);
        for ($i = 0; $i < count($snapshots); $i++) {
            if ($generateSnapshots[SNAPSHOT_BASIC]) {
                $snapshots[$i]->save("snapshot_$i");
                echo "\tsnapshot_$i.ics" . PHP_EOL;
            }
            if ($generateSnapshots[SNAPSHOT_RANDOM_FILTER]) {
                $snapshots[$i]->filter(function (): bool {
                    return rand(1, 10) != 3;
                })->save("snapshot_randomFilter_$i");
                echo "\tsnapshot_randomFilter_$i.ics" . PHP_EOL;
            }
            if ($generateSnapshots[SNAPSHOT_RANDOM_RENAME]) {
                $snapshots[$i]->transform(function (vevent $e): vevent {
                    if (rand(1, 5) == 3) {
                        $e->setSummary(random_factor());
                    }
                    return $e;
                })->filter(function (): bool {
                    return rand(1, 30) != 17;
                })->save("snapshot_randomRename_$i");
                echo "\tsnapshot_randomRename_$i.ics" . PHP_EOL;
            }
        }
    }

    if ($generateComparisons) {
        echo 'Generating filter comparison calendars...' . PHP_EOL;
        $base->filter(function (vevent $e): bool {
            return preg_match('/([a-z])\\1/i', $e->getProperty(SUMMARY)) === 1;
        })->save('silly_aunt_sally');
        echo "\tsilly_aunt_sally.ics" . PHP_EOL;

        $generateComparisons = [
            'Contains' => function (string $a, string $b): bool {
                return strpos($a, $b) !== false;
            },
            'Equals' => function (string $a, string $b): bool {
                return $a == $b;
            },
            'GreaterThan' => function (string $a, string $b): bool {
                return $a > $b;
            },
            'GreaterThanOrEquals' => function (string $a, string $b): bool {
                return $a >= $b;
            },
            'LessThan' => function (string $a, string $b): bool {
                return $a < $b;
            },
            'LessThanOrEquals' => function (string $a, string $b): bool {
                return $a <= $b;
            }
        ];
        foreach ($generateComparisons as $comparison => $callback) {
            $keyword = $base->getRandomSummary();
            if ($comparison == 'Contains') $keyword = explode(' ', $keyword)[0];
            $base->filter(function (vevent $e) use ($comparison, $callback, $keyword): bool {
                return $callback($e->getProperty(SUMMARY), $keyword);
            })->save("filtered_{$comparison}_{$keyword}");
            echo "\tfiltered_{$comparison}_{$keyword}.ics" . PHP_EOL;
        }
    }

    if ($generateLogicalExpressions) {
        echo 'Generating filter logical expression calendars...' . PHP_EOL;
        $expressions = [
            'And' => function (string $a, string $b, string $c): bool {
                return $a > $b && $a < $c;
            },
            'Or' => function (string $a, string $b, string $c): bool {
                return $a < $b || $a > $c;
            },
            'Not' => function (string $a, string $b): bool {
                return $a != $b;
            }
        ];
        foreach ($expressions as $expression => $callback) {
            $keywordA = $base->getRandomSummary();
            $keywordB = $base->getRandomSummary();
            if ($keywordA > $keywordB) {
                $temp = $keywordA;
                $keywordA = $keywordB;
                $keywordB = $temp;
            }
            $base->filter(function (vevent $e) use ($expression, $callback, $keywordA, $keywordB): bool {
                return $callback($e->getProperty(SUMMARY), $keywordA, $keywordB);
            })->save("filtered_{$expression}_{$keywordA}_{$keywordB}");
            echo "\tfiltered_{$expression}_{$keywordA}_{$keywordB}.ics" . PHP_EOL;
        }
    }

    if ($generateTransformations) {
        echo 'Generating filter transformation calendars...' . PHP_EOL;
        $transformations = [
            'RegexReplace' => function (string $text) {
                return preg_replace('/[aeiou]/i', '_', $text);
            },
            'RenderMarkdown' => function (string $text) {
                return Markdown::defaultTransform($text);
            },
            'Replace' => function (string $text) {
                return str_replace(' ', '-', $text);
            }
        ];
        foreach ($transformations as $transformation => $callback) {
            $base->transform(function (vevent $e) use ($transformation, $callback): vevent {
                $e->setProperty(DESCRIPTION, $callback($e->getProperty(DESCRIPTION)));
                return $e;
            })->save("transform_{$transformation}");
            echo "\ttransform_{$transformation}.ics" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    exit($e->getCode());
}
