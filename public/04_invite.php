<?php

use Battis\IcsMunger\ConvertToInvitation\ConvertToInvitation;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::create(__DIR__ . '/..')->load();

$source = __DIR__ . '/' . getenv('SOURCE_NAME') . ' - CS I.ics';
if (!file_exists($source)) {
    require_once(__DIR__ . '/03_filter.php');
}
$source = realpath($source);

$transport = (new Swift_SmtpTransport(getenv('SMTP_HOST'), getenv('SMTP_PORT'), getenv('SMTP_ENCRYPTION')))
    ->setUsername(getenv('SMTP_USER'))
    ->setPassword(getenv('SMTP_PASSWORD'));
$mailer = new Swift_Mailer($transport);

try {
    echo "Parsing $source";
    $calendar = new ConvertToInvitation(
        $source,
        $pdo,
        $mailer,
        getenv('ORGANIZER')
    );
    echo 'Components: ' . $calendar->countComponents() . PHP_EOL . PHP_EOL;

    foreach (['sbattis@gannacademy.org', 'seth@battis.net'] as $attendee) {
        echo "Inviting $attendee" . PHP_EOL . PHP_EOL;
        $calendar->invite($attendee);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
