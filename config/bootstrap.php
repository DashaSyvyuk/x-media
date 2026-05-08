<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (!class_exists(Dotenv::class)) {
    throw new LogicException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
}

if (!isset($_SERVER['APP_ENV']) && !isset($_ENV['APP_ENV'])) {
    if (class_exists(Dotenv::class)) {
        (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
    }
}

if ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? false) {
    umask(0000);
}
