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

  function get_application_path()
  {
    // Default to http protocol
    $proto = "http";

    // Detect if we are running HTTPS or proxied HTTPS
    if (server('HTTPS') == 'on') {
      // Web server is running native HTTPS
      $proto = "https";
    }
    elseif (server('HTTP_X_FORWARDED_PROTO') == "https") {
      // Web server is running behind a proxy which is running HTTPS
      $proto = "https";
    }

    if( isset( $_SERVER['HTTP_X_FORWARDED_SERVER'] ))
        $path = dirname("$proto://" . server('HTTP_X_FORWARDED_SERVER') . server('SCRIPT_NAME')) . "/";
    else
        $path = dirname("$proto://" . server('HTTP_HOST') . server('SCRIPT_NAME')) . "/";

    return $path;
  }

  function emon_session_start()
  {
    session_set_cookie_params(
            3600 * 24 * 30, //lifetime, 30 days
            "/", //path
            "", //domain 
            false, //secure
            true//http_only
    );
    session_start();
    if (isset($_SESSION['admin'])) $session['admin'] = $_SESSION['admin']; else $session['admin'] = 0;
    if (isset($_SESSION['read'])) $session['read'] = $_SESSION['read']; else $session['read'] = 0;
    if (isset($_SESSION['write'])) $session['write'] = $_SESSION['write']; else $session['write'] = 0;
    if (isset($_SESSION['userid'])) $session['userid'] = $_SESSION['userid']; else $session['userid'] = 0;
    return $session;
  }

  function get($index)
  {
    $val = null;
    if (isset($_GET[$index])) $val = $_GET[$index];
    return $val;
  }

  function post($index)
  {
    $val = null;
    if (isset($_POST[$index])) $val = $_POST[$index];
    return $val;
  }

  function server($index)
  {
    $val = null;
    if (isset($_SERVER[$index])) $val = $_SERVER[$index];
    return $val;
  }

  function decode_route($q)
  {
    // Init vars
    $route = array();
    $route['format'] = "html";
    $route['controller'] = '';
    $route['action'] = '';
    $route['subaction'] = '';

    // filter out all except a-z / .
    $q = preg_replace('/[^.\/A-Za-z0-9]/', '', $q);

    // Split
    $args = preg_split('/[\/]/', $q);

    // get format (part of last argument after . i.e view.json)
    $lastarg = sizeof($args) - 1;
    $lastarg_split = preg_split('/[.]/', $args[$lastarg]);
    if (count($lastarg_split) > 1) { $route['format'] = $lastarg_split[1]; }
    $args[$lastarg] = $lastarg_split[0];

    if (count($args) > 0) { $route['controller'] = $args[0]; }
    if (count($args) > 1) { $route['action'] = $args[1]; }
    if (count($args) > 2) { $route['subaction'] = $args[2]; }

    return $route;
  }

  function controller($controller_name)
  {
    $output = array('content'=>'');

    if ($controller_name)
    {
      $controller = $controller_name."_controller";
      $controllerScript = "Modules/".$controller_name."/".$controller.".php";   
      if (is_file($controllerScript))
      {
        // Load language files for module
        $domain = "messages";
        bindtextdomain($domain, "Modules/".$controller_name."/locale");
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);

        require $controllerScript;
        $output = $controller();
      }
    }

    return $output;
  }

  function view($filepath, array $args)
  {
    extract($args);
    ob_start();       
    include "Modules/$filepath";   
    $content = ob_get_clean();
    return $content;
  }

  function theme($filepath, array $args)
  {
    global $theme;
    extract($args);
    ob_start();       
    include "Theme/$theme/$filepath";   
    $content = ob_get_clean();
    return $content;
  }

  function load_db_schema()
  {
    $schema = array();
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
      if (filetype("Modules/".$dir[$i])=='dir') 
      {
        if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_schema.php"))
        {
          require "Modules/".$dir[$i]."/".$dir[$i]."_schema.php";
        }
      }
    }
    return $schema;
  }

  function load_menu()
  {
    $menu_left = array();
    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
      if (filetype("Modules/".$dir[$i])=='dir') 
      {
        if (is_file("Modules/".$dir[$i]."/".$dir[$i]."_menu.php"))
        {
          require "Modules/".$dir[$i]."/".$dir[$i]."_menu.php";
        }
      }
    }
    return $menu_left;
  }


?>
