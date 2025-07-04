<?php

defined('EMONCMS_EXEC') or die('Restricted access');

function delete_user($userid,$mode) {

    $dryrun = true;
    if ($mode=="permanentdelete") {
        $dryrun = false;
    }

    global $mysqli,$redis,$user,$settings;

    $result1 = $mysqli->query("SELECT id,apikey_read,apikey_write FROM users WHERE id=$userid");
    if ($user_row = $result1->fetch_object()){
        $result = "User $userid ".time()." ".date("Y-m-d H:i:s",time())."\n";
        
        require_once "Modules/feed/feed_model.php";
        $feed = new Feed($mysqli,$redis,$settings["feed"]);

        require_once "Modules/input/input_model.php";
        $input = new Input($mysqli,$redis,$feed);
    
        $result .= "Feeds:\n";
        $result2 = $mysqli->query("SELECT id,name FROM feeds WHERE userid=$userid");
        while ($row2 = $result2->fetch_object())
        {
            $feedid = $row2->id;
            $result .= "  $feedid:$row2->name\n";
            if ($mode=="permanentdelete") $feed->delete($feedid);
        }
    
        $result .= "Inputs:\n";
        $result2 = $mysqli->query("SELECT id,name FROM input WHERE userid=$userid");
        while ($row2 = $result2->fetch_object())
        {
            $inputid = $row2->id;
            $result .= "  $inputid:$row2->name\n";
            if ($mode=="permanentdelete") $input->delete($userid, $inputid);
        }
        
        $apikey = $user_row->apikey_read;
        if ($redis!= false && $redis->exists("readapikey:$apikey")) {
            $result .= "- readapikey from redis\n";
            if ($mode=="permanentdelete") $redis->del("readapikey:$apikey");
        }
        
        $apikey = $user_row->apikey_write;
        if ($redis!= false && $redis->exists("writeapikey:$apikey")) {
            $result .= "- writeapikey from redis\n";
            if ($mode=="permanentdelete") $redis->del("writeapikey:$apikey");
        }
        
        $schema = load_db_schema();
        
        foreach ($schema as $tablename=>$columns) {
            if (isset($columns['userid'])) {
                $result .= delete_entry_in_table($tablename,$userid,$mode);
            }
        }
        
        // It would be better to implement some kind of standard interface for this
        if (file_exists("Modules/account/account_model.php")) {
            require_once("Modules/account/account_model.php");
            $account_class = new Accounts($mysqli, $redis, $user);
            $account_result = $account_class->delete_user($userid, $dryrun);
            if ($account_result['success']) {
                $result .= "- ".$account_result['message']."\n";
            }
        }
        
        $result .= "- user entry\n";
        if ($mode=="permanentdelete") $mysqli->query("DELETE FROM users WHERE `id` = '$userid'");
        
        // $user->logout();
    } else {
        $result = "user does not exist";
    }
    
    // if ($mode=="permanentdelete") {
    //    $fh = fopen("/var/log/emoncms/userdelete.log","a");
    //    fwrite($fh,$result);
    //    fclose($fh);
    // } 
    
    return $result;
}

function delete_entry_in_table($tablename,$userid,$mode) { 
    global $mysqli;
    
    if ($result = $mysqli->query("SHOW TABLES LIKE '$tablename'")) {
        if ($result->num_rows) {
            if ($result = $mysqli->query("SELECT * FROM $tablename WHERE `userid`='$userid'")) {
                if ($result->num_rows>0) {
                    if ($mode=="permanentdelete") $mysqli->query("DELETE FROM $tablename WHERE `userid`='$userid'");      
                    return "- $tablename entry\n";
                }
            }
        }
    }
    

    return "";
}

function check_entry_in_table($tablename,$userid) { 
    global $mysqli;
    $result = $mysqli->query("SELECT * FROM $tablename WHERE `userid`='$userid'");
    if ($result && $result->num_rows>0) return true;
    return false;
}
