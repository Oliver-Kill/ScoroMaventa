<?php namespace App;

use ScoroMaventa\API;
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
    API::truncateLastRequestDebugFile();
    $app = new Application;
} catch (\Exception $e) {
    debug("ERROR: " . $e->getMessage());
    if (sentryDsnIsSet()) {
        Application::sendExceptionToSentry($e, 'GuzzleDebug', [file_get_contents(API::debugFileLocation)]);
        debug("Sent error to Sentry");
        if (Request::isAjax()){
            stop(500, $e->getMessage());
        }
    }
    exit();
}