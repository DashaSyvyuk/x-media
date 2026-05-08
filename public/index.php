<?php

$_SERVER['APP_RUNTIME_OPTIONS'] = [
    'disable_dotenv' => true,
];

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel(
        $context['APP_ENV'] ?? 'prod',
        (bool) ($context['APP_DEBUG'] ?? false)
    );
};
