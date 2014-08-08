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

/*
 * Create a new user dashboard menu item
 *
 */
class Dashboardmenu
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function get_list($userid, $public, $published)
    {
        $userid = (int) $userid;

        $qB = ""; $qC = "";
        if ($public==true) $qB = " and public=1";
        if ($published==true) $qC = " and published=1";
        $result = $this->mysqli->query("SELECT id, name, alias, description, main, published, public, showdescription FROM dashboard WHERE userid='$userid'".$qB.$qC);

        $list = array();
        while ($row = $result->fetch_object())
        {
        $list[] = array (
            'id' => (int) $row->id,
            'name' => $row->name,
            'alias' => $row->alias,
            'showdescription' => (bool) $row->showdescription,
            'description' => $row->description,
            'main' => (bool) $row->main,
            'published'=> (bool) $row->published,
            'public'=> (bool) $row->public
        );
        }
        return $list;
    }
    
    public function build_menu($userid,$location)
    {
        global $path, $session;
        $userid = (int) $userid;

        $public = 0; $published = 0;

        if (isset($session['profile']) && $session['profile']==1) {
            $dashpath = $session['username'];
            $public = !$session['write'];
            $published = 1;
        } else {
            $dashpath = 'dashboard/'.$location;
        }

        $dashboards = $this->get_list($userid, $public, $published);
        $arraycheck_dashboards = array_filter($dashboards);
        
        $topmenu="";

		if (!empty($arraycheck_dashboards)) 
		{
			if ($session['write']) 
			{
			  $topmenu.='<li><a href="'.$path.'dashboard/list"><i class="icon-th-list" title="List view"></i> View List</a></li>';
			  $topmenu.='<li class="divider"></li>';
			}

			foreach ($dashboards as $dashboard)
			{
				// Check show description
				if ($dashboard['showdescription']) {
						$desc = ' title="'.$dashboard['description'].'"';
				} else {
						$desc = '';
				}

					// Set URL using alias or id
				if ($dashboard['alias']) {
					$aliasurl = "/".$dashboard['alias'];
				} else {
					$aliasurl = '&id='.$dashboard['id'];
				}

					// Build the menu item
				$topmenu.='<li><a href="'.$path.$dashpath.$aliasurl.'"'.$desc.'>'.$dashboard['name'].'</a></li>';
			}

		}
		
		return $topmenu;
    
	}

}