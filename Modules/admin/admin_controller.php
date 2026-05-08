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
    global $mysqli, $session, $route, $user;

    // Must be an admin
    if (!$session['admin']) {
        return array('success'=>false, 'content'=>'', 'reauth'=>true, 'message'=>"Admin re-authentication required");
    }

    // Must have write access
    if (!$session['write']) {
        return array('success'=>false, 'content'=>'', 'reauth'=>true, 'message'=>"Admin re-authentication required");
    }

    // Load admin model
    require_once "Modules/admin/admin_model.php";
    $admin_model = new AdminModel($mysqli, $user);

    // Admin user list view
    if ($route->action == 'users') {
        $route->format = 'html';
        return view("Modules/admin/Views/userlist_view.php", array());
    }

    // Switch to another user by id (admin only)
    if ($route->action == 'setuser') {
        $admin_model->setUser(get("id",true));
    }

    // Switch to another user by feedid (admin only)
    if ($route->action == 'setuserfeed') {
        $admin_model->setUserFeed(get("feedid", true));
    }

    // Get total number of users (admin only)
    if ($route->action == 'numberofusers') {
        $route->format = 'text';
        return $admin_model->numberOfUsers();
    }

    // Get paginated list of users (admin only)
    if ($route->action == 'userlist') {
        $route->format = 'json';
        return $admin_model->userList(
            get("page"),
            get("perpage"),
            get("orderby"),
            get("order"),
            get("search")
        );
    }

    return EMPTY_ROUTE;
}
