<?php

use Battis\IcsMunger\Calendar\CalendarException;
use Battis\IcsMunger\ConvertToInvitation\ConvertToInvitation;

//require_once '03_filter.php';
require_once __DIR__ . '/../vendor/autoload.php';
$c = json_decode(__DIR__ . '/config.json');

$transport = (new Swift_SmtpTransport($c->smtp->host, $c->smtp->port, $c->smtp->encryption))
    ->setUsername($c->smtp->user)
    ->setPassword($c->smtp->password);
$mailer = new Swift_Mailer($transport);

try {
    $calendar = new ConvertToInvitation(
    //$calendar,
        __DIR__ . "/{$c->source->name} - CS I.ics",
        $pdo,
        $mailer,
        $c->invitation->organizer);
    $calendar->invite('seth@battis.net');
} catch (CalendarException $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
