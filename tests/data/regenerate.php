<?php

use Battis\IcsMunger\Tests\data\CalendarGenerator;
use kigkonsult\iCalcreator\vevent;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $base = new CalendarGenerator();
    $base->setCalendarDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'calendars');
    $base->emptyCalendarDirectory();
    $base->save('base');
    $snapshots = $base->snapshots(3);
    for ($i = 0; $i < count($snapshots); $i++) {
        $snapshots[$i]->save("snapshot_$i");
        $snapshots[$i]->filter(function (): bool {
            return rand(1, 10) != 3;
        })->save("snapshot_randomFilter_$i");
        $snapshots[$i]->transform(function (vevent $e): vevent {
            if (rand(1, 5) == 3) {
                $e->setSummary(random_factor());
            }
            return $e;
        })->filter(function (): bool {
            return rand(1, 30) != 17;
        })->save("snapshot_randomRename_$i");
    }

    $base->filter(function (vevent $e): bool {
        return preg_match('/([a-z])\\1/i', $e->getProperty('summary')) === 1;
    })->save('silly_aunt_sally');

    $comparisons = [
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
    foreach ($comparisons as $comparison => $callback) {
        $keyword = $base->getRandomSummary();
        if ($comparison == 'Contains') $keyword = explode(' ', $keyword)[0];
        $base->filter(function (vevent $e) use ($comparison, $callback, $keyword): bool {
            return $callback($e->getProperty('summary'), $keyword);
        })->save("filtered_{$comparison}_{$keyword}");
    }

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
            return $callback($e->getProperty('summary'), $keywordA, $keywordB);
        })->save("filtered_{$expression}_{$keywordA}_{$keywordB}");
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    exit($e->getCode());
}
