<?php

    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */
    
    $emoncms_version = "8.1.2";

    $ltime = microtime(true);

    define('EMONCMS_EXEC', 1);

    // 1) Load settings and core scripts
    require "process_settings.php";
    require "core.php";
    require "route.php";
    require "locale.php";

    $path = get_application_path();

    require "Modules/log/EmonLogger.php";

    // 2) Database
    $mysqli = @new mysqli($server,$username,$password,$database);

    if (class_exists('Redis') && $redis_enabled) {
        $redis = new Redis();
        $connected = $redis->connect("127.0.0.1");
        if (!$connected) {
            echo "Can't connect to redis database, it may be that redis-server is not installed or started see readme for redis installation"; die;
        }
    } else {
        $redis = false;
    }
    
    if ( $mysqli->connect_error ) {
        echo "Can't connect to database, please verify credentials/configuration in settings.php<br />";
        if ( $display_errors ) {
            echo "Error message: <b>" . $mysqli->connect_error . "</b>";
        }
        die();
    }

    if (!$mysqli->connect_error && $dbtest==true) {
    require "Lib/dbschemasetup.php";
    if (!db_check($mysqli,$database)) db_schema_setup($mysqli,load_db_schema(),true);
    }

    // 3) User sessions
    require "Modules/user/rememberme_model.php";
    $rememberme = new Rememberme($mysqli);
    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,$rememberme);

    if (isset($_GET['apikey']))
    {
        $session = $user->apikey_session($_GET['apikey']);
    } 
    elseif (isset($_POST['apikey']))
    {
        $session = $user->apikey_session($_POST['apikey']);

    }
    else
    {
        $session = $user->emon_session_start();
    }

    // 4) Language
    if (!isset($session['lang'])) $session['lang']='';
    set_emoncms_lang($session['lang']);

    // 5) Get route and load controller
    $route = new Route(get('q'));

    if (get('embed')==1) $embed = 1; else $embed = 0;

    // If no route specified use defaults
    if (!$route->controller && !$route->action)
    {
    // Non authenticated defaults
    if (!$session['read'])
    {
        $route->controller = $default_controller;
        $route->action = $default_action;
    }
    else // Authenticated defaults
    {
        $route->controller = $default_controller_auth;
        $route->action = $default_action_auth;
    }
    }

    if ($route->controller == 'api') $route->controller = 'input';
    if ($route->controller == 'input' && $route->action == 'post') $route->format = 'json';
    if ($route->controller == 'input' && $route->action == 'bulk') $route->format = 'json';

    // 6) Load the main page controller
    $output = controller($route->controller);

    // If no controller of this name - then try username
    // need to actually test if there isnt a controller rather than if no content
    // is returned from the controller.
    if (!$output['content'] && $public_profile_enabled && $route->controller!='admin')
    {
    $userid = $user->get_id($route->controller);
    if ($userid) {
        $route->subaction = $route->action;
        $session['userid'] = $userid;
        $session['username'] = $route->controller;
        $session['read'] = 1;
        $session['profile'] = 1;
        $route->action = $public_profile_action;
        $output = controller($public_profile_controller);
    }
    }

    // $mysqli->close();

    // 7) Output
    if ($route->format == 'json')
    {
    header('Content-Type: application/json');
    if ($route->controller=='time') {
        print $output['content'];
    } elseif ($route->controller=='input' && $route->action=='post') {
        print $output['content'];
    } elseif ($route->controller=='input' && $route->action=='bulk') {
        print $output['content'];
    } else {
        print json_encode($output['content']);
    }
    }
    if ($route->format == 'html')
    {
    $menu = load_menu();
    $output['mainmenu'] = view("Theme/menu_view.php", array());
    if ($embed == 0) print view("Theme/theme.php", $output);
    if ($embed == 1) print view("Theme/embed.php", $output);
    }

    $ltime = microtime(true) - $ltime;

    // if ($session['userid']>0) {
    //  $redis->incr("user:postcount:".$session['userid']);
    //  $redis->incrbyfloat("user:reqtime:".$session['userid'],$ltime);
    // }
