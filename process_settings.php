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

require_once('Lib/enum.php');

// Load settings.php
if(file_exists(dirname(__FILE__)."/settings.php")) {
    require_once('default-settings.php');
    require_once('settings.php');
    if (!isset($settings)) $settings = array();
    $settings = array_replace_recursive($_settings,$settings);
} else if(file_exists(dirname(__FILE__)."/settings.ini")) {
    $CONFIG_INI = parse_ini_file("default-settings.ini", true);
    $CUSTOM_INI = parse_ini_file("settings.ini", true);
    $settings = ini_merge($CONFIG_INI, $CUSTOM_INI);
    // $settings = ini_check_envvars($settings);
}
// ---------------------------------------------------------------------------------------
/*
if (!isset($linked_modules_dir)) {
    if (is_dir("$emoncms_dir/modules")) {
        $linked_modules_dir = "$emoncms_dir/modules";
    } else {
        $linked_modules_dir = $emoncms_dir;
    }
}*/

// Set display errors
if (isset($settings["display_errors"]) && ($settings["display_errors"])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
}


// ---------------------------------------------------------------------------------------
// FUNCTIONS
// ---------------------------------------------------------------------------------------

// This function takes two arrays of settings and merges them, using
// the value from $overrides where it differs from the one in $defaults.
function ini_merge ($defaults, $overrides) {
    foreach ($overrides as $k => $v) {
        if (is_array($v)) {
            $defaults[$k] = ini_merge($defaults[$k], $overrides[$k]);
        } else {
            $defaults[$k] = $v;
        }
    }

    return $defaults;
};

// This function iterates over all the config file entries, replacing values
// of the format {{VAR_NAME}} with the environment variable 'VAR_NAME'.
//
// This can be useful in containerised setups, or testing environments.
function ini_check_envvars($config) {
    global $error_out;

    foreach ($config as $section => $options) {
        foreach ($options as $key => $value) {
            // Find {{ }} vars and replace what's within them with the
            // named environment var
            if (strpos($value, '{{') !== false && strpos($value, '}}') !== false) {
                preg_match_all( '/{{([^}]*)}}/', $value, $matches);
                foreach ($matches[1] as $match) {
                    if (!isset($_ENV[$match])) {
                        $error_out .= "<p>Error: environment var '${match}' not defined in config section [${section}], setting '${key}'</p>";
                    } else {
                        $newval = str_replace('{{'.$match.'}}', $_ENV[$match], $value);

                        // Convert booleans from strings
                        if ($newval === 'true') {
                            $newval = true;
                        } else if ($newval === 'false') {
                            $newval = false;

                        // Convert numbers from strings
                        } else if (is_numeric($newval)) {
                            $newval = $newval + 0;
                        }

                        // Set the new value
                        $config[$section][$key] = $newval;
                    }
                }
            }
        }
    }

    return $config;
}
