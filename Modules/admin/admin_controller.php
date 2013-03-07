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

function admin_controller()
{
    global $mysqli,$session, $route;

    if ($route->action == 'view' && $session['write'] && $session['admin']) $result = view("Modules/admin/admin_main_view.php", array());

    if ($route->action == 'db' && $session['write'] && $session['admin'])
    {
        require "Lib/dbschemasetup.php";
        $out = db_schema_setup($mysqli,load_db_schema());
        $result = view("Modules/admin/admin_db_view.php", array('out'=>$out));
    }

    return array('content'=>$result);
}
