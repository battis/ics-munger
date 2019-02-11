<?php

use Battis\IcsMunger\IcsMungerException;
use Battis\IcsMunger\RetainHistory\RetainCalendarHistory;

require_once __DIR__ . '/../vendor/autoload.php';

$c = require __DIR__ . '/config.inc.php';
try {
    $calendar = new RetainCalendarHistory($c->url, new PDO("mysql:host={$c->host};port={$c->port};dbname={$c->database}", $c->user, $c->password));
    var_dump($calendar->getId());
    var_dump($calendar->getName());
    echo 'First event: ' . $calendar->getFirstEventStart()->format('c');
} catch (IcsMungerException $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
