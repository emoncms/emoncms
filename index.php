<?php
/*

All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/

define('EMONCMS_EXEC', 1);

// 1) Load settings and core scripts
require "process_settings.php";
require "core.php";
require "route.php";
require "param.php";
require "locale.php";

$emoncms_version = ($settings['feed']['redisbuffer']['enabled'] ? "low-write " : "") . version();

$path = get_application_path();
$sidebarFixed = true;

require "Lib/EmonLogger.php";
$log = new EmonLogger(__FILE__);
if (isset($_GET['q'])) {
    $log->info($_GET['q']);
}

// 2) Database
if ($settings['redis']['enabled']) {
    $redis = new Redis();
    $connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);
    if (!$connected) {
        echo "Can't connect to redis at ".$settings['redis']['host'].":".$settings['redis']['port']." , it may be that redis-server is not installed or started see readme for redis installation";
        die;
    }
    if (!empty($settings['redis']['prefix'])) {
        $redis->setOption(Redis::OPT_PREFIX, $settings['redis']['prefix']);
    }
    if (!empty($settings['redis']['auth'])) {
        if (!$redis->auth($settings['redis']['auth'])) {
            echo "Can't connect to redis at ".$settings['redis']['host'].", autentication failed";
            die;
        }
    }
    if (!empty($settings['redis']['dbnum'])) {
        $redis->select($settings['redis']['dbnum']);
    }
} else {
    $redis = false;
}

$mqtt = false;

# Check MySQL PHP modules are loaded
if (!extension_loaded('mysql') && !extension_loaded('mysqli')) {
    echo "Your PHP installation appears to be missing the MySQL extension(s) which are required by Emoncms. <br> See /php-info.php (restricted to local access)";
    die;
}

# Check Gettext PHP  module is loaded
if (!extension_loaded('gettext')) {
    echo "Your PHP installation appears to be missing the gettext extension which is required by Emoncms. <br> See /php-info.php (restricted to local access)";
    die;
}
    
$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);

if ($mysqli->connect_error) {
    echo "Can't connect to database, please verify credentials/configuration in settings.ini<br />";
    if ($settings["display_errors"]) {
        echo "Error message: <b>" . $mysqli->connect_error . "</b>";
    }
    die();
}
// Set charset to utf8
$mysqli->set_charset("utf8");

if (!$mysqli->connect_error && $settings["sql"]["dbtest"]==true) {
    require "Lib/dbschemasetup.php";
    if (!db_check($mysqli, $settings["sql"]["database"])) {
        db_schema_setup($mysqli, load_db_schema(), true);
    }
}

// 3) User sessions
require("Modules/user/user_model.php");
$user = new User($mysqli, $redis);

$apikey = false;
$devicekey = false;
if (isset($_GET['apikey'])) {
    $apikey = $_GET['apikey'];
} elseif (isset($_POST['apikey'])) {
    $apikey = $_POST['apikey'];
} elseif (isset($_GET['devicekey'])) {
    $devicekey = $_GET['devicekey'];
} elseif (isset($_POST['devicekey'])) {
    $devicekey = $_POST['devicekey'];
} elseif (isset($_SERVER["HTTP_AUTHORIZATION"])) {
    // Support passing apikey on Authorization header per rfc6750, like example:
    //      GET /resource HTTP/1.1
    //      Host: server.example.com
    //      Authorization: Bearer THE_API_KEY_HERE
    
    if (isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"]=="aes128cbc") {
        // If content_type is AES128CBC
    } else {
        $apikey = str_replace('Bearer ', '', $_SERVER["HTTP_AUTHORIZATION"]);
    }
}

$device = false;
if ($apikey) {
    $session = $user->apikey_session($apikey);
    if (empty($session)) {
          header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
          header('WWW-Authenticate: Bearer realm="API KEY", error="invalid_apikey", error_description="Invalid API key"');
          print "Invalid API key";
          $log->error("Invalid API key | ".$_SERVER["REMOTE_ADDR"]);
          exit();
    }
} elseif ($devicekey && (@include "Modules/device/device_model.php")) {
    $device = new Device($mysqli, $redis);
    $session = $device->devicekey_session($devicekey);
    if (empty($session)) {
          header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
          header('WWW-Authenticate: Bearer realm="Device KEY", error="invalid_devicekey", error_description="Invalid device key"');
          print "Invalid device key";
          $log->error("Invalid device key");
          exit();
    }
} else {
    $session = $user->emon_session_start();
}

// 4) Language
if (!isset($session['lang'])) {
    $session['lang']='';
}
set_emoncms_lang($session['lang']);

// 5) Get route and load controller

// output string if controller or action not found. used to return error.
define('EMPTY_ROUTE', "#UNDEFINED#");

$route = new Route(get('q'), server('DOCUMENT_ROOT'), server('REQUEST_METHOD'));

// Load get/post/encrypted parameters - only used by input/post and input/bulk API's
$param = new Param($route, $user);

// --------------------------------------------------------------------------------------
// Special routes

// Return brief device descriptor for hub detection
if ($route->controller=="describe") {
    header('Content-Type: text/plain');
    header('Access-Control-Allow-Origin: *');
    if ($redis && $redis->exists("describe")) {
        $type = $redis->get("describe");
    } else {
        $type = 'emoncms';
    }
    echo $type;
    die;
}
// read the version file and return the value;
if ($route->controller=="version") {
    header('Content-Type: text/plain; charset=utf-8');
    echo version();
    exit;
}

if (get('embed')==1) {
    $embed = 1;
} else {
    $embed = 0;
}

// If no route specified use defaults
if ($route->isRouteNotDefined()) {
    // EmonPi Setup Wizard
    if ($settings["interface"]["enable_admin_ui"]) {
        if (file_exists("Modules/setup")) {
            require "Modules/setup/setup_model.php";
            $setup = new Setup($mysqli);
            if ($setup->status()=="unconfigured") {
                $settings["interface"]["default_controller"] = "setup";
                $settings["interface"]["default_action"] = "";
                // Provide special setup access to WIFI module functions
                $_SESSION['setup_access'] = true;
            }
        }
    }
    
    if (!isset($session['read']) || (isset($session['read']) && !$session['read'])) {
        // Non authenticated defaults
        $route->controller = $settings["interface"]["default_controller"];
        $route->action = $settings["interface"]["default_action"];
        $route->subaction = "";
    } else {
        if (isset($session["startingpage"]) && $session["startingpage"]!="") {
            header('Location: '.$session["startingpage"]);
            die;
        } else {
            // Authenticated defaults
            $route->controller = $settings["interface"]["default_controller_auth"];
            $route->action = $settings["interface"]["default_action_auth"];
            $route->subaction = "";
        }
    }
}

if ($devicekey && !($route->controller == 'input' && ($route->action == 'bulk' || $route->action == 'post'))) {
    header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
    print "Unauthorized. Device key autentication only permits input post or bulk actions";
    $log->error("Unauthorized. Device key autentication only permits input post or bulk actions");
    exit();
}

if ($route->controller == 'input' && $route->action == 'bulk') {
    $route->format = 'json';
} elseif ($route->controller == 'input' && $route->action == 'post') {
    $route->format = 'json';
}

// 6) Load the main page controller
$output = controller($route->controller);
// If no controller of this name - then try username
// need to actually test if there isnt a controller rather than if no content
// is returned from the controller.
if ($output['content'] == EMPTY_ROUTE && $settings["public_profile"]["enabled"] && $route->controller!='admin') {
    $userid = $user->get_id($route->controller);
    if ($userid) {
        $route->subaction = $route->action;
        $session['userid'] = $userid;
        $session['username'] = $route->controller;
        $session['read'] = 1;
        $session['profile'] = 1;
        $route->controller = $settings["public_profile"]["controller"];
        $route->action = $settings["public_profile"]["action"];
        $output = controller($route->controller);

        // catch "username/graph" and redirect to the graphs module if no dashboard called "graph" exists
        if ($output["content"]=="" && $route->subaction=="graph") {
            $route->controller = "graph";
            $route->action = "";
            $_GET['userid'] = $userid;
            $output = controller($route->controller);
        }
    }
}

// If no controller found or nothing is returned, give friendly error
if ($output['content'] === EMPTY_ROUTE) {
    // alter output is $route has $action
    $actions = implode("/", array_filter(array($route->action, $route->subaction)));
    $message = sprintf(_('%s cannot respond to %s'), sprintf("<strong>%s</strong>", ucfirst($route->controller)), sprintf('<strong>"%s"</strong>', $actions));
    // alter the http header code
    header($_SERVER["SERVER_PROTOCOL"]." 406 Not Acceptable");
    $title = _('406 Not Acceptable');
    $plain_text = _('Route not found');
    $intro = sprintf('%s %s', _('URI not acceptable.'), $message);
    $text = _('Try another link from the menu.');
    // return the formatted string
    if ($route->format==='html') {
        $output['content'] = sprintf('<h2>%s</h2><p class="lead">%s.</p><p>%s</p>', $title, $intro, $text);
    } else {
        $output['content'] = array(
            'success'=> false,
            'message'=> sprintf('%s. %s', $title, $plain_text)
        );
    }
    $log->warn(sprintf('%s|%s', $title, implode('/', array_filter(array($route->controller,$route->action,$route->subaction)))));
}

// If not authenticated and no ouput, asks for login
if ($output['content'] == "" && (!isset($session['read']) || (isset($session['read']) && !$session['read']))) {
    $log->error(sprintf('%s|%s', _('Not Authenticated'), implode('/', array_filter(array($route->controller,$route->action,$route->subaction)))));
    $route->controller = "user";
    $route->action = "login";
    $route->subaction = "";
    $message = urlencode(_('Authentication Required'));
    $referrer = urlencode(base64_encode(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL)));
    $route->query = sprintf("msg=%s&ref=%s", $message, $referrer);
    $output = controller($route->controller);
}

$output['route'] = $route;
$output['session'] = $session;

// 7) Output
if ($route->format == 'json') {
    if ($route->controller=='time') {
        header('Content-Type: text/plain');
        print $output['content'];
    } elseif ($route->controller=='input' && $route->action=='post') {
        header('Content-Type: text/plain');
        print $output['content'];
    } elseif ($route->controller=='input' && $route->action=='bulk') {
        header('Content-Type: text/plain');
        print $output['content'];
    } else {
        header('Content-Type: application/json');
        if (!empty($output['message'])) {
            header(sprintf('X-emoncms-message: %s', $output['message']));
        }
        print json_encode($output['content']);
        if (json_last_error()!=JSON_ERROR_NONE) {
            switch (json_last_error()) {
                case JSON_ERROR_DEPTH:
                    $log->error("json_encode - $route->controller: Maximum stack depth exceeded");
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $log->error("json_encode - $route->controller: Underflow or the modes mismatch");
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $log->error("json_encode - $route->controller: Unexpected control character found");
                    break;
                case JSON_ERROR_SYNTAX:
                    $log->error("json_encode - $route->controller: Syntax error, malformed JSON");
                    break;
                case JSON_ERROR_UTF8:
                    $log->error("json_encode - $route->controller: Malformed UTF-8 characters, possibly incorrectly encoded");
                    break;
                default:
                    $log->error("json_encode - $route->controller: Unknown error");
                    break;
            }
        }
    }
} elseif ($route->format == 'html') {
    // Select the theme
    $themeDir = "Theme/" . $settings["interface"]["theme"] . "/";
    if ($embed == 1) {
        print view($themeDir . "embed.php", $output);
    } else {
        $menu = load_menu();
        
        // EMONCMS MENU
        if ($session['write']) {
            $menu['tabs'][] = array(
                'icon'=>'menu',
                'title'=> _("Emoncms"),
                'text'=> _("Setup"),
                'path' => 'feed/list',
                'order' => 0,
                'data'=> array(
                    'sidebar' => '#sidebar_emoncms'
                )
            );
        }

        include_once("Lib/misc/nav_functions.php");
        sortMenu($menu);
        // debugMenu('sidebar');
        $output['svg_icons'] = view($themeDir . "svg_icons.svg", array());
        $output['mainmenu'] = view($themeDir . "menu_view.php", array('menu'=>$menu));
        
        // add css class names to <body> tag based on controller's options
        $output['page_classes'][] = $route->controller;

        $output['sidebar'] = view(
            $themeDir . "sidebar_view.php",
            array(
            'menu' => $menu,
            'path' => $path,
            'session' => $session,
            'route' => $route
            )
        );
        $output['page_classes'][] = 'has-sidebar';
        if (!$session['read']) {
            $output['page_classes'][] = 'collapsed manual';
        } else {
            if (!in_array("manual", $output['page_classes'])) {
                $output['page_classes'][] = 'auto';
            }
        }
        print view($themeDir . "theme.php", $output);
    }
} elseif ($route->format == 'text') {
    header('Content-Type: text/plain');
    print $output['content'];
} elseif ($route->format == 'csv') {
    header('Content-Type: text/csv');
    print $output['content'];
} else {
    header($_SERVER["SERVER_PROTOCOL"]." 406 Not Acceptable");
    print "URI not acceptable. Unknown format '".$route->format."'.";
}
