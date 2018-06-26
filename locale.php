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

use Gettext\BaseTranslator;

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

/* Extract the list of browser accept languages  */
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
            $a = $splits["primarytag"];
            if (isset($splits["subtag"]) && $splits["subtag"]<> "") $a = $a."_".$splits["subtag"];
            $langs[]=$a;
        } else {
            // No match
        }
    }
    return $langs;
}


function set_emoncms_lang($lang)
{
    global $session;

    // If no language defined use the browser language
    if ($lang == '') {
        $browser_lang_accept = lang_http_accept();
        // set the first browser selected language
        // TODO: iterate to find a suitable available language

        // Chrome returns different HTTP_ACCEPT_LANGUAGE code than firefox!!!
        // Firefox      Chrome
        // -------------------
        //  en_EN         en
        //  es_ES         es
        // ... so translation system does not work in Chrome!!!
        // lets try to fix quickly

        if (isset($browser_lang_accept[0]))
        {
            $lang=$browser_lang_accept[0];

            switch($lang) {
                case 'cy': $lang='cy_GB'; break;
                case 'da': $lang='da_DK'; break;
                case 'en': $lang='en_EN'; break;
                case 'es': $lang='es_ES'; break;
                case 'fr': $lang='fr_FR'; break;
                case 'it': $lang='it_IT'; break;
                case 'nl': $lang='nl_NL'; break;
                //case 'nl': $lang='nl_BE'; break; //to do??
            }
        }
    }

    putenv("LC_ALL=$lang".'.UTF8');
    setlocale(LC_ALL,$lang.'.UTF8');
    $session['lang'] = $lang; //set language in session
}

/**
 * Echo the translation of a string.
 *
 * @param string $original
 *
 * @return void
 */
function _e($original)
{
    $text = BaseTranslator::$current->gettext($original);

    if (func_num_args() === 1) {
        echo $text;
        return;
    }
    $args = array_slice(func_get_args(), 1);
    $str = is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
    echo $str;

}

/* Load translation from MO file or if set from redis cache */
function load_translation_file($mofile, $domain)
{
    global $t, $session, $redis;
    $lang_key='languages:'; //prefix key
    $lang_code = $session['lang']; //current language selected
    $ttl = 60 * 60 * 24; //in sec 1gg

    if(file_exists($mofile)) {
        if($redis->exists($lang_key.$lang_code.':'.$domain)){
            $cache_translations = $redis->get($lang_key.$lang_code.':'.$domain);
            $translations = unserialize($cache_translations);
        }
        else {
            $translations = Gettext\Translations::fromMoFile($mofile);
            $translations->setDomain($domain);
            $redis->set($lang_key.$lang_code.':'.$domain, serialize($translations), $ttl);
        }
        $t->loadTranslations($translations);
    }

}
