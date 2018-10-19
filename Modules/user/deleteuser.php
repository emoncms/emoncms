<?php

defined('EMONCMS_EXEC') or die('Restricted access');

function delete_user($userid,$mode) {

    global $mysqli,$redis,$user,$feed_settings;

    $result1 = $mysqli->query("SELECT id,apikey_read,apikey_write FROM users WHERE id=$userid");
    if ($user_row = $result1->fetch_object()){
        $result = "User $userid ".time()." ".date("Y-m-d H:i:s",time())."\n";
        
        require_once "Modules/feed/feed_model.php";
        $feed = new Feed($mysqli,$redis,$feed_settings);

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
        
        $tables = array("app_config","autoconfig","dashboard","emailreport","graph","multigraph","myip","node","statico","rememberme");
        foreach ($tables as $tablename) {
            $result .= delete_entry_in_table($tablename,$userid,$mode);
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
    
    $result = "";
    
    if ($result1 = $mysqli->query("SELECT * FROM $tablename WHERE `userid`='$userid'")) {
        if ($result1->num_rows>0) {
            $result = "- $tablename entry\n";
            if ($mode=="permanentdelete") $mysqli->query("DELETE FROM $tablename WHERE `userid`='$userid'");
        }
    }
    return $result;
}

function check_entry_in_table($tablename,$userid) { 
    global $mysqli;
    $result = $mysqli->query("SELECT * FROM $tablename WHERE `userid`='$userid'");
    if ($result && $result->num_rows>0) return true;
    return false;
}
