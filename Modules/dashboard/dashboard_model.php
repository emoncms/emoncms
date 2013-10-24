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
class Dashboard
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function create($userid)
    {
      $userid = (int) $userid;
      $sql = ("INSERT INTO dashboard (userid, alias) VALUES ('$userid', '');");
      $result = db_query($this->conn, $sql);

      return db_lastval($this->conn, $result);
    }

    public function delete($id)
    {
      $id = (int) $id;
      $sql = ("DELETE FROM dashboard WHERE id = '$id';");
      $result = db_query($this->conn, $sql);
      return $result; /* check if affected rows is more appropiate here */
    }

    public function dashclone($userid, $id)
    {
      $userid = (int) $userid;
      $id = (int) $id;

	    // Get content, name and description from origin dashboard
      $sql = ("SELECT content, name, description FROM dashboard WHERE userid = '$userid' AND id='$id';");
      $result = db_query($this->conn, $sql);
      $row = db_fetch_array($result);
	
	    // Name for cloned dashboard
	    $name = $row['name']._(' clone');

      $sql = ("INSERT INTO dashboard (userid, content, name, description) VALUES ('$userid', '{$row['content']}', '$name', '{$row['description']}');");
      $result = db_query($this->conn, $sql);
	
	    return db_lastval($this->conn, $result);
    }

    public function get_list($userid, $public, $published)
    {
      $userid = (int) $userid;

      $qB = ""; $qC = "";
      if ($public==true) $qB = " and public=1";
      if ($published==true) $qC = " and published=1";
      $sql = ("SELECT id, name, alias, description, main, published, public, showdescription FROM dashboard WHERE userid='$userid'".$qB.$qC);
      $result = db_query($this->conn, $sql);
      $list = array();
      while ($row = db_fetch_object($result))
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

    public function set_content($userid, $id, $content, $height)
    {
      $userid = (int) $userid;
      $id = (int) $id;
      $height = (int) $height;
      $content = db_real_escape_string($this->conn, $content);
      
      //echo $content;

      $sql = ("SELECT id FROM dashboard WHERE userid = '$userid' AND id = '$id';");
      $result = db_query($this->conn, $sql);
      $row = db_fetch_array($result);
      if ($row) {
          $sql = ("UPDATE dashboard SET content = '$content', height = '$height' WHERE userid = '$userid' AND id = '$id';");
          db_query($this->conn, $sql);
      }

      return array('success'=>true);
    }

    public function set($userid,$id,$fields)
    {
      $userid = (int) $userid;
      $id = (int) $id;
      $fields = json_decode(stripslashes($fields));

      $array = array();

      // content, height, name, alias, description, main, public, published, showdescription
      // Repeat this line changing the field name to add fields that can be updated:

      if (isset($fields->height)) $array[] = "height = '".intval($fields->height)."'";
      if (isset($fields->content)) $array[] = "content = '".preg_replace('/[^\w\s-.#<>?",;:=&\/%]/','',$fields->content)."'";

      if (isset($fields->name)) $array[] = "name = '".preg_replace('/[^\w\s-]/','',$fields->name)."'";
      if (isset($fields->alias)) $array[] = "alias = '".preg_replace('/[^\w\s-]/','',$fields->alias)."'";
      if (isset($fields->description)) $array[] = "description = '".preg_replace('/[^\w\s-]/','',$fields->description)."'";

      if (isset($fields->main)) 
      {
        $main = (bool)$fields->main;
        if ($main) {
	    $sql = ("UPDATE dashboard SET main = FALSE WHERE userid = '$userid' and id <> '$id';");
	    db_query($this->conn, $sql);
	}
        $array[] = "main = '" . $main . "'";
      }

      if (isset($fields->public)) $array[] = "public = '".((bool)$fields->public)."'";
      if (isset($fields->published)) $array[] = "published = '".((bool)$fields->published)."'";
      if (isset($fields->showdescription)) $array[] = "public = '".((bool)$fields->showdescription)."'";
      // Convert to a comma seperated string for the mysql query
      $fieldstr = implode(",",$array);

      $sql = ("UPDATE dashboard SET " . $fieldstr . " WHERE userid = '$userid' and id = '$id';");
      $result = db_query($this->conn, $sql);

      if (db_affected_rows($this->conn, $result)>0){
        return array('success'=>true, 'message'=>'Field updated');
      } else {
        return array('success'=>false, 'message'=>'Field could not be updated');
      }
    }

    // Return the main dashboard from $userid
    public function get_main($userid)
    {
      $userid = (int) $userid;
      $sql = ("SELECT * FROM dashboard WHERE userid = '$userid' AND main = 'TRUE';");
      $result = db_query($this->conn, $sql);
      return db_fetch_array($result);
    }

    public function get($userid, $id, $public, $published)
    {
      $userid = (int) $userid;
      $id = (int) $id;
      $qB = ""; if ($public==true) $qB = " AND public = 1";
      $qC = ""; if ($published==true) $qC = " AND published = 1";
      $sql = ("SELECT * FROM dashboard WHERE userid = '$userid' and id = '$id'" . $qB . $qC . ";");
      $result = db_query($this->conn, $sql);
      return db_fetch_array($result);
    }

    // Returns the $id dashboard from $userid
    public function get_from_alias($userid, $alias, $public, $published)
    {
      $userid = (int) $userid;
      $alias = preg_replace('/[^\w\s-]/','',$alias);
      $qB = ""; if ($public==true) $qB = " AND public = 1";
      $qC = ""; if ($published==true) $qC = " AND published = 1";
      $sql = ("SELECT * FROM dashboard WHERE userid = '$userid' AND alias = '$alias'" . $qB . $qC . ";");
      $result = db_query($this->conn, $sql);
      return db_fetch_array($result);
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
      $topmenu="";
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
      return $topmenu;
    }

}

