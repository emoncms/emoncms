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

/**
 * debug method wrapper for print_r
 *
 * If debug is disabled nothing is output.
 *
 * @return void
 */
function pr($var) 
{
    if (!Configure::read('debug')) 
    {
        return;
    }
    echo sprintf('<pre>%s</pre>', print_r($var, true));
}

function get_application_path()
{
    // Default to http protocol
    $proto = "http";

    // Detect if we are running HTTPS or proxied HTTPS
    if (env('HTTPS') == 'on') {
        // Web server is running native HTTPS
        $proto = "https";
    } elseif (env('HTTP_X_FORWARDED_PROTO') == "https") {
        // Web server is running behind a proxy which is running HTTPS
        $proto = "https";
    }

    if(!empty(env('HTTP_X_FORWARDED_SERVER'))) {
        return dirname("$proto://" . env('HTTP_X_FORWARDED_SERVER') . env('SCRIPT_NAME')) . "/";
    }

    return dirname("$proto://" . env('HTTP_HOST') . env('SCRIPT_NAME')) . "/";
}

function db_check($mysqli,$database)
{
    $result = $mysqli->query("SELECT count(table_schema) as count from information_schema.tables WHERE table_schema = '$database'")->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

function controller($controller_name)
{
    $output = array('content'=>'');

    if ($controller_name)
    {
        $controller = $controller_name."_controller";
        $controllerScript = "Modules/".$controller_name . DS . $controller.".php";
        if (is_file($controllerScript))
        {
            $domain = 'messages';
            bindtextdomain($domain, 'Modules' . DS . $controller_name . DS . 'locale');
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
    include $filepath;
    $content = ob_get_clean();
    return $content;
}

function get($index)
{
    return globalValue('get', $index);
}

function post($index)
{
    return globalValue('post', $index);
}

function globalValue($type, $index) 
{
    switch ($type) 
    {
        case 'post':
            $return = isset($_POST[$index]) ? $_POST[$index] : null;
            break;

        case 'get':
            $return = isset($_GET[$index]) ? $_GET[$index] : null;
            break;
    }  

    if (get_magic_quotes_gpc() && !is_array($return)) 
    {
        return stripslashes($return);
    }

    return $return;
}

function load_db_schema()
{
    $schema = array();
    $dir = scandir('Modules');
    for ($i = 2; $i < count($dir); $i++)
    {
        $file = 'Modules' . DS . $dir[$i] . DS . $dir[$i] . '_schema.php';
        if (is_file($file))
        {
            require $file;
        }
    }
    return $schema;
}

function load_menu()
{
    $menu_left = $menu_right = $menu_dropdown = array();

    $dir = scandir('Modules');
    for ($i = 2; $i < count($dir); $i++)
    {
        $file = 'Modules' . DS . $dir[$i] . DS . $dir[$i] . '_menu.php';
        if (is_file($file))
        {
            require $file;
        }
    }

    usort($menu_left, function ($a, $b) {
        return $a['order'] > $b['order'];
    });
    return array(
        'left' => $menu_left,
        'right' => $menu_right,
        'dropdown' => $menu_dropdown,
    );
}

if (!function_exists('env')) {

/**
 * Gets an environment variable from available sources, and provides emulation
 * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
 * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
 * environment information.
 *
 * @param string $key Environment variable name.
 * @return string Environment variable setting.
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#env
 */
    function env($key) {
        if ($key === 'HTTPS') {
            if (isset($_SERVER['HTTPS'])) {
                return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            }
            return (strpos(env('SCRIPT_URI'), 'https://') === 0);
        }

        if ($key === 'SCRIPT_NAME') {
            if (env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
                $key = 'SCRIPT_URL';
            }
        }

        $val = null;
        if (isset($_SERVER[$key])) {
            $val = $_SERVER[$key];
        } elseif (isset($_ENV[$key])) {
            $val = $_ENV[$key];
        } elseif (getenv($key) !== false) {
            $val = getenv($key);
        }

        if ($key === 'REMOTE_ADDR' && $val === env('SERVER_ADDR')) {
            $addr = env('HTTP_PC_REMOTE_ADDR');
            if ($addr !== null) {
                $val = $addr;
            }
        }

        if ($val !== null) {
            return $val;
        }

        switch ($key) {
            case 'DOCUMENT_ROOT':
                $name = env('SCRIPT_NAME');
                $filename = env('SCRIPT_FILENAME');
                $offset = 0;
                if (!strpos($name, '.php')) {
                    $offset = 4;
                }
                return substr($filename, 0, -(strlen($name) + $offset));
            case 'PHP_SELF':
                return str_replace(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
            case 'CGI_MODE':
                return (PHP_SAPI === 'cgi');
            case 'HTTP_BASE':
                $host = env('HTTP_HOST');
                $parts = explode('.', $host);
                $count = count($parts);

                if ($count === 1) {
                    return '.' . $host;
                } elseif ($count === 2) {
                    return '.' . $host;
                } elseif ($count === 3) {
                    $gTLD = array(
                        'aero',
                        'asia',
                        'biz',
                        'cat',
                        'com',
                        'coop',
                        'edu',
                        'gov',
                        'info',
                        'int',
                        'jobs',
                        'mil',
                        'mobi',
                        'museum',
                        'name',
                        'net',
                        'org',
                        'pro',
                        'tel',
                        'travel',
                        'xxx'
                    );
                    if (in_array($parts[1], $gTLD)) {
                        return '.' . $host;
                    }
                }
                array_shift($parts);
                return '.' . implode('.', $parts);
        }
        return null;
    }

}