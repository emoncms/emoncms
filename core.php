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

function get_application_path()
{
    // Default to http protocol
    $proto = "http";

    // Detect if we are running HTTPS or proxied HTTPS
    if (server('HTTPS') == 'on') {
        // Web server is running native HTTPS
        $proto = "https";
    } elseif (server('HTTP_X_FORWARDED_PROTO') == "https") {
        // Web server is running behind a proxy which is running HTTPS
        $proto = "https";
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $path = dirname("$proto://" . server('HTTP_X_FORWARDED_HOST') . server('SCRIPT_NAME')) . "/";
    } else {
        $path = dirname("$proto://" . server('HTTP_HOST') . server('SCRIPT_NAME')) . "/";
    }

    return $path;
}

function db_check($mysqli, $database)
{
    $result = $mysqli->query("SELECT count(table_schema) from information_schema.tables WHERE table_schema = '$database'");
    $row = $result->fetch_array();
    if ($row['0']>0) {
        return true;
    } else {
        return false;
    }
}

function controller($controller_name)
{
    $output = array('content'=>EMPTY_ROUTE);

    if ($controller_name) {
        $controller = $controller_name."_controller";
        $controllerScript = "Modules/".$controller_name."/".$controller.".php";
        if (is_file($controllerScript)) {
            load_language_files("Modules/".$controller_name."/locale");

            require_once $controllerScript;
            $output = $controller();
            if (!is_array($output) || !isset($output["content"])) {
                $output = array("content"=>$output);
            }
        }
    }
    return $output;
}

function view($filepath, array $args = array())
{
    $content = '';
    if (file_exists($filepath)) {
        extract($args);
        ob_start();
        include "$filepath";
        $content = ob_get_clean();
    }
    return $content;
}
/**
 * strip slashes from GET values or null if not set
 *
 * @param string $index name of $_GET item
 *
 **/
function get($index)
{
    $val = null;
    if (isset($_GET[$index])) {
        $val = rawurldecode($_GET[$index]);
    }
    
    $val = stripslashes($val);
    return $val;
}
/**
 * strip slashes from POST values or null if not set
 *
 * @param string $index name of $_POST item
 *
 **/
function post($index)
{
    $val = null;
    if (isset($_POST[$index])) {
        // PHP automatically converts POST names with brackets `field[]` to type array
        if (!is_array($_POST[$index])) {
            $val = rawurldecode($_POST[$index]); // does not decode the plus symbol into spaces
        } else {
            // sanitize the array values
            $SANTIZED_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            if (!empty($SANTIZED_POST[$index])) {
                $val = $SANTIZED_POST[$index];
            }
        }
    }
    if (is_array($val)) {
        $val = array_map("stripslashes", $val);
    } else {
        $val = stripslashes($val);
    }
    return $val;
}
/**
 * strip slashes from POST or GET values or null if not set
 *
 * @param string $index name of $_POST or $_GET item
 *
 **/
function prop($index)
{
    $val = null;
    if (isset($_GET[$index])) {
        $val = $_GET[$index];
    }
    if (isset($_POST[$index])) {
        $val = $_POST[$index];
    }
    
    if (is_array($val)) {
        $val = array_map("stripslashes", $val);
    } else {
        $val = stripslashes($val);
    }
    return $val;
}


function server($index)
{
    $val = null;
    if (isset($_SERVER[$index])) {
        $val = $_SERVER[$index];
    }
    return $val;
}

function delete($index)
{
    parse_str(file_get_contents("php://input"), $_DELETE);//create array with posted (DELETE) method) values
    $val = null;
    if (isset($_DELETE[$index])) {
        $val = $_DELETE[$index];
    }
    
    if (is_array($val)) {
        $val = array_map("stripslashes", $val);
    } else {
        $val = stripslashes($val);
    }
    return $val;
}
function put($index)
{
    parse_str(file_get_contents("php://input"), $_PUT);//create array with posted (PUT method) values
    $val = null;
    if (isset($_PUT[$index])) {
        $val = $_PUT[$index];
    }
    
    if (is_array($val)) {
        $val = array_map("stripslashes", $val);
    } else {
        $val = stripslashes($val);
    };
    return $val;
}

function version()
{
    $version_file = file_get_contents('./version.txt');
    $version = filter_var($version_file, FILTER_SANITIZE_STRING);
    return $version;
}


function load_db_schema()
{
    $schema = array();
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++) {
        if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link') {
            if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_schema.php")) {
                require "Modules/".$dir[$i]."/".$dir[$i]."_schema.php";
            }
        }
    }
    return $schema;
}
/**
 * binds the gettext translations to the correct file and domain/type
 *
 * @param string $path path to the directory containing the .mo files for each language
 * @param [string] $domain
 * @return void
 */
function load_language_files($path, $domain = 'messages')
{
    // Load language files for module
    bind_textdomain_codeset($domain, 'UTF-8');
    bindtextdomain($domain, $path);
    textdomain($domain);
}

function load_menu()
{
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++) {
        if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link') {
            if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_menu.php")) {
                load_language_files("Modules/".$dir[$i]."/locale");
                require "Modules/".$dir[$i]."/".$dir[$i]."_menu.php";
            }
        }
    }
    // add old menu structure if module not updated
    // @todo: remove this once all users updated (2019-02-15)
    if (isset($menu_dropdown_config)) {
        foreach ($menu_dropdown_config as $item) {
            if (!empty($item['name'])) {
                $item['text'] = $item['name'];
            }
            $item['icon'] .= ' icon-white';
            $menu['sidebar']['setup'][] = $item;
        }
    }

    return $menu;
}

function load_sidebar()
{
    global $route;
    $sidebar = array(); // Sidebar 1st level nav
    $sidebar_footer = array(); // Sidebar footer
    $sidebar_sub = array(); // Sidebar 2nd level nav

    $dir = $route->controller;
    $path = implode(DIRECTORY_SEPARATOR, array('Modules', $dir, $dir . "_menu.php"));
    
    if (is_file($path)) {
        require $path;
    }

    if (!empty($sidebar)) {
        $sidebar['sidebar'] = $sidebar;
    }
    if (!empty($subnav)) {
        $sidebar['subnav'] = $subnav;
    }
    if (!empty($sidebar_footer)) {
        $sidebar['footer'] = $sidebar_footer;
    }

    if (!empty($sidebar_includes)) {
        foreach ($sidebar_includes as $file) {
            if (file_exists($file)) {
                $sidebar['includes'][] = view($file);
            }
        }
    }
    return $sidebar;
}

function http_request($method, $url, $data)
{

    $options = array();
    $urlencoded = http_build_query($data);
    
    if ($method=="GET") {
        $url = "$url?$urlencoded";
    } elseif ($method=="POST") {
        $options[CURLOPT_POST] = 1;
        $options[CURLOPT_POSTFIELDS] = $data;
    }
    
    $options[CURLOPT_URL] = $url;
    $options[CURLOPT_RETURNTRANSFER] = 1;
    $options[CURLOPT_CONNECTTIMEOUT] = 2;
    $options[CURLOPT_TIMEOUT] = 5;

    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
}

function emoncms_error($message)
{
    return array("success"=>false, "message"=>$message);
}

function call_hook($function_name, $args)
{
    // @todo: make args parameter optional
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++) {
        if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link') {
            if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_hooks.php")) {
                require "Modules/".$dir[$i]."/".$dir[$i]."_hooks.php";
                if (function_exists($dir[$i].'_'.$function_name)==true) {
                    $hook = $dir[$i].'_'.$function_name;
                    return $hook($args);
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------------------------------------
/**
 * return ip address of requesting machine
 * the ip address can be stored in different variables by the system.
 * which variable name may change dependant on different system setups.
 * this function *should return an acceptible value in most cases
 * @todo: more testing on different hardware/opperating systems/proxy servers etc.
 *
 * @return string
 */
function get_client_ip_env()
{
    $ipaddress = filter_var(getenv('REMOTE_ADDR'), FILTER_VALIDATE_IP);
    if (empty($ipaddress)) {
        $ipaddress = '';
    }
    return $ipaddress;
}
