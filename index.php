<?php
    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */
    
    $ltime = microtime(true);
    define('EMONCMS_EXEC', 1);

    // 1) Load settings and core scripts
    require "process_settings.php";
    require "core.php";
    require "route.php";
    require "param.php";
    require "locale.php";

    $emoncms_version = ($feed_settings['redisbuffer']['enabled'] ? "low-write " : "") . version();

    $path = get_application_path();
    $sidebarFixed = true;

    require "Lib/EmonLogger.php";
    $log = new EmonLogger(__FILE__);
    if (isset($_GET['q'])) $log->info($_GET['q']);

    // 2) Database
    if ($redis_enabled) {
        $redis = new Redis();
        $connected = $redis->connect($redis_server['host'], $redis_server['port']);
        if (!$connected) { echo "Can't connect to redis at ".$redis_server['host'].":".$redis_server['port']." , it may be that redis-server is not installed or started see readme for redis installation"; die; }
        if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
        if (!empty($redis_server['auth'])) {
            if (!$redis->auth($redis_server['auth'])) {
                echo "Can't connect to redis at ".$redis_server['host'].", autentication failed"; die;
            }
        }
        if (!empty($redis_server['dbnum'])) {
            $redis->select($redis_server['dbnum']);
        }
    } else {
        $redis = false;
    }

    $mqtt = false;
    
    # Check MySQL PHP modules are loaded
    if (!extension_loaded('mysql') && !extension_loaded('mysqli')){
       echo "Your PHP installation appears to be missing the MySQL extension(s) which are required by Emoncms. <br> See /php-info.php (restricted to local access)"; die;
    }
    
    # Check Gettext PHP  module is loaded
    if (!extension_loaded('gettext')){
       echo "Your PHP installation appears to be missing the gettext extension which is required by Emoncms. <br> See /php-info.php (restricted to local access)"; die;
    }

    $mysqli = @new mysqli($server,$username,$password,$database,$port);
    if ( $mysqli->connect_error ) {
        echo "Can't connect to database, please verify credentials/configuration in settings.php<br />";
        if ( $display_errors ) {
            echo "Error message: <b>" . $mysqli->connect_error . "</b>";
        }
        die();
    }
    // Set charset to utf8
    $mysqli->set_charset("utf8");

    if (!$mysqli->connect_error && $dbtest==true) {
        require "Lib/dbschemasetup.php";
        if (!db_check($mysqli,$database)) db_schema_setup($mysqli,load_db_schema(),true);
    }

    // 3) User sessions
    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis);

    $apikey = false;
    $devicekey = false;
    if (isset($_GET['apikey'])) {
        $apikey = $_GET['apikey'];
    } else if (isset($_POST['apikey'])) {
        $apikey = $_POST['apikey'];
    } else if (isset($_GET['devicekey'])) {
        $devicekey = $_GET['devicekey'];
    } else if (isset($_POST['devicekey'])) {
        $devicekey = $_POST['devicekey'];
    } else if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
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
              $log->error("Invalid API key '" . $apikey. "' | ".$_SERVER["REMOTE_ADDR"]);
              exit();
        }
    } else if ($devicekey && (@include "Modules/device/device_model.php")) {
        $device = new Device($mysqli,$redis);
        $session = $device->devicekey_session($devicekey);
        if (empty($session)) {
              header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
              header('WWW-Authenticate: Bearer realm="Device KEY", error="invalid_devicekey", error_description="Invalid device key"');
              print "Invalid device key";
              $log->error("Invalid device key '" . $devicekey. "'");
              exit();
        }
    } else {
        $session = $user->emon_session_start();
    }
    
    // 4) Language
    if (!isset($session['lang'])) $session['lang']='';
    set_emoncms_lang($session['lang']);

    // 5) Get route and load controller
    $route = new Route(get('q'), server('DOCUMENT_ROOT'), server('REQUEST_METHOD'));
    
    // Load get/post/encrypted parameters - only used by input/post and input/bulk API's
    $param = new Param($route,$user);
    
    // --------------------------------------------------------------------------------------
    // Special routes

    // Return brief device descriptor for hub detection
    if ($route->controller=="describe") { 
        header('Content-Type: text/plain');
        header('Access-Control-Allow-Origin: *');
        if(file_exists('/home/pi/data/emonbase')) {
            $type = 'emonbase';
        } elseif(file_exists('/home/pi/data/emonpi')) {
            $type = 'emonpi';
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

    if (get('embed')==1) $embed = 1; else $embed = 0;

    // If no route specified use defaults
    if ($route->isRouteNotDefined())
    {
        // EmonPi Setup Wizard
        if ($allow_emonpi_admin) {
            if (file_exists("Modules/setup")) {
                require "Modules/setup/setup_model.php";
                $setup = new Setup($mysqli);
                if ($setup->status()=="unconfigured") {
                    $default_controller = "setup";
                    $default_action = "";
                    // Provide special setup access to WIFI module functions
                    $_SESSION['setup_access'] = true;
                }
            }
        }
        
        if (!isset($session['read']) || (isset($session['read']) && !$session['read'])) {
            // Non authenticated defaults
            $route->controller = $default_controller;
            $route->action = $default_action;
            $route->subaction = "";
        } else {
            if (isset($session["startingpage"]) && $session["startingpage"]!="") {
                header('Location: '.$session["startingpage"]);
                die;
            } else {
                // Authenticated defaults
                $route->controller = $default_controller_auth;
                $route->action = $default_action_auth;
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

    if ($route->controller == 'input' && $route->action == 'bulk') $route->format = 'json';
    else if ($route->controller == 'input' && $route->action == 'post') $route->format = 'json';

    // 6) Load the main page controller
    $output = controller($route->controller);

    // If no controller of this name - then try username
    // need to actually test if there isnt a controller rather than if no content
    // is returned from the controller.
    if ($output['content'] == "#UNDEFINED#" && $public_profile_enabled && $route->controller!='admin')
    {
        $userid = $user->get_id($route->controller);
        if ($userid) {
            $route->subaction = $route->action;
            $session['userid'] = $userid;
            $session['username'] = $route->controller;
            $session['read'] = 1;
            $session['profile'] = 1;
            $route->controller = $public_profile_controller;
            $route->action = $public_profile_action;
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
    if ($output['content'] === "#UNDEFINED#") {
        header($_SERVER["SERVER_PROTOCOL"]." 406 Not Acceptable");
        $output['content'] = "URI not acceptable. No controller '" . $route->controller . "'. (" . $route->action . "/" . $route->subaction .")";
    }

    // If not authenticated and no ouput, asks for login
    if ($output['content'] == "" && (!isset($session['read']) || (isset($session['read']) && !$session['read']))) {
        $route->controller = "user";
        $route->action = "login";
        $route->subaction = "";
        $output = controller($route->controller);
    }

    $output['route'] = $route;
    $output['session'] = $session;

    // 7) Output
    if ($route->format == 'json')
    {
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
    }
    else if ($route->format == 'html')
    {
        // Select the theme
        $themeDir = "Theme/" . $theme . "/";
        if ($embed == 1) {
            print view($themeDir . "embed.php", $output);
        } else {
            $menu = load_menu();
            
            // EMONCMS MENU
            $menu['tabs'][] = array(
                'icon'=>'menu',
                'title'=> _("Emoncms"),
                'path' => 'feed/list',
                'order' => 0,
                'data'=> array(
                    'sidebar' => '#sidebar_emoncms'
                )
            );

            include ("Lib/misc/nav_functions.php");
            sortMenu($menu);
            // debugMenu('sidebar');
            
            $output['mainmenu'] = view($themeDir . "menu_view.php", array('menu'=>$menu));
            
            // add css class names to <body> tag based on controller's options
            $output['page_classes'][] = $route->controller;
            
            if($fullwidth) $output['page_classes'][] = 'fullwidth';

            if($session['read']){
                $output['sidebar'] = view($themeDir . "sidebar_view.php", 
                array(
                    'menu' => $menu,
                    'path' => $path,
                    'session' => $session,
                    'route' => $route
                ));
                $output['page_classes'][] = 'has-sidebar';
            }
            

            print view($themeDir . "theme.php", $output);
        }
    }
    else if ($route->format == 'text')
    {
        header('Content-Type: text/plain');
        print $output['content'];
    }

    else if ($route->format == 'csv')
    {
        header('Content-Type: text/csv');
        print $output['content'];
    }
    else {
        header($_SERVER["SERVER_PROTOCOL"]." 406 Not Acceptable");
        print "URI not acceptable. Unknown format '".$route->format."'.";
    }

    $ltime = microtime(true) - $ltime;

    // if ($session['userid']>0) {
    //  $redis->incr("user:postcount:".$session['userid']);
    //  $redis->incrbyfloat("user:reqtime:".$session['userid'],$ltime);
    // }
