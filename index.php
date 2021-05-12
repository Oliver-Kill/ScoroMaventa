<?php namespace App;

use ScoroMaventa\Mail;
use function Sentry\init;

// Init composer auto-loading
if (!@include_once("vendor/autoload.php")) {

    exit('Run composer install');

}
include 'system/functions.php';
include 'constants.php';

date_default_timezone_set(DEFAULT_TIMEZONE);

// Load config
if (!include('config.php')) {
    $errors[] = 'No config.php. Please make a copy of config.sample.php and name it config.php and configure it.';
    require 'templates/error_template.php';
    exit();
}

if (sentryDsnIsSet()) {
    init([
        'dsn' => SENTRY_DSN,
        'environment' => SENTRY_ENVIRONMENT
    ]);
}


// Load app
try {
    $app = new Application;
} catch (\Exception $e) {
    debug("ERROR: " . $e->getMessage());
    Application::sendExceptionToSentry($e);
    debug("Sent error to Sentry");
    Mail::send('Application error: ' . $e->getMessage(), json_encode($e));
    exit();
}
