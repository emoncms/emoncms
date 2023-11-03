<?php

class Accounts
{
    private $mysqli;
    private $user;
    private $table = "accounts";

    public function __construct($mysqli, $user)
    {
        $this->mysqli = $mysqli;
        $this->user = $user;
    }

    public function list($adminuser)
    {
        $adminuser = (int) $adminuser;

        $accounts = array();
        $result = $this->mysqli->query("SELECT linkeduser FROM ".$this->table." WHERE adminuser = '$adminuser'");
        while ($row = $result->fetch_object()) {

            // Get user details
            $u = $this->user->get($row->linkeduser);

            // Count feeds
            $result2 = $this->mysqli->query("SELECT COUNT(*) AS feeds FROM feeds WHERE userid = '$row->linkeduser'");
            $f = $result2->fetch_object();

            $account = new stdClass();
            $account->id = $row->linkeduser;
            $account->username = $u->username;
            $account->email = $u->email;
            $account->feeds = $f->feeds;
            $account->access = $this->user->get_access($row->linkeduser);

            $accounts[] = $account;
        }
        return $accounts;
    }

    public function add($adminuser,$username,$password,$email,$timezone) 
    {
        // Check if adminuser is a linkeduser 
        $result = $this->mysqli->query("SELECT * FROM ".$this->table." WHERE linkeduser='$adminuser'");
        if ($result->fetch_object()) {
            return array("success"=>false,"message"=>"Cannot add user as admin user is a linked user");
        }

        $userid = $this->user->get_id($username);
        // If user does not exist then register
        if (!$userid) {
            // Get adminuser details, if email is the same turn off email verification
            $adminuser_details = $this->user->get($adminuser);
            if ($adminuser_details->email == $email) {
                global $settings;
                $settings["interface"]["email_verification"] = false;
            }
            // User does not exist
            // Register user
            $result = $this->user->register($username, $password, $email, $timezone);
            if (!$result['success']) return $result;
            // if success then get userid
            $linkeduser = $result['userid'];

            // Disable login access by default
            $this->user->set_access($linkeduser,0);
            
        } else {
            // else check password and fetch userid
            $result = $this->user->get_apikeys_from_login($username,$password);
            if (!$result['success']) {
                return array("success"=>false,"message"=>"invalid username or password");
            }
            $linkeduser = $result['userid'];
        }

        // Check linked user is not admin user
        if ($adminuser==$linkeduser) {
            return array("success"=>false,"message"=>"cannot link to self");
        }
        
        // Check if user is already linked
        $result = $this->mysqli->query("SELECT * FROM ".$this->table." WHERE linkeduser='$linkeduser'");
        if ($row = $result->fetch_object()) {
            return array("success"=>false,"message"=>"user already linked");
        }
        
        // Add user to linked table
        $this->mysqli->query("INSERT INTO ".$this->table." (adminuser,linkeduser) VALUES ('$adminuser','$linkeduser')");
        
        return array("success"=>true,"message"=>"user linked");
    }
    
    public function unlink($adminuser,$linkeduser)
    {
        $adminuser = (int) $adminuser;
        $linkeduser = (int) $linkeduser;

        // Check if linkeduser belongs to adminuser
        if (!$this->is_linked($adminuser,$linkeduser)) {
            return array("success"=>false,"message"=>"invalid linked userid");
        }

        // Unlink user
        $this->mysqli->query("DELETE FROM ".$this->table." WHERE adminuser='$adminuser' AND linkeduser='$linkeduser'");
        return array("success"=>true,"message"=>"user unlinked");
    }

    public function switch($adminuser, $linkeduser) {
        $adminuser = (int) $adminuser;
        $linkeduser = (int) $linkeduser;

        // switch to adminuser
        if (!$linkeduser && isset($_SESSION["adminuser"])) {
            $userid = $_SESSION["adminuser"];
            unset($_SESSION["adminuser"]);
            $this->load_user_session($userid);

            header("Location: ../account/list");
            // stop any other code from running once http header sent
            exit();
        }

        // check that linkeduser is linked to adminuser
        if (!$this->is_linked($adminuser,$linkeduser)) {
            return array("success"=>false,"message"=>"invalid linked userid");
        }

        // switch user
        $_SESSION["adminuser"] = $adminuser;
        $this->load_user_session($linkeduser);
        
        header("Location: ../".$_SESSION["startingpage"]);
        // stop any other code from running once http header sent
        exit();
    }

    public function load_user_session($userid) {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT `username`,`admin`,`timezone`,`startingpage`,`gravatar` FROM users WHERE `id`='$userid'");
        if ($row = $result->fetch_object()) {
            $_SESSION["userid"] = $userid;
            $_SESSION['admin'] = $row->admin;
            $_SESSION["username"] = $row->username;
            $_SESSION["timezone"] = $row->timezone;
            $_SESSION["startingpage"] = $row->startingpage;
            $_SESSION["gravatar"] = $row->gravatar;
        }
    }

    // Set access
    public function set_access($adminuser,$linkeduser,$access) {
        $adminuser = (int) $adminuser;
        $linkeduser = (int) $linkeduser;
        $access = (int) $access;

        // Check if linkeduser belongs to adminuser
        if (!$this->is_linked($adminuser,$linkeduser)) {
            return array("success"=>false,"message"=>"invalid linked userid");
        }

        // Set access
        $this->user->set_access($linkeduser,$access);

        return array("success"=>true,"message"=>"access updated");
    }

    // Check if linkeduser belongs to adminuser
    public function is_linked($adminuser,$linkeduser) {
        $adminuser = (int) $adminuser;
        $linkeduser = (int) $linkeduser;
        $result = $this->mysqli->query("SELECT * FROM ".$this->table." WHERE adminuser='$adminuser' AND linkeduser='$linkeduser'");
        if ($result->fetch_object()) {
            return true;
        }
        return false;
    }
    
    // Delete user method
    public function delete_user($userid, $dryrun = true) {
        $userid = (int) $userid;

        // Check if user is a linked user
        $result = $this->mysqli->query("SELECT * FROM ".$this->table." WHERE linkeduser='$userid'");
        if ($result->fetch_object()) {
            if (!$dryrun) {
                // Unlink user
                $this->mysqli->query("DELETE FROM ".$this->table." WHERE linkeduser='$userid'");
            }
            return array("success"=>true,"message"=>"removed user from accounts table");
        }

        // Check if user is an admin user
        $result = $this->mysqli->query("SELECT * FROM ".$this->table." WHERE adminuser='$userid'");
        // If user is an admin user then delete all linked users
        // get number of linked users
        $num_linked_users = $result->num_rows;
        if ($num_linked_users) {
            if (!$dryrun) {
                // Delete all linked users
                $this->mysqli->query("DELETE FROM ".$this->table." WHERE adminuser='$userid'");
            }
            return array("success"=>true,"message"=>"removed $num_linked_users linked users from accounts table");
        }
        
        // no linked or admin users
        return array("success"=>false,"message"=>"user not found in accounts table");
    }
}
