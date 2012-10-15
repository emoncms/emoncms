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
 * Create a new user dashboard
 * 
 */
function new_dashboard($userid)
{
  // If it is first user dashboard, set it the main one or no one exists 
  if (!get_main_dashboard($userid))
    db_query("INSERT INTO dashboard (`userid`,`main`) VALUES ('$userid',TRUE)");  
  else
    db_query("INSERT INTO dashboard (`userid`) VALUES ('$userid')");
  
  return db_insert_id();
}

function delete_dashboard($userid, $id)
{
  $result = db_query("DELETE FROM dashboard WHERE userid = '$userid' AND id = '$id'");
  return $result;
}

function clone_dashboard($userid, $id)
{
	// Get content, name and description from origin dashboard
  $result = db_query("SELECT content,name,description FROM dashboard WHERE userid = '$userid' AND id='$id'");
  $row = db_fetch_array($result);
	
	// Name for cloned dashboard
	$name = $row['name']._(' clone');

  db_query("INSERT INTO dashboard (`userid`,`content`,`name`,`description`) VALUES ('$userid','{$row['content']}','$name','{$row['description']}')");
	
	return db_insert_id();
}

function get_dashboard_list($userid, $public, $published)
{
  $qB = ""; $qC = "";
  if ($public) $qB = " and public=1";
  if ($published) $qC = " and published=1";
  $result = db_query("SELECT id, name, alias, description, main, published, public, showdescription FROM dashboard WHERE userid='$userid'".$qB.$qC);

  $list = array();
  while ($row = db_fetch_array($result)) $list[] = $row;
  return $list;
}


function set_dashboard_content($userid, $content, $id)
{
  $result = db_query("SELECT * FROM dashboard WHERE userid = '$userid' AND id='$id'");
  $row = db_fetch_array($result);

  if ($row)
  {
    db_query("UPDATE dashboard SET content = '$content' WHERE userid='$userid' AND id='$id'");
  }
  else
  {
    db_query("INSERT INTO dashboard (`userid`,`content`,`id`) VALUES ('$userid','$content','$id')");
  }
}

/*
 * Sets dashboard $id of $userid with $name
 */
function set_dashboard_name($userid, $id, $name)
{
  db_query("UPDATE dashboard SET name = '$name' WHERE userid='$userid' AND id='$id'"); 
}

function set_dashboard_description($userid, $id, $description)
{
  db_query("UPDATE dashboard SET description = '$description' WHERE userid='$userid' AND id='$id'"); 
}

function set_dashboard_alias($userid, $id, $alias)
{
  db_query("UPDATE dashboard SET alias = '$alias' WHERE userid='$userid' AND id='$id'"); 
}

function set_dashboard_conf($userid, $id, $name, $alias, $description, $main, $public, $published)
{
  $result = db_query("SELECT id FROM dashboard WHERE userid = '$userid' AND id='$id'");
  $row = db_fetch_array($result);

  if ($row)
  {
    db_query("UPDATE dashboard SET name = '$name', alias = '$alias', description = '$description', public = '$public', published = '$published' WHERE userid='$userid' AND id='$id'");

    // set user main dashboard
    if ($main == '1')
    {
      db_query("UPDATE dashboard SET main = FALSE WHERE userid='$userid' AND id<>'$id'");

      // set main to the main dashboard
      db_query("UPDATE dashboard SET main = TRUE WHERE userid='$userid' AND id='$id'");
    }
    else
    {
      // set main to false all other user dashboards
      db_query("UPDATE dashboard SET main = FALSE WHERE userid='$userid' AND id='$id'");
    }
  }
}

// Return the main dashboard from $userid
function get_main_dashboard($userid)
{
  $result = db_query("SELECT * FROM dashboard WHERE userid='$userid' and main=TRUE");
  return db_fetch_array($result);
}

// Returns the $id dashboard from $userid
function get_dashboard_id($userid, $id, $public, $published)
{
  $qB = ""; if ($public) $qB = " and public=1";
  $qC = ""; if ($published) $qC = " and published=1";

  $result = db_query("SELECT * FROM dashboard WHERE userid='$userid' and id='$id'".$qB.$qC);
  return db_fetch_array($result);
}

// Returns the $id dashboard from $userid
function get_dashboard_alias($userid, $alias, $public, $published)
{
  $qB = ""; if ($public) $qB = " and public=1";
  $qC = ""; if ($published) $qC = " and published=1";

  $result = db_query("SELECT * FROM dashboard WHERE userid='$userid' and alias='$alias'".$qB.$qC);
  return db_fetch_array($result);
}

/*
 * Set a $id dashboard from $userid as main dashboard
 * Only one dashboard can be main so first set all dashboards main property to false if new main dashboard is set
 * Main dashboard is set published too
 */
function set_dashboard_main($userid, $id, $main)
{
  // set user main dashboard
  if ($main == '1')
  {
	// set main to false all other user dashboards  	
    db_query("UPDATE dashboard SET main = FALSE WHERE userid='$userid' AND id<>'$id'");

    // set main to the main dashboard
    db_query("UPDATE dashboard SET main = TRUE WHERE userid='$userid' AND id='$id'");
    
    // main dashboard must be published
    set_dashboard_publish($userid,$id,'1');
  }
  else
  {       
    db_query("UPDATE dashboard SET main = FALSE WHERE userid='$userid' AND id='$id'");
  }
}

/*
 * Set a $id dashboard from $userid as published/unpublished dashboard
 * 
 */
function set_dashboard_publish($userid, $id, $published)
{
  if ($published == '1')  
    db_query("UPDATE dashboard SET published = TRUE WHERE userid='$userid' AND id='$id'");
  else
    db_query("UPDATE dashboard SET published = FALSE WHERE userid='$userid' AND id='$id'");
}

/*
 * Set a $id dashboard from $userid as public/private dashboard
 * 
 */
function set_dashboard_public($userid, $id, $public)
{
  if ($public == '1')  
    db_query("UPDATE dashboard SET public = TRUE WHERE userid='$userid' AND id='$id'");
  else
    db_query("UPDATE dashboard SET public = FALSE WHERE userid='$userid' AND id='$id'");
}

/*
 * Set showdescription property
 */
function set_dashboard_showdescription($userid, $id, $showdescription)
{
  if ($showdescription == '1')  
    db_query("UPDATE dashboard SET showdescription = TRUE WHERE userid='$userid' AND id='$id'");
  else
    db_query("UPDATE dashboard SET showdescription = FALSE WHERE userid='$userid' AND id='$id'");
}

function build_dashboard_menu($userid,$location)
{
  global $path, $session;
  $public = 0; $published = 0;
  if ($location!="run") { $dashpath = 'dashboard/'.$location; } else { $dashpath = $session['username'];   $public = !$session['write']; $published = 1;}

  $dashboards = get_dashboard_list($userid, $public, $published);
  $topmenu="";
  foreach ($dashboards as $dashboard)
  {
  	// Check show description
  	if ($dashboard['showdescription']) 
  	{		 
  		$desc = ' title="'.$dashboard['description'].'"';
  	}
		else 
		{
			$desc = '';
		} 
		
		// Set URL using alias or id
    if ($dashboard['alias'])
    {
    	$aliasurl = "/".$dashboard['alias'];
    }
    else
    {
    	$aliasurl = '&id='.$dashboard['id'];    	
    }

		// Build the menu item
  	$topmenu.='<li><a href="'.$path.$dashpath.$aliasurl.'"'.$desc.'>'.$dashboard['name'].'</a></li>';		
  }
  return $topmenu;
}


