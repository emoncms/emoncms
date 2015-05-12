<?php

/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

defined('EMONCMS_EXEC') or die('Restricted access');

function dashboard_controller()
{
    global $mysqli, $path, $session, $route, $user;

    require "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);

    // id, userid, content, height, name, alias, description, main, public, published, showdescription

    $result = false; $submenu = '';

    if ($route->format == 'html')
    {
        if ($route->action == "list" && $session['write'])
        {
            $result = view("Modules/dashboard/Views/dashboard_list.php",array());

            $menu = $dashboard->build_menu($session['userid'],"view");
            $submenu = view("Modules/dashboard/Views/dashboard_menu.php", array('menu'=>$menu, 'type'=>"list"));
        }

        else if ($route->action == "view" && $session['read'])
        {
            if ($route->subaction) $dash = $dashboard->get_from_alias($session['userid'],$route->subaction,false,false);
            elseif (isset($_GET['id'])) $dash = $dashboard->get($session['userid'],get('id'),false,false);
            else $dash = $dashboard->get_main($session['userid']);

            if ($dash) {
              $result = view("Modules/dashboard/Views/dashboard_view.php",array('dashboard'=>$dash));
            } else {
              $result = view("Modules/dashboard/Views/dashboard_list.php",array());
            }

            $menu = $dashboard->build_menu($session['userid'],"view");
            $submenu = view("Modules/dashboard/Views/dashboard_menu.php", array('id'=>$dash['id'], 'menu'=>$menu, 'type'=>"view"));
        }

        else if ($route->action == "edit" && $session['write'])
        {
            if ($route->subaction) $dash = $dashboard->get_from_alias($session['userid'],$route->subaction,false,false);
            elseif (isset($_GET['id'])) $dash = $dashboard->get($session['userid'],get('id'),false,false);

            $result = view("Modules/dashboard/Views/dashboard_edit_view.php",array('dashboard'=>$dash));
            $result .= view("Modules/dashboard/Views/dashboard_config.php", array('dashboard'=>$dash));

            $menu = $dashboard->build_menu($session['userid'],"edit");
            $submenu = view("Modules/dashboard/Views/dashboard_menu.php", array('id'=>$dash['id'], 'menu'=>$menu, 'type'=>"edit"));
        }
    }
    else if ($route->format == 'json')
    {
        if ($session['write']) {
            if ($route->action=='list') $result = $dashboard->get_list($session['userid'], false, false);
            else if ($route->action=='set') $result = $dashboard->set($session['userid'],get('id'),get('fields'));
            else if ($route->action=='setcontent') $result = $dashboard->set_content($session['userid'],post('id'),post('content'),post('height'));
            else if ($route->action=='create') $result = $dashboard->create($session['userid']);
            else if ($route->action=='delete') $result = $dashboard->delete(get('id'));
            else if ($route->action=='clone') $result = $dashboard->dashclone($session['userid'], get('id'));
        }
    }
    return array('content'=>$result, 'submenu'=>$submenu);
}