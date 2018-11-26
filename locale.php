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
function directoryLocaleScan($dir) {
    if (isset($dir) && is_readable($dir)) {
        $dlist = Array();
        $dir = realpath($dir);

        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);

        foreach($objects as $entry => $object){
            $entry = str_replace($dir, '', $entry);
            if (basename(dirname($entry))=='locale' && basename($entry)!='.' && basename($entry)!='..') $dlist[] = basename($entry);
        }

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

    foreach (explode(',', server('HTTP_ACCEPT_LANGUAGE')) as $lang)
    {
        $pattern = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
        '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
        '(?P<quantifier>\d\.\d))?$/';

        $splits = array();

        if (preg_match($pattern, $lang, $splits)) {
            $langs[] = !empty($splits['subtag']) ? $splits["primarytag"] . "_" . $splits['subtag'] : $splits["primarytag"];
        } else {
            // No match
        }
    }
    return $langs;
}

/***
 * take the first value from the given list and save it as the user's language
 * only takes supported language values.
 * @param array $language - array returned by lang_http_accept()  - without the quantify values
 * @todo possibly fall back to second or third choices if available?
 */
function set_lang($language)
{
    global $default_language;
    // DEFAULT - from settings.php (if not in file use 'en_GB')
    $default = !empty($default_language) ? $default_language : 'en_GB';
    $firstChoice = !empty($language[0]) ? filter_var($language[0], FILTER_SANITIZE_STRING) : $default;
    $supported_languages = array(
        'cy' => 'cy_GB',
        'da' => 'da_DK',
        'es' => 'es_ES',
        'fr' => 'fr_FR',
        'it' => 'it_IT',
        'nl' => 'nl_NL',
        'en' => 'en_GB'
    );
    // if given language is a key or value in the above list use it 
    if (isset($supported_languages[$firstChoice])) { // key check
        $lang = $supported_languages[$firstChoice];
    } elseif (in_array($firstChoice, $supported_languages)) { // value check
        $lang = $firstChoice;
    } else {
        $lang = $default; // not found use default
    }

    set_lang_by_user($lang);
}

function set_lang_by_user($lang)
{
    putenv("LC_ALL=$lang".'.UTF8');
    setlocale(LC_ALL,$lang.'.UTF8');
}

function set_emoncms_lang($lang)
{
    // If no language defined use the language browser
    if ($lang == '') {
        set_lang(lang_http_accept());
    } else {
        set_lang_by_user($lang);
    }
}

