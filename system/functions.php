<?php

use App\Request;
use App\Translation;

/**
 * Display a fancy error page and quit.
 * @param $error_msg string Error message to show
 * @param int $code HTTP RESPONSE CODE. Default is 500 (Internal server error)
 */
function error_out($error_msg, $code = 500)
{

    // Return HTTP RESPONSE CODE to browser
    header($_SERVER["SERVER_PROTOCOL"] . " $code Something went wrong", true, $code);


    // Set error message
    $errors[] = $error_msg;

    if (Request::isAjax()) {
        stop(400, $error_msg);
    }

    // Show pretty error, too, to humans
    require __DIR__ . '/../templates/error_template.php';


    // Stop execution
    exit();
}

function get_translation_strings($lang)
{
    global $translations;

    // Handle case when current language has been just deleted from the DB
    $translationColumn = !in_array($_SESSION['language'], Translation::languageCodesInUse(false))
        ? "NULL AS translationIn$lang" : "translationIn$lang";

    $translations_raw = get_all("
        SELECT translationPhrase, $translationColumn 
        FROM translations");

    foreach ($translations_raw as $item) {
        $translations[$item['translationPhrase']] = $item["translationIn$lang"] === NULL ? $item['translationPhrase']
            : $item["translationIn$lang"];
    }
}

/**
 * Translates the text into currently selected language
 * @param $translationPhrase string The text to be translated
 * @param null $dynamic_source
 * @return string Translated text
 */
function __(string $translationPhrase, $dynamic_source = null): ?string
{
    global $translations;

    $translationPhrase = trim($translationPhrase);

    // We don't want such things ending up in db
    if ($translationPhrase === '') {
        return '';
    }

    // Convert the first letter of the language code to upper case
    $lang = ucfirst($_SESSION['language']);

    // return the original string if there was no language
    if (!$lang) {
        return $translationPhrase;
    }

    // Load translations (only the first time)
    if (empty($translations)) {

        // Return original string if the language does not exist (any more)
        if (!in_array($lang, Translation::languageCodesInUse(true))) {
            return $translationPhrase;
        }
        get_translation_strings($lang);
    }

    // Db does not store more than 765 bytes
    $translationPhrase = substr($translationPhrase, 0, 765);

    // Return the translation if it's there
    if (isset($translations[$translationPhrase])) {

        // Return original string if untranslated
        if ($translations[$translationPhrase] === NULL)
            return $translationPhrase;

        // Else return translated string
        return $translations[$translationPhrase];
    }

    // Right, so we don't have this in our db yet

    // Insert new stub
    Translation::add($translationPhrase, $dynamic_source);

    // And return the original string
    return nl2br($translationPhrase);

}

function stop($code, $data = false)
{
    $response['status'] = $code;

    if ($data) {
        $response['data'] = $data;
    }

    // Change HTTP status code
    http_response_code($code);

    exit(json_encode($response));
}

/**
 * Returns true if Sentry DSN is set
 * @return bool
 */
function sentryDsnIsSet(): bool
{
    if (!defined('SENTRY_DSN') || empty(SENTRY_DSN)) {
        return false;
    }
    return true;
}

function debug($message)
{
    $data = date('[H:i:s] ') . $message;
    file_put_contents('.debug/debug.log', $data . "\n", FILE_APPEND);
    if (SHOW_DEBUGGING_MESSAGES) {
        echo inCli() ? $data : "<pre>" . htmlentities($data) . "</pre>" . "\n";
    }
}

function inCli(): bool
{
    return php_sapi_name() == "cli";
}

function disableHtmlErrorsWhenInCli()
{
    if (inCli()) {
        ini_set('xdebug.cli_color', 1);
        ini_set('html_errors', 0);
    }
}

function replaceAmpersandWithHtmlEntityRecursively(&$array)
{
    foreach ($array as $key => &$value) {

        if (is_object($array)) {
            if (is_object($array->$key) || is_array($array->$key)) {
                $array->$key = replaceAmpersandWithHtmlEntityRecursively($array->$key);
            } else {
                if (is_string($value)) $array->$key = str_replace('&', '&amp;', $value);
            }

        } elseif (is_array($array)) {
            if (is_object($array[$key]) || is_array($array[$key])) {
                $array[$key] = replaceAmpersandWithHtmlEntityRecursively($array[$key]);
            } else {
                if (is_string($value)) $array[$key] = str_replace('&', '&amp;', $value);
            }
        }
    }

    return $array;
}

function stripNonNumbers($businessId): string
{
    return preg_replace('/[^\d]/', '', $businessId);
}