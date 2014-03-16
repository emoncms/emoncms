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
    global $mysqli,$session,$route,$updatelogin;

    // Allow for special admin session if updatelogin property is set to true in settings.php
    // Its important to use this with care and set updatelogin to false or remove from settings
    // after the update is complete.

    if (!$updatelogin && !$session['admin'])
    {
        return array('content' => '');
    }

    if ($route->action == 'view') 
    {
        $result = view('Modules/admin/admin_main_view.php', array());
    }

    if ($route->action == 'db')
    {
        $applychanges = (bool)get('apply');

        require 'Modules/admin/update_class.php';
        require_once 'Lib/dbschemasetup.php';

        $update = new Update($mysqli);

        $updates = array(
            array(
                'title' => 'Database schema',
                'description' => '',
                'operations' => db_schema_setup($mysqli,load_db_schema(),$applychanges)
            )
        );

        if (empty($updates[0]['operations'])) 
        {
            foreach ($update->methodsToRun() as $method) 
            {
                $updates[] = $update->{$method}($applychanges);
            }
        }

        $result = view('Modules/admin/update_view.php', array(
            'applychanges' => $applychanges, 
            'updates' => $updates,
        ));
    }

    if ($session['write'] && $session['admin']) 
    {
        switch ($route->action) 
        {
            case 'users':
                $result = view('Modules/admin/userlist_view.php', array());
                break;

            case 'userlist':
                $data = $mysqli->query('SELECT id, username, email FROM users');
                $result = array();
                while ($row = $data->fetch_object()) 
                {
                    $result[] = $row;
                }
                break;

            case 'setuser':
                $_SESSION['userid'] = (int)get('id');
                header('Location: ../user/view');
                break;
        }
    }

    return array('content'=>$result);
}
