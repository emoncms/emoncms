<?php
  /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    USER CONTROLLER ACTIONS		ACCESS

    login?name=john&pass=test		all
    create?name=john&pass=test		all
    changepass?old=sdgs43&new=sdsg345   write
    newapiread				write
    newapiwrite				write
    logout				read
    getapiread				read
    getapiwrite 			write
    view				write
    setlang				write

  */

  // no direct access
  defined('EMONCMS_EXEC') or die('Restricted access');
  
  function user_controller()
  {
    global $session, $route ,$allowusersregister, $default_controller_auth, $default_action_auth;

    $format = $route['format'];
    $action = $route['action'];

    $output['content'] = "";
    $output['message'] = "";

     
    //--------------------------------------------------------------------------
    // Login user (PUBLIC ACTION)
    // http://yoursite/emoncms/user/login?name=john&pass=test
    //--------------------------------------------------------------------------
    if ($action == 'login')
    {
      if ((isset($_POST['name']) && isset($_POST['pass'])) &&
        ($_POST['name'] && $_POST['pass']))
      {
        $username = preg_replace('/[^\w\s-.]/','',$_POST['name']);	// filter out all except for alphanumeric white space and dash
        $username = db_real_escape_string($username);

        $password = db_real_escape_string($_POST['pass']);
        $result = user_logon($username,$password);
	  
        if ($result == 0)
        {
      	  $output['message'] = _('Invalid username or password');
          $output['content'] = view("user/login_block.php", array());
        }
        else
        {	      	
      	  $output['message'] = _('Welcome, you are now logged in');
    	  if ($format == 'html'){
      	    header('Location: ../'.$default_controller_auth.'/'.$default_action_auth);
	  }
        }
      } 
      else
      {
        $output['content'] = view('user/login_block.php', array());
      }
    }

    //--------------------------------------------------------------------------
    // Create a user (PUBLIC ACTION) 
    // To disable addtional user creation remove or add higher priviledges to this
    // http://yoursite/emoncms/user/create?name=john&pass=test
    //--------------------------------------------------------------------------
    if ($action == 'create' && $allowusersregister)
    {    	
      $username = preg_replace('/[^\w\s-.]/','',post("name"));	// filter out all except for alphanumeric white space and dash
      $username = db_real_escape_string($username);

      $password = db_real_escape_string(post("pass"));

      $failed = FALSE;

      if (get_user_id($username) != 0)
      {
      	$output['message'] = _("Sorry username already exists");
        $failed = TRUE;
      }
      elseif (strlen($username) < 4 || strlen($username) > 30)
      {
      	$output['message'] = _("Please enter a username that is 4 to 30 characters long")."<br/>";
        $failed = TRUE;
      } 
      elseif (strlen($password) < 4 || strlen($password) > 30)
      {
        $output['message'] = _("Please enter a password that is 4 to 30 characters long")."<br/>";
        $failed = TRUE;
      }  
      else
      {
        $userid = create_user($username,$password);
        $result = user_logon($username,$password);

         if (isset($_POST['returnapikey']) && $_POST['returnapikey'] == true) $output['message'] = get_apikey_read($userid); else $output['message'] = _("Your new account has been created");
        if ($format == 'html')
        {
          header("Location: ../user/view");
	}
      }

      if ($failed && $format=='html') $output['content'] = view('user/login_block.php', array());
    }

    // http://yoursite/emoncms/user/changepass?old=sdgs43&new=sdsg345
    if ($action == 'changepass' && $_SESSION['write'])
    {
      $failed = FALSE;
      $oldpass =  db_real_escape_string(post('oldpass'));
      $newpass =  db_real_escape_string(post('newpass'));
      if (strlen($newpass) < 4 || strlen($newpass) > 30)
      {
        $failed = TRUE;
        $output['message'] = _("Please enter a password that is 4 to 30 characters long")."<br/>";
      }
      elseif (change_password($_SESSION['userid'],$oldpass,$newpass))
      {
        $output['message'] = _("Your password has been changed");
      } 
      else
      {
        $failed = TRUE;
        $output['message'] = _("Invalid password");
      }

      if ($format=='html') $action = 'view';
    }

    // http://yoursite/emoncms/user/changepass?old=sdgs43&new=sdsg345
    if ($action == 'changedetails' && $_SESSION['write'])
    {
      $username = preg_replace('/[^\w\s-.]/','',post("username"));
      $username =  db_real_escape_string($username);

      $email = preg_replace('/[^\w\s-.@]/','',post("email"));
      $email =  db_real_escape_string($email);

      $id = get_user_id($username);
      if ($id && $id!=$_SESSION['userid'])
      {
      	$output['message'] = _("Sorry username already exists");
      }
      elseif (strlen($username) < 4 || strlen($username) > 30)
      {
      	$output['message'] = _("Please enter a username that is 4 to 30 characters long")."<br/>";
      }
      elseif (!$email)
      {
      	$output['message'] = _("No email address present")."<br/>";
      }
      else
      {
        $output['message'] = _("Username and password updated");
        set_user_username($_SESSION['userid'], $username);
        set_user_email($_SESSION['userid'], $email);
      }

      if ($format=='html') $action = 'view';
    }

    //--------------------------------------------------------------------------
    // NEW API READ
    // http://yoursite/emoncms/user/newapiread
    //--------------------------------------------------------------------------
    if ($action == 'newapiread' && $session['write'])
    {
      $apikey_read = md5(uniqid(mt_rand(), true));
      set_apikey_read($session['userid'],$apikey_read);
      $output['message'] = _("New read apikey: ").$apikey_read;

      if ($format == 'html')
      {
      	header("Location: view");
      }
    }

    //--------------------------------------------------------------------------
    // NEW API WRITE
    // http://yoursite/emoncms/user/newapiwrite
    //--------------------------------------------------------------------------
    if ($action == 'newapiwrite' && $session['write'])
    {
      $apikey_write = md5(uniqid(mt_rand(), true));
      set_apikey_write($session['userid'],$apikey_write);
      $output['message'] = _("New write apikey: ").$apikey_write;

      if ($format == 'html')
      {
      	header("Location: view");
      }
    }

    //--------------------------------------------------------------------------
    // Logout
    // http://yoursite/emoncms/user/logout
    //--------------------------------------------------------------------------
    if ($action == 'logout' && $session['read'])
    { 
      user_logout();

      if ($format == 'html'){
        header("Location: ../");
      }
    }

    //--------------------------------------------------------------------------
    // GET API READ
    // http://yoursite/emoncms/user/getapiread
    //--------------------------------------------------------------------------
    if ($action == 'getapiread' && $session['read'])
    {
      $apikey_read = get_apikey_read($session['userid']);
      $output = $apikey_read;
    }

    //--------------------------------------------------------------------------
    // GET API WRITE
    // http://yoursite/emoncms/user/getapiwrite
    //--------------------------------------------------------------------------
    if ($action == 'getapiwrite' && $session['write'])
    {
      $apikey_write = get_apikey_write($session['userid']);
      $output = $apikey_write;
    }

    //--------------------------------------------------------------------------
    // GET USER
    // http://yoursite/emoncms/user/view
    //--------------------------------------------------------------------------
    if ($action == 'view' && $session['write'])
    {
      $user = get_user($session['userid']);

      if ($format == 'json') $output['content'] = json_encode($user);
      if ($format == 'html') $output['content'] = view("user/user_view.php", array('user' => $user));
    }

    //--------------------------------------------------------------------------
    // SET USERS DEFAULT LANGUAGE
    // http://yoursite/emoncms/user/setlang
    //--------------------------------------------------------------------------
    if ($action == 'setlang' && $session['write'])
    {
      // Store userlang in database

      $lang = preg_replace('/[^\w\s-]/','',get('lang'));
      set_user_lang($session['userid'],$lang);

      // Reload the page	  	
      if ($format == 'html')
      {
        header("Location: view");
      }
    }

    if ($action == 'admin' && $session['write'] && $session['admin'])
    {
      $userlist = get_user_list();    
      usort($userlist, 'user_sortby_uphits');
      $output['content'] = view("user/user_admin_view.php", array('userlist'=>$userlist));
    }

    return $output;
  }
?>
