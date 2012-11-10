<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/


/*
 * Return all locale directory from all modules.
 * If one module has a language it will be detected
 */
function directoryLocaleScan($dir) {
  if (isset($dir) && is_readable($dir)) {
    $dlist = Array();
    $dir = realpath($dir);

    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
    
    foreach($objects as $entry => $object){ 
      $entry = str_replace($dir, '', $entry);
      if (basename(dirname($entry))=='locale')     
        $dlist[] = basename($entry);
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
	
	foreach (explode(',', server('HTTP_ACCEPT_LANGUAGE')) as $lang) {
		$pattern = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
    	'(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
    	'(?P<quantifier>\d\.\d))?$/';

    	$splits = array();

		if (preg_match($pattern, $lang, $splits)) {
			// print_r($splits);
			$a = $splits["primarytag"];
			if ($splits["subtag"]<> "") $a = $a."_".$splits["subtag"];
				$langs[]=$a;
    		} else {
        		//echo "\nno match\n"; 
    	}
	}
	return $langs;
}

function set_lang($language)
{
	// set the first browser selected language
	// TODO: iterate to find a suitable available language
	if (isset($language[0])) set_lang_by_user($language[0]);
}

function set_lang_by_user($lang)
{
	putenv("LC_ALL=$lang");
	setlocale(LC_ALL, $lang); 
	//bindtextdomain("app", "./locale");
	//textdomain("app");
}

function set_emoncms_lang($userid)
{
	// Get language from database user
	$lang = get_user_lang($userid);
	
	// If no language defined use the language browser
	if ($lang == '')
		set_lang(lang_http_accept());
	else 
		set_lang_by_user($lang);
}

?>
