<?php


class Update
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    function u0001($apply)
    {
      $operations = array();
      $sql = ("SELECT userid, id, name, nodeid, time, processlist FROM input;");
      $result = db_query($this->conn, $sql);
      while ($row = db_fetch_object($result))
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

            $sql = ("SELECT id FROM input WHERE userid = '" . $row->userid . "' AND nodeid = '$nodeid' AND name = '$name';");
            $inputexists = db_query($this->conn, $sql);
            if (!db_num_rows($this->conn, $inputexists)) {
                $operations[] = "UPDATE input SET name = '$name', nodeid = '$nodeid' WHERE id = '" . $row->id . "';";
            }
            if (!db_num_rows($this->conn, $inputexists) && $apply) {
                $sql = ("UPDATE input SET name = '$name', nodeid = '$nodeid' WHERE id = '" . $row->id . "';");
                db_query($this->conn, $sql);
            }
          }
        }

        preg_match('/^csv/', $row->name, $csv_matches);
        if ($csv_matches && $row->nodeid==0) 
        {
          $name = preg_replace('/^csv/', '',$row->name);
          $nodeid = 0;

          $sql = ("SELECT id FROM input WHERE userid = '" . $row->userid . "' AND nodeid = '$nodeid' AND name = '$name';");
          $inputexists = db_query($this->conn, $sql);
          if (!db_num_rows($this->conn, $inputexists) && !$apply) {
              $operations[] = "UPDATE input SET name = '$name', nodeid = '$nodeid' WHERE id = '" . $row->id . "';";
          }
          if (!db_num_rows($this->conn, $inputexists) && $apply) {
              $sql = ("UPDATE input SET name = '$name', nodeid = '$nodeid' WHERE id = '" . $row->id . "';");
              db_query($this->conn, $sql);
          }
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
      $sql = ("SELECT userid, id, processlist, time, record FROM input;");
      $result = db_query($this->conn, $sql);
      while ($row = db_fetch_object($result))
      {
        if ($row->processlist)
        {
          $pairs = explode(",",$row->processlist);
          foreach ($pairs as $pair)    			        
          {
            $inputprocess = explode(":", $pair);
            $processid = $inputprocess[0];
            $type = $process_list[$processid][1];

            if (isset($inputprocess[1]) && $type == 1) {  // type 1 = input
              $inputid = $inputprocess[1];
              $sql = ("SELECT record FROM input WHERE id = '$inputid';");
              $inputexists = db_query($this->conn, $sql);
              $inputrow = db_fetch_object($inputexists);
              if (!$inputrow->record) {
                  $operations[] = "UPDATE input SET record = '1' WHERE id = '$inputid';";
              }
              if (!$inputrow->record && $apply) {
                  $sql = ("UPDATE input SET record ='1' WHERE id = '$inputid';");
                  db_query($this->conn, $sql);
              }
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
      $sql = ("SELECT id, username FROM users;");
      $result = db_query($this->conn, $sql);
      while ($row = db_fetch_object($result))
      {
        $id = $row->id;
        $username = $row->username;
        // filter out all except for alphanumeric white space and dash
        $usernameout = preg_replace('/[^\w\s-]/','',$username);
        if ($usernameout!=$username) {
          $sql = ("SELECT id FROM users WHERE username = '$usernameout';");
          $userexists = db_query($this->conn, $sql);
          if (!db_num_rows($this->conn, $userexists)) {
            $operations[] = "Change username from $username to $usernameout";
            if ($apply) {
                $sql = ("UPDATE users SET username = '$usernameout' WHERE id = '$id';");
                db_query($this->conn, $sql);
            }
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
      if ($default_engine == Engine::MYSQL)
          $sql = ("SHOW columns FROM feeds LIKE 'timestore';");
     $result = db_query($this->conn, $sql);
     $row = db_fetch_array($result);
      
      if ($row) {
        $sql = ("SELECT id, timestore, engine FROM feeds;");
        $result = db_query($this->conn, $sql);
        while ($row = db_fetch_object($result))
        {
          $id = $row->id;
          $timestore = $row->timestore;
          
          if ($timestore==1 && $row->engine==0) $operations[] = "Set feed engine for feed $id to timestore";
          if ($timestore && $apply) {
              $sql = ("UPDATE feeds SET engine = '1' WHERE id = '$id';");
              db_query($this->conn, $sql);
          }
        }
      }
      return array(
        'title'=>"Field name change",
        'description'=>"Changed to more generic field name called engine rather than timestore specific",
        'operations'=>$operations
      );
    }
}
