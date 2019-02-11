<?php

use Battis\IcsMunger\Filtered\FilteredCalendar;
use Battis\IcsMunger\Filtered\Tests\BooleanOperators\AndOp;
use Battis\IcsMunger\Filtered\Tests\BooleanOperators\NotOp;
use Battis\IcsMunger\Filtered\Tests\Comparisons\Like;

require_once __DIR__ . '/../vendor/autoload.php';

$url = "https://gannacademy.myschoolapp.com/podium/feed/iCal.aspx?z=xYlO%2ffhAGxZYhQm8P3bnrGMROkbrKOrs%2be8DL04ukp3pTqlfiAJhB4P5%2fRgODqtGp%2f9YjUjde0TBN2QkIewfZP8jfaGMgnbKxaamuBC820PHCzuKUGTlc0FkbYLxMHZP";

if (file_exists(__DIR__ . '/original.ics')) unlink(realpath(__DIR__ . '/original.ics'));
if (file_exists(__DIR__ . '/filtered.ics')) unlink(realpath(__DIR__ . '/filtered.ics'));

file_put_contents(__DIR__ . '/original.ics', file_get_contents($url));

try {
    $c = new FilteredCalendar(
        $url,
        AndOp::expr([
            NotOp::expr(Like::expr('summary', '/[A-K] Block.*\\([A-K] Block\\)/i')),
            Like::expr('summary', '/ - \\d/')
        ])
    );
    $c->setConfig(['directory' => __DIR__, 'filename' => 'filtered.ics']);
    $c->saveCalendar();
} catch (Exception $e) {
    error_log($e->getTraceAsString());
}
