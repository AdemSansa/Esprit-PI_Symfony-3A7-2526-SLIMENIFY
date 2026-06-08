<?php

// Serve static files when using the built-in PHP web server (php -S)
if (php_sapi_name() === 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = __DIR__ . $url;
    // If the file exists and is not a directory, let the built-in server handle it directly
    if ($url !== '/' && file_exists($path) && is_file($path)) {
        return false;
    }
}

set_time_limit(30);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
