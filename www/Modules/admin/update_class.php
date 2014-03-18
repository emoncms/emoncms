<?php


class Update
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    function u0001($apply)
    {
      $operations = array();
      $result = $this->mysqli->query("SELECT userid,id,name,nodeid,time,processList FROM input");
      while ($row = $result->fetch_object()) 
      {

        preg_match('/^node/', $row->name, $node_matches);
        if ($node_matches) 
        {
          $out = preg_replace('/^node/', '',$row->name);
          $out = explode('_',$out);

          if (is_numeric($out[0])) 
          {
            $nodeid = (int) $out[0]; 
            if (is_numeric($out[1])) $name = (int) $out[1]; else $name = $out[1];

            $inputexists = $this->mysqli->query("SELECT id FROM input WHERE `userid`='".$row->userid."' AND `nodeid`='$nodeid' AND `name`='$name'");
            if (!$inputexists->num_rows) $operations[] = "UPDATE input SET `name`='$name',`nodeid`='$nodeid' WHERE `id`='".$row->id."'";
            if (!$inputexists->num_rows && $apply) $this->mysqli->query("UPDATE input SET `name`='$name',`nodeid`='$nodeid' WHERE `id`='".$row->id."'");
          }
        }

        preg_match('/^csv/', $row->name, $csv_matches);
        if ($csv_matches && $row->nodeid==0) 
        {
          $name = preg_replace('/^csv/', '',$row->name);
          $nodeid = 0;

          $inputexists = $this->mysqli->query("SELECT id FROM input WHERE `userid`='".$row->userid."' AND `nodeid`='$nodeid' AND `name`='$name'");
          if (!$inputexists->num_rows && !$apply) $operations[] = "UPDATE input SET `name`='$name',`nodeid`='$nodeid' WHERE `id`='".$row->id."'";
          if (!$inputexists->num_rows && $apply) $this->mysqli->query("UPDATE input SET `name`='$name',`nodeid`='$nodeid' WHERE `id`='".$row->id."'");
        }
      }

      return array(
        'title'=>"Changed input naming convention",
        'description'=>"The input naming convention has been changed from the <b>node10_1</b> convention to <b>1</b> with the nodeid in the nodeid field. The following list, lists all the input names in your database that the script can update automatically:",
        'operations'=>$operations
      );
    }

    function u0002($apply)
    {
      require "Modules/input/process_model.php";
      $process = new Process(null,null,null);
      $process_list = $process->get_process_list();

      $operations = array();
      $result = $this->mysqli->query("SELECT userid,id,processList,time,record FROM input");
      while ($row = $result->fetch_object())
      {
        if ($row->processList)
        {
          $pairs = explode(",",$row->processList);
          foreach ($pairs as $pair)    			        
          {
            $inputprocess = explode(":", $pair);
            $processid = $inputprocess[0];
            $type = $process_list[$processid][1];

            if (isset($inputprocess[1]) && $type == 1) {  // type 1 = input
              $inputid = $inputprocess[1];
              $inputexists = $this->mysqli->query("SELECT record FROM input WHERE `id`='$inputid'");
              $inputrow = $inputexists->fetch_object();
              if (!$inputrow->record) $operations[] = "UPDATE input SET `record`='1' WHERE `id`='$inputid'";
              if (!$inputrow->record && $apply) $this->mysqli->query("UPDATE input SET `record`='1' WHERE `id`='$inputid'");
            }
          }
        }
      }

      return array(
        'title'=>"Inputs are only recorded if used",
        'description'=>"To improve performance inputs are only recorded if used as part of / x + - by input processes.",
        'operations'=>$operations
      );
    }

    function u0003($apply)
    {
      $operations = array();
      $data = array();
      $data2 = array();
      $result = $this->mysqli->query("SELECT id,username FROM users");
      while ($row = $result->fetch_object()) 
      {
        $id = $row->id;
        $username = $row->username;
        // filter out all except for alphanumeric white space and dash
        $usernameout = preg_replace('/[^\w\s-]/','',$username);
        if ($usernameout!=$username) {
          $userexists = $this->mysqli->query("SELECT id FROM users WHERE `username` = '$usernameout'");
          if (!$userexists->num_rows) {
            $operations[] = "Change username from $username to $usernameout";
            if ($apply) $this->mysqli->query("UPDATE users SET `username`='$usernameout' WHERE `id`='$id'");
          } else {
            $operations[] = "Cannot change username from $username to $usernameout as username $usernameout already exists, please fix manually.";
          }

        }
      }

      return array(
        'title'=>"Username format change",
        'description'=>"All . characters have been removed from usernames as the . character conflicts with the new routing implementation where emoncms thinks that the part after the . is the format the page should be in.",
        'operations'=>$operations
      );
    }
    
    function u0004($apply)
    {
      $operations = array();
      $result = $this->mysqli->query("Show columns from feeds like 'timestore'");
      $row = $result->fetch_array();
      
      if ($row) {
        $result = $this->mysqli->query("SELECT id,timestore,engine FROM feeds");
        while ($row = $result->fetch_object()) 
        {
          $id = $row->id;
          $timestore = $row->timestore;
          
          if ($timestore==1 && $row->engine==0) $operations[] = "Set feed engine for feed $id to timestore";
          if ($timestore && $apply) $this->mysqli->query("UPDATE feeds SET `engine`='1' WHERE `id`='$id'");
        }
      }
      return array(
        'title'=>"Field name change",
        'description'=>"Changed to more generic field name called engine rather than timestore specific",
        'operations'=>$operations
      );
    }
}
