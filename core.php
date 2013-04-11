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

    if( isset( $_SERVER['HTTP_X_FORWARDED_SERVER'] ))
        $path = dirname("$proto://" . server('HTTP_X_FORWARDED_SERVER') . server('SCRIPT_NAME')) . "/";
    else
        $path = dirname("$proto://" . server('HTTP_HOST') . server('SCRIPT_NAME')) . "/";

    return $path;
}

function db_check($mysqli,$database)
{
  $result = $mysqli->query("SELECT count(table_schema) from information_schema.tables WHERE table_schema = '$database'");
  $row = $result->fetch_array();
  if ($row['0']>0) return true; else return false;
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
    include "$filepath";   
    $content = ob_get_clean();
    return $content;
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
    $menu_dropdown = array();

    $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
        if (filetype("Modules/".$dir[$i])=='dir') 
        {
          $classname = $dir[$i].'_module';               
          $module = new $classname();
          $module->getmenu($menu_left);
        }
    }

    usort($menu_left, "menu_sort");
    return array('left'=>$menu_left, 'dropdown'=>$menu_dropdown);
}

// Menu sort by order
function menu_sort($a,$b) {
    return $a['order']>$b['order'];
}

//////// modules test
function get_modules()
{
  $modules = array();
  
  $dir = scandir("Modules");
    for ($i=2; $i<count($dir); $i++)
    {
        if (filetype("Modules/".$dir[$i])=='dir') 
        {
          $classname = $dir[$i].'_module';               
          $modules[] = $classname;
        }
    }

  return $modules;  
}

function __autoload($className)
{ 
  $file = 'Modules/'.str_replace('_module', '', $className).'/'.str_replace('_module', '_class.php', $className);

  if(!file_exists($file))
    return false;
  else   
    require_once $file;          
}

function rendertemplate($position)
{
  $modules = get_modules();
  $render = "";
  
  foreach ($modules as $module)
  {
    $module_class = new $module();    
    if ($module_class instanceof iHTLMModule) {      
      $render = $render . $module_class->moduleHTMLRender($position);
    }  
  }  
  echo $render;
}

interface iModule
{ 
  public function getmenu(&$menu_left);
  public function modulename();
  public function moduleversion();
  public function moduletype();
  public function moduledescription();  
}

interface iHTLMModule
{
  public function moduleHTMLRender($position);
}
