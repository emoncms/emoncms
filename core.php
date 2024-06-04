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

function is_https() {
    // Detect if we are running HTTPS or proxied HTTPS
    if (server('HTTPS') == 'on') {
        // Web server is running native HTTPS
        return true;
    } elseif (server('HTTP_X_FORWARDED_PROTO') == "https") {
        // Web server is running behind a proxy which is running HTTPS
        return true;
    } elseif (server('HTTP_X_FORWARDED_PORT') == 443) {
        // Web server is running behind a proxy which is running HTTPS
        return true;
    } elseif (request_header('HTTP_X_FORWARDED_PROTO') == "https") {
        return true;
    }
    return false;
}

function get_application_path($manual_domain=false)
{
    if (is_https()) {
        $proto = "https";
    } else {
        $proto = "http";
    }

    if ($manual_domain) {
        return "$proto://".$manual_domain."/";
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $filepath = "$proto://" . server('HTTP_X_FORWARDED_HOST');
        if (isset($_SERVER['HTTP_X_INGRESS_PATH'])) {
            // web server is running in ingress mode in home assistant
            $filepath .= server('HTTP_X_INGRESS_PATH');
        }
        $filepath .= server('SCRIPT_NAME');
        $path = dirname($filepath) . "/";
    } else {
        $path = dirname("$proto://" . server('HTTP_HOST') . server('SCRIPT_NAME')) . "/";
    }

    return $path;
}

function db_check($mysqli, $database)
{
    $result = $mysqli->query("SELECT count(table_schema) from information_schema.tables WHERE table_schema = '$database'");
    $row = $result->fetch_array();
    return $row['0'] > 0;
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
            $output['is_controller'] = true;
        } else {
            $output['is_controller'] = false;
        }
    }
    return $output;
}

function view($filepath, array $args = array())
{
    global $path;
    $args['path'] = $path;
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
function get($index,$error_if_missing=false,$default=null)
{
    $val = $default;
    if (isset($_GET[$index])) {
        $val = rawurldecode($_GET[$index]);
    } elseif ($error_if_missing) {
        header('Content-Type: text/plain');
        die("missing $index parameter");
    }
    if (!is_null($val)) {
        $val = stripslashes($val);
    }
    return $val;
}
/**
 * strip slashes from POST values or null if not set
 *
 * @param string $index name of $_POST item
 *
 **/
function post($index,$error_if_missing=false,$default=null)
{
    $val = $default;
    if (isset($_POST[$index])) {
        // PHP automatically converts POST names with brackets `field[]` to type array
        if (!is_array($_POST[$index])) {
            $val = rawurldecode($_POST[$index]); // does not decode the plus symbol into spaces
        } else {
            $val = htmlspecialchars(json_encode($_POST[$index]));
        }
    } elseif ($error_if_missing) {
        header('Content-Type: text/plain');
        die("missing $index parameter");
    }

    if (!is_null($val)) {
        if (is_array($val)) {
            $val = array_map("stripslashes", $val);
        } else {
            $val = stripslashes($val);
        }
    }
    return $val;
}
/**
 * strip slashes from POST or GET values or null if not set
 *
 * @param string $index name of $_POST or $_GET item
 *
 **/
function prop($index,$error_if_missing=false,$default=null)
{
    $val = $default;
    if (isset($_GET[$index])) {
        $val = $_GET[$index];
    } elseif (isset($_POST[$index])) {
        $val = $_POST[$index];
    } elseif ($error_if_missing) {
        header('Content-Type: text/plain');
        die("missing $index parameter");
    }

    if(!is_null($val)) {
        if (is_array($val)) {
            $val = array_map("stripslashes", $val);
        } else {
            $val = stripslashes($val);
        }
    }
    return $val;
}

function request_header($index)
{
   $val = null;
   $headers = apache_request_headers();
   if (isset($headers[$index])) {
        $val = $headers[$index];
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
    }
    return $val;
}

function version()
{
    $version_file = json_decode(file_get_contents('./version.json'));
    return $version_file->version;
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
    global $menu;
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
        if (filetype("Modules/".$dir[$i])=='dir' || filetype("Modules/".$dir[$i])=='link')
        {
            if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_menu.php"))
            {
                if (is_file("Modules/".$dir[$i]."/locale/".$dir[$i]."_messages.pot")) {
                    load_language_files("Modules/".$dir[$i]."/locale",$dir[$i]."_messages"); // management of domains beginning with the name of the module
                } else {
                    load_language_files("Modules/".$dir[$i]."/locale");
                }
                require "Modules/".$dir[$i]."/".$dir[$i]."_menu.php";
            }
        }
    }
}

function http_request($method, $url, $data)
{
    $options = array();

    if ($method=="GET") {
        $urlencoded = http_build_query($data);
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

// ---------------------------------------------------------------------------------------------------------
// Generate secure key
// ---------------------------------------------------------------------------------------------------------
function generate_secure_key($length) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    } else {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}

// ---------------------------------------------------------------------------------------------------------
// Generate a 16 bytes (128 bits) UUID - RFC 4122 compliant Version 4
// ---------------------------------------------------------------------------------------------------------
function guidv4() {
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
    } else {
        $data = openssl_random_pseudo_bytes(16);
    }
    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

