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

function feed_controller()
{
    global $mysqli, $session, $route;
    $result = false;

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli);

    if ($route->format == 'html')
    {
        if ($route->action == "list" && $session['write']) $result = view("Modules/feed/Views/feedlist_view.php",array());
        if ($route->action == "api" && $session['write']) $result = view("Modules/feed/Views/feedapi_view.php",array());
    }

    if ($route->format == 'json')
    {
        // Public actions available on public feeds.
        if ($route->action == "list" && isset($_GET['userid'])) $result = $feed->get_user_public_feeds(get('userid'));

        if ($feed->belongs_to_user_or_public(get('id'),$session['userid']))
        {
            if ($route->action == "value") $result = $feed->get_field(get('id'),'value');
            if ($route->action == "get") $result = $feed->get_field(get('id'),get('field')); // '/[^\w\s-]/'
            if ($route->action == 'data') $result = $feed->get_data(get('id'),get('start'),get('end'),get('dp'));
            if ($route->action == 'histogram') $result = $feed->get_histogram_data(get('id'),get('start'),get('end'));
            if ($route->action == 'kwhatpower') $result = $feed->get_kwhd_atpower(get('id'),get('min'),get('max'));
        }

        // at least read session required
        if ($session['read'])
        {
            if ($route->action == "getid") $result = $feed->get_id($session['userid'],get('name'));
            if ($route->action == "list" && !isset($_GET['userid'])) $result = $feed->get_user_feeds($session['userid']);
        }

        // write session required
        if ($session['write'])
        {
            if ($route->action == "create") $result = $feed->create($session['userid'],get('name'),get('type'));
            if ($route->action == "emptybin") $result = $feed->permanently_delete_feeds($session['userid']);

            if ($feed->belongs_to_user(get('id'),$session['userid']))
            {
                if ($route->action == 'set') $result = $feed->set_feed_fields(get('id'),get('fields'));
                if ($route->action == "insert") $result = $feed->insert_data(get("id"),time(),get("time"),get("value"));
                if ($route->action == "update") $result = $feed->update_data(get('id'),time(),get("time"),get('value'));
                if ($route->action == "deletedatapoint") $result = $feed->delete_data(get('id'),get('feedtime'),get('feedtime'));
                if ($route->action == "delete") $result = $feed->delete(get('id'));
                if ($route->action == "restore") $result = $feed->restore(get('id'));
                if ($route->action == "export") $result = $feed->export(get('id'),get('start'));
            }
        }
    }

    return array('content'=>$result);
}
