<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load test stubs BEFORE real classes to prevent actual HTTP requests
// This overrides production classes with test-safe versions

require_once __DIR__ . '/Stubs/TurboSms.php';

// Suppress PHP 8.4 deprecation warnings from PHPUnit 9.5
// PHPUnit 11 requires symfony/phpunit-bridge 7.x which needs Symfony 7.x
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0027);
}
