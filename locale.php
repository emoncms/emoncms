<?php
/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

// Return all locale directory from all modules.
// If one module has a language it will be detected
function directoryLocaleScan($dir)
{
    if (isset($dir) && is_readable($dir)) {
        $dlist = array();
        $dir = realpath($dir);

        $dlist = glob($dir."/{Modules,Theme}/*/locale/*", GLOB_ONLYDIR | GLOB_BRACE);

        $dlist = array_map(
            function ($item) {
                return basename($item);
            },
            $dlist
        );

        return array_unique($dlist);
    }
}

function get_available_languages()
{
    return directoryLocaleScan(dirname(__FILE__));
}


function lang_http_accept()
{
    $langs = array();

    foreach (explode(',', server('HTTP_ACCEPT_LANGUAGE')) as $lang) {
        $pattern = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
        '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
        '(?P<quantifier>\d\.\d))?$/';

        $splits = array();

        if (preg_match($pattern, $lang, $splits)) {
            $langs[] = !empty($splits['subtag']) ? $splits["primarytag"] . "_" . $splits['subtag'] : $splits["primarytag"];
        }
    }
    return $langs;
}

/***
 * take the values from the given list and save it as the user's language
 * only takes supported language values.
 * @param array $language - array returned by lang_http_accept() - without the validating values
 */
function set_lang($language)
{
    global $settings;
    // DEFAULT - from settings.php (if not in file use 'en_GB')
    $fallback_language = $settings['interface']['default_language'];

    $supported_languages = array(
        'cy' => 'cy_GB',
        'da' => 'da_DK',
        'es' => 'es_ES',
        'fr' => 'fr_FR',
        'it' => 'it_IT',
        'nl' => 'nl_NL',
        'en' => 'en_GB'
    );

/**
 * ORDER OF PREFERENCE WITH LANGUAGE SELECTION
 * -------------------------------------------
 * 1. non logged in users use the browser's language
 * 2. logged in users use their saved language preference
 * 3. logged in users without language saved uses `$default_language` from settings.php
 * 4. else fallback is set to 'en_GB'
*/

    $lang = $fallback_language; // if not found use fallback

    // loop through all given $language values
    // if given language is a key or value in the above list use it
    foreach ($language as $lang_code) {
        $lang_code = filter_var($lang_code, FILTER_SANITIZE_STRING);
        if (isset($supported_languages[$lang_code])) { // key check
            $lang = $supported_languages[$lang_code];
            break;
        } elseif (in_array($lang_code, $supported_languages)) { // value check
            $lang = $lang_code;
            break;
        }
    }
    set_lang_by_user($lang);
}

function set_lang_by_user($lang)
{
    $locale = $lang.'.UTF8';
    define(LC_MESSAGES, $locale);
    putenv("LC_ALL=$locale");
    setlocale(LC_ALL, $locale);
}

function set_emoncms_lang($lang)
{
    // If no language defined use the browser language
    if ($lang == '') {
        $browser_languages = lang_http_accept();
        set_lang($browser_languages);
    } else {
        set_lang_by_user($lang);
    }
    global $session;
}
