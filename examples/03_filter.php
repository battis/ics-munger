<?php

use Battis\IcsMunger\Calendar\Event;
use Battis\IcsMunger\Filtered\FilteredCalendar;

require_once '02_consolidate.php';

try {
    echo 'Filtering to ' . realpath(__DIR__ . "/{$c->source->name} - CS I.ics") . PHP_EOL;
    $calendar = new FilteredCalendar(
        $calendar,
        function (Event $e): bool {
            $result = strpos($e->getSummary(), 'CSI:') !== false && strpos($e->getSummary(), 'FSO') === false;
            return $result;
        },
        function (Event $e): Event {
            preg_match('/.*(\([C-J] Block\)).*/', $e->getSummary(), $matches);
            $e->setSummary('CS I ' . $matches[1]);
            switch ($matches[1]) {
                case '(D Block)':
                    $e->setLocation('Room L005');
                    break;
                case '(G Block)':
                    $e->setLocation('Room 105');
                    break;
            }
            return $e;
        }
    );
    echo 'Components: ' . $calendar->countComponents() . PHP_EOL . PHP_EOL;
    $calendar->setConfig([
        'directory' => __DIR__,
        'filename' => "{$c->source->name} - CS I.ics"
    ]);
    $calendar->saveCalendar();
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
