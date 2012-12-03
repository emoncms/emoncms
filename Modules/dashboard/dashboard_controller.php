<?php

  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

  // dashboard/new						New dashboard
  // dashboard/delete 				POST: id=			Delete dashboard
  // dashboard/clone					POST: id=			Clone dashboard
  // dashboard/thumb 					List dashboards
  // dashboard/list         	List mode
  // dashboard/view?id=1			View and run dashboard (id)
  // dashboard/edit?id=1			Edit dashboard (id) with the draw editor
  // dashboard/ckeditor?id=1	Edit dashboard (id) with the CKEditor
  // dashboard/set POST				Set dashboard
  // dashboard/setconf POST 	Set dashboard configuration

  defined('EMONCMS_EXEC') or die('Restricted access');

  function dashboard_controller()
  {
    require "Modules/feed/feed_model.php";
    require "Modules/dashboard/dashboard_model.php";
    global $path, $session, $route;

    $format = $route['format'];
    $action = $route['action'];
    $subaction = $route['subaction'];

    $output['content'] = "";
    $output['message'] = "";

    //----------------------------------------------------------------------------------------------------------------------
    // New dashboard
    //----------------------------------------------------------------------------------------------------------------------
    if ($action == 'new' && $session['write']) // write access required
    {
      $dashid = new_dashboard($session['userid']);
      $output['message'] = _("New dashboard created");
    }

    //----------------------------------------------------------------------------------------------------------------------
    // Delete dashboard
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'delete' && $session['write']) // write access required
    {
      $output['message'] = delete_dashboard($session['userid'], intval(post("id")));
    }
		
    //----------------------------------------------------------------------------------------------------------------------
    // Clone dashboard
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'clone' && $session['write']) // write access required
    {
      $output['message'] = clone_dashboard($session['userid'], intval(post("id")));
    }
		
    //----------------------------------------------------------------------------------------------------------------------
    // List dashboards
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'list' && $session['read'])
    {
      $_SESSION['editmode'] = TRUE;
      if ($session['read']) $apikey = get_apikey_read($session['userid']);
      $dashboards = get_dashboard_list($session['userid'],0,0); 
      $menu = build_dashboard_menu($session['userid'],"edit");
      $user = get_user($session['userid']);
      if ($format == 'html') $output['content'] = view("dashboard/Views/dashboard_list_view.php", array('apikey'=>$apikey, 'dashboards'=>$dashboards,'menu'=>$menu, 'user'=>$user));
    }

    //----------------------------------------------------------------------------------------------------------------------
    // List of all public dashboards from all users
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'public')
    {
      $userlist = get_user_list();

      $dashboard_list = array();
      foreach ($userlist as $user)
      {
        $user_dash_list = get_dashboard_list($user['userid'],1,1);
        foreach ($user_dash_list as $user_dash)
        {
          $user_dash['username'] = $user['name'];
          $dashboard_list[] = $user_dash;
        }
      }

      if ($format == 'html') $output['content'] = view("dashboard/Views/dashboard_publiclist_view.php", array('dashboards'=>$dashboard_list));
    }

    //----------------------------------------------------------------------------------------------------------------------
    // Thumb List dashboards
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'thumb' && $session['read'])
    {
      $_SESSION['editmode'] = TRUE;
      if ($session['read']) $apikey = get_apikey_read($session['userid']);
      $dashboards = get_dashboard_list($session['userid'],0,0); 
      $menu = build_dashboard_menu($session['userid'],"edit");
      if ($format == 'html') $output['content'] = view("dashboard/Views/dashboard_thumb_view.php", array('apikey'=>$apikey, 'dashboards'=>$dashboards,'menu'=>$menu));
    }
    
    //----------------------------------------------------------------------------------------------------------------------
    // View or run dashboard (id)
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'view' && $session['read']) // write access required
    {
      $id = intval(get('id'));
      $alias = preg_replace('/[^a-z]/','',$subaction);

      if (!isset($session['profile'])) $session['profile'] = 0;

      if ($session['profile']==1){
        $public = 1; $published = 1; $action = "run";
      } else { 
        $public = 0; $published = 0;
      }

      if ($id) 
      {     
        // If a dashboard id is given we get the coresponding dashboard
        $dashboard = get_dashboard_id($session['userid'],$id, $public, $published);
      }
      elseif ($alias)
      {
        $dashboard = get_dashboard_alias($session['userid'],$alias, $public, $published);
      }
      else
      {  
        // Otherwise we get the main dashboard
        $dashboard = get_main_dashboard($session['userid']);
      }

      // URL ENCODE...
      if ($format == 'json') 
      {
        $output['content'] = urlencode($dashboard['content']);
        return $output;
      }
 
      $menu = build_dashboard_menu($session['userid'], $action);

      if ($session['profile']==1)
      {
        // In run mode dashboard menu becomes the main menu
        $output['runmenu'] =  '<div class="nav-collapse collapse">';
        $output['runmenu'] .= '<ul class="nav">'.$menu.'</ul>';
        $output['runmenu'] .= "</div>";
      }
      else
      {
        // Otherwise in view mode the dashboard menu is an additional grey menu
        $output['submenu'] = view("dashboard/Views/dashboard_menu.php", array('id'=>$dashboard['id'], 'menu'=>$menu, 'type'=>"view"));
      }
      
      //if ($dashboard_arr) 
      //{
        $apikey = get_apikey_read($session['userid']);
        $output['content'] = view("dashboard/Views/dashboard_view.php", array('dashboard'=>$dashboard, "apikey_read"=>$apikey));
	
			// If run mode avoid include dashboard configuration (this makes dashboard page lighter)
			if ($action!="run") {
        $output['content'] .= view("dashboard/Views/dashboard_config.php", array('dashboard'=>$dashboard));
			}
      
      //}
      //else
      //{
      //  $output['content'] = view("dashboard_run_errornomain.php",array());	
      //}
    }

    //----------------------------------------------------------------------------------------------------------------------
    // Edit dashboard (id) with the draw editor
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'edit' && $session['write']) // write access required
    {
      $id = intval(get('id'));
      $alias = preg_replace('/[^a-z]/','',$subaction);

      if ($id) 
      {     
        // If a dashboard id is given we get the coresponding dashboard
        $dashboard = get_dashboard_id($session['userid'],$id,0,0);
      }
      elseif ($alias)
      {
        $dashboard = get_dashboard_alias($session['userid'],$alias,0,0);
      }
      else
      {  
        // Otherwise we get the main dashboard
        $dashboard = get_main_dashboard($session['userid']);
      }

      //$apikey = get_apikey_read($session['userid']);
      $menu = build_dashboard_menu($session['userid'],"edit");
      $feedlist = get_user_feed_names($session['userid']);
      $output['content'] = view("dashboard/Views/dashboard_edit_view.php", array('dashboard'=>$dashboard, 'feedlist'=>$feedlist));

      $output['content'] .= view("dashboard/Views/dashboard_config.php", array('dashboard'=>$dashboard));
      $output['submenu'] = view("dashboard/Views/dashboard_menu.php", array('id'=>$dashboard['id'], 'menu'=>$menu, 'type'=>"edit"));
    }

    //----------------------------------------------------------------------------------------------------------------------
    // Edit dashboard (id) with the CKEditor
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'ckeditor' && $session['write'])
    {
      $id = intval(get('id'));
      $alias = preg_replace('/[^a-z]/','',$subaction);

      if ($id) 
      {     
        // If a dashboard id is given we get the coresponding dashboard
        $dashboard = get_dashboard_id($session['userid'],$id,0,0);
      }
      elseif ($alias)
      {
        $dashboard = get_dashboard_alias($session['userid'],$alias,0,0);
      }
      else
      {  
        // Otherwise we get the main dashboard
        $dashboard = get_main_dashboard($session['userid']);
      }

      $menu = build_dashboard_menu($session['userid'],"ckeditor");
      $output['content'] = view("dashboard/Views/dashboard_ckeditor_view.php",array('dashboard' => $dashboard,'menu'=>$menu));
      $output['submenu'] = view("dashboard/Views/dashboard_menu.php", array('id'=>$dashboard['id'], 'menu'=>$menu, 'type'=>"ckeditor"));
    }

    //----------------------------------------------------------------------------------------------------------------------
    // SET dashboard
    // dashboard/set?content=<h2>HelloWorld</h2>
    //----------------------------------------------------------------------------------------------------------------------
    if ($action == 'set' && $session['write']) // write access required
    {
      $content = post('content');
      if (!$content) $content = get('content');

      $id = intval(post('id'));
      if (!$id) $id = intval(get('id'));

      $height = intval(post('height'));
      if (!$height) $height = intval(get('height'));

      // IMPORTANT: if you get problems with characters being removed check this line:
      $content = preg_replace('/[^\w\s-.#<>?",;:=&\/%]/','',$content);	// filter out all except characters usually used

      $content = db_real_escape_string($content);

      set_dashboard_content($session['userid'],$content,$id,$height);

      if ($format == 'html')
      {
        $output['message'] = _("dashboard set");
      }
      else
      {
        $output['message'] = "ok";
      }
    }

    //----------------------------------------------------------------------------------------------------------------------
    // SET dashboard configuration
    //----------------------------------------------------------------------------------------------------------------------
    elseif ($action == 'setconf' && $session['write']) // write access required
    {
      $id = intval(post('id'));
      $name = preg_replace('/[^\w\s-]/','',post('name'));
      $alias = preg_replace('/[^a-z]/','',post('alias'));
      $description = preg_replace('/[^\w\s-]/','',post('description'));
       
	  // Separated functions to allow set values in independent way
      if (isset($_POST['main']))
      	set_dashboard_main($session['userid'],$id,intval($_POST['main']));
      
      if (isset($_POST['published']))
        set_dashboard_publish($session['userid'],$id,intval($_POST['published']));
      
      if (isset($_POST['public']))
        set_dashboard_public($session['userid'],$id,intval($_POST['public']));
      
      if (isset($_POST['name'])) 
        set_dashboard_name($session['userid'],$id,$name);
      
      if (isset($_POST['alias'])) 
        set_dashboard_alias($session['userid'],$id,$alias);
      
      if (isset($_POST['description']))
        set_dashboard_description($session['userid'],$id,$description);
      
			if (isset($_POST['showdescription']))
        set_dashboard_showdescription($session['userid'],$id,intval($_POST['showdescription']));
      
      //set_dashboard_conf($session['userid'],$id,$name,$alias,$description,$main,$public,$published);
	  
      $output['message'] = _("dashboard set configuration");
    }

    return $output;
  }

?>
