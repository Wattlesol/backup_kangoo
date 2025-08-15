<?php

/*
|--------------------------------------------------------------------------
| Suppress PHP 8.4 Deprecation Warnings for Laravel 8 Compatibility
|--------------------------------------------------------------------------
|
| Laravel 8 was not designed for PHP 8.4, so we suppress deprecation warnings
| to prevent console spam while maintaining functionality.
|
*/

if (isset($_ENV['PHP_ERROR_REPORTING'])) {
    error_reporting(eval('return ' . $_ENV['PHP_ERROR_REPORTING'] . ';'));
} elseif (version_compare(PHP_VERSION, '8.4.0', '>=')) {
    // Suppress deprecation warnings for PHP 8.4+ with Laravel 8
    error_reporting(E_ALL & ~E_DEPRECATED);
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
