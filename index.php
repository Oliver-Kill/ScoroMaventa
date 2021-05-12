<?php namespace App;

// Init composer auto-loading
use function Sentry\init;

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
    init(['dsn' => SENTRY_DSN]);
}


// Load app
$app = new Application;
