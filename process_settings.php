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
$settings_error = false;

if(file_exists(dirname(__FILE__)."/settings.php")) {
    require_once('default-settings.php');
    require_once('settings.php');
    if (!isset($settings)) {
        require_once('Lib/process_old_settings.php');
        //$settings_error = true;
        //$settings_error_title = "settings.php file error";    
        //$settings_error_message = "It looks like you are using an old version of settings.php try re-creating your settings.php file from default-settings.php";
    } else {
        $settings = array_replace_recursive($_settings,$settings);
    }
} else if(file_exists(dirname(__FILE__)."/settings.ini")) {
    $DEFAULT_INI = parse_ini_file("default-settings.ini", true, INI_SCANNER_TYPED);
    $SETTINGS_INI = parse_ini_file("settings.ini", true, INI_SCANNER_TYPED);
    $SUPPORTED_ENV = parse_ini_file("settings.env.ini", true, INI_SCANNER_TYPED);

    $SETTINGS_ENV = ini_check_envvars($SUPPORTED_ENV);
    // overwrite order: default-> settings-> env
    $settings = array_replace_recursive($DEFAULT_INI, $SETTINGS_INI, $SETTINGS_ENV);

} else {
    $settings_error = true;
    $settings_error_title = "missing settings file";
    $settings_error_message = "Create a settings.ini file from a example.settings.ini template";
}

if ($settings_error) {
    if (PHP_SAPI === 'cli') {
        echo "$settings_error_title\n";
        echo "$settings_error_message\n";
    } else {
        echo "<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'>";
        echo "<h3>$settings_error_title</h3>";
        echo "<p>$settings_error_message</p>";
        echo "</div>";
    }
    die;
}

// ---------------------------------------------------------------------------------------
if (is_dir($settings["emoncms_dir"]."/modules")) {
    $linked_modules_dir = $settings["emoncms_dir"]."/modules";
} else {
    $linked_modules_dir = $settings["emoncms_dir"];
}

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
function ini_merge($defaults, $overrides) {
    foreach ($overrides as $k => $v) {
        if (is_array($v)) {
            $defaults[$k] = ini_merge($defaults[$k], $overrides[$k]);
        } else {
            $defaults[$k] = resolve_env_vars($v,$defaults[$k]);
#            $defaults[$k] = $v;
        }
    }

    return $defaults;
};

// This function iterates over a single config file entry, replacing values
// of the format {{VAR_NAME}} with the environment variable 'VAR_NAME'.
//
// This can be useful in containerised setups, or testing environments.

function resolve_env_vars($value) {
    // To do any processing we need a string, and at least one pair of {{ }}
    // Otherwise just return what we got.
    if (!is_string($value) ||
            strpos($value, '{{') === false ||
            strpos($value, '}}') === false) {
        return $value;
    }

    preg_match_all('/{{([^}]+)}}/', $value, $matches);
    foreach ($matches[1] as $match) {
        $env_name = $match;
        $env_value = getenv($env_name);
        if ($env_value === false) {
            echo "<p>Error: environment var '${env_name}' not defined</p>";
            return $value;
        }

        $value = str_replace('{{'.$env_name.'}}', $env_value, $value);
    }

    // Convert booleans from strings
    if (strcasecmp($value, "true") == 0) {
        $value = true;
    } else if (strcasecmp($value, 'false') == 0) {
        $value = false;

    // Convert numbers from strings
    } else if (is_numeric($value)) {
        $value = $value + 0;
    }

    // Set the new value
    return $value;
}

// This function iterates over all the config file entries, replacing values
// of the format {{VAR_NAME}} with the environment variable 'VAR_NAME'.
//
// This can be useful in containerised setups, or testing environments.
// if {{VAR_NAME}} found in .ini file but not an ENV variable it is removed.
function ini_check_envvars($config) {
    global $error_out;

    foreach ($config as $section => $options) {
        if(is_array($options)) {
            foreach ($options as $key => $value) {
                // Find {{ }} vars and replace what's within them with the
                // named environment var
                if(is_array($value)) {
                    foreach($value as $subKey => $subValue) {
                        // loop through INI $value that is an array. currently not supported
                        //@todo: add array_walk_recursive() style loop but keeping the $section value
                    }
                } elseif (strpos($value, '{{') !== false && strpos($value, '}}') !== false) {
                    preg_match_all( '/{{([^}]*)}}/', $value, $matches);
                    foreach ($matches[1] as $match) {
                        if (!isset($_ENV[$match])) {
                            $error_out .= "<p>Error: environment var '${match}' not defined in config section [${section}], setting '${key}'</p>";
                            // no match found remove ENV var overide field
                            if (isset($config[$section][$key])) {
                                unset($config[$section][$key]);
                            }
                        } else {
                            if (is_bool($_ENV[$match])) {
                                $_ENV[$match] = $_ENV[$match] ? 'true' : 'false';
                            }
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
    }
    return array_filter($config);
}
