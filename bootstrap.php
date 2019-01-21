<?php

use Doctrine\ORM\Tools\Setup;

require_once 'vendor/autoload.php';

$isDevMode = true;
$metadata = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/src'], $isDevMode);

$config = json_decode(file_get_contents(__DIR__ . '/config.json'));
$db = $config->database;

$entityManager = \Doctrine\ORM\EntityManager::create(
    ['url' => "{$db->driver}://{$db->user}:{$db->password}@{$db->host}/{$db->database}"],
    $metadata
);
