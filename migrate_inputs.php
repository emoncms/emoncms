<?php 

  die; 
  $execute = FALSE;

?>

<link href="Lib/bootstrap/css/bootstrap.css" rel="stylesheet">

<div style="padding:20px; max-width:1000px;">

<p>It is recommended to do an input table backup before running this script with execute mode on</p>

<h2>1) Input name change</h2>

<p>The new version has a different input name convention. Instead of the node10_1 convention the name is now simply 1 with the nodeid in the input table nodeid field. The following list show all the inputs in your installation that need converting:</p>

<table class='table'>
<tr><th>User</th><th>Current nodeid</th><th>Current name</th><th>New nodeid</th><th>New name</th><th>Exists</th><th>Process List</th><th>Last updated</th></tr>
<?php

  class ProcessArg {
    const VALUE = 0;
    const INPUTID = 1;
    const FEEDID = 2;
  }

  class DataType {
    const UNDEFINED = 0;
    const REALTIME = 1;
    const DAILY = 2;
    const HISTOGRAM = 3;
  }

  define('EMONCMS_EXEC', 1);

  error_reporting(E_ALL);      
  ini_set('display_errors', 'on');  

  require "process_settings.php";
  $mysqli = new mysqli($server,$username,$password,$database);


  require "Modules/feed/feed_model.php"; // 540
  $feed = new Feed($mysqli);

  require "Modules/input/input_model.php"; // 295
  $input = new Input($mysqli,$feed);

  require "Modules/input/process_model.php"; // 886
  $process = new Process($mysqli,$input,$feed);

  $result = $mysqli->query("SELECT userid,id,name,nodeid,time,processList FROM input");
  while ($row = $result->fetch_object()) 
  {

    preg_match('/^node/', $row->name, $node_matches);
    if ($node_matches) 
    {
      $out = preg_replace('/^node/', '',$row->name);
      $out = explode('_',$out);

      $valid = false;
      if (is_numeric($out[0]) && is_numeric($out[1])) 
      {
        $valid = true;
        $nodeid = (int) $out[0]; 
        $name = (int) $out[1];
        $id = $row->id;

        $userid = $row->userid;
        $inputexists = $mysqli->query("SELECT id FROM input WHERE `userid`='$userid' AND `nodeid`='$nodeid' AND `name`='$name'");
        $numinputexists = $inputexists->num_rows;
        if ($numinputexists) $valid = false;

        if ($execute && !$numinputexists) $mysqli->query("UPDATE input SET `name`='$name',`nodeid`='$nodeid' WHERE `id`='$id'");
      }

      if (is_numeric($out[0])) 
      {
        $valid = true;
        $nodeid = (int) $out[0];
        $name = $out[1];
        $id = $row->id;

        $userid = $row->userid;
        $inputexists = $mysqli->query("SELECT id FROM input WHERE `userid`='$userid' AND `nodeid`='$nodeid' AND `name`='$name'");
        $numinputexists = $inputexists->num_rows;
        if ($numinputexists) $valid = false;
        
        if ($execute && !$numinputexists) $mysqli->query("UPDATE input SET `name`='$name',`nodeid`='$nodeid' WHERE `id`='$id'");
      }
      
      if ($valid == true) echo "<tr><td>User: ".$row->userid."</td><td>".$row->nodeid."</td><td>".$row->name."</td><td>".$nodeid."</td><td>".$name."</td><td>".$numinputexists."</td><td>".$row->processList."</td><td>".$row->time."</td></tr>";
    }

    preg_match('/^csv/', $row->name, $csv_matches);
    if ($csv_matches && $row->nodeid==0) 
    {
      $name = preg_replace('/^csv/', '',$row->name);
      $nodeid = 0;

      $id = $row->id;
      $userid = $row->userid;
      $inputexists = $mysqli->query("SELECT id FROM input WHERE `userid`='$userid' AND `nodeid`='$nodeid' AND `name`='$name'");
      $numinputexists = $inputexists->num_rows;
      if (!$numinputexists) echo "<tr><td>User: ".$row->userid."</td><td>".$row->nodeid."</td><td>".$row->name."</td><td>".$nodeid."</td><td>".$name."</td><td>".$numinputexists."</td><td>".$row->processList."</td><td>".$row->time."</td></tr>";

      if ($execute && !$numinputexists) $mysqli->query("UPDATE input SET `name`='$name',`nodeid`='$nodeid' WHERE `id`='$id'");
    }
  }
?>
</table>

<h2>2) Record input</h2>
<p>The new input processing implementation only records an input if its being used for - + / x by input type processes to optimise input processing efficiency. Executing the following turns recording on for the inputs needed</p>

<?php
      
  $process_list = $process->get_process_list();

  $result = $mysqli->query("SELECT userid,id,processList,time,record FROM input");

  $out = "";
  while ($row = $result->fetch_object())
  {
    $inputid = $row->id;
    $record = $row->record;
    $processList = $row->processList;
    if ($processList)
    {

      $index = 0;
      $pairs = explode(",",$processList);
      foreach ($pairs as $pair)    			        
      {
        $index++;
        $inputprocess = explode(":", $pair);
        $processid = $inputprocess[0];

        // An unlikely occurance but for some reason it does happen
        if (!isset($inputprocess[1])) 
        {
          $out.="<tr><td>User: ".$row->userid."</td><td>Input: ".$inputid."</td><td>$processList</td><td>Invalid entry, missing : and argument</td></tr>";
        }
        else
        {
          $arg = $inputprocess[1];
          $type = $process_list[$processid][1];
          if ($type==ProcessArg::FEEDID)
          {
            $feedid = $arg;
            $feedresult = $mysqli->query("SELECT id,name FROM feeds WHERE `id`='$feedid'");

            $feedrow = $feedresult->fetch_array();
            if (!$feedrow) {
              $out.="<tr><td>User: ".$row->userid."</td><td>Input: ".$inputid."</td><td>Process index: ".$index."</td><td>Feed id:$feedid does not exist, delete process entry</td></tr>";
              if ($execute) {
                $input->delete_process($inputid, $index);
              }
            }
          }

          if ($type==ProcessArg::INPUTID)
          {
            if ($arg)
            {
              $inputexists = $mysqli->query("SELECT record FROM input WHERE `id`='$arg'");
              $inprow = $inputexists->fetch_array();

              if ($inprow['record']==false) $out.="<tr><td>User: ".$row->userid."</td><td>Input: ".$inputid."</td><td>Process index: ".$index."</td><td>Set input $arg to record</td></tr>";

              if ($execute && $inprow['record']==false) $mysqli->query("UPDATE input SET `record`='1' WHERE `id`='$arg'");
            }
          }
        }

      }

    }
  }
  echo "<table class='table'>".$out."</table>";

?>


<p>To execute all of the above changes open this script and change execute to true</p>

</div>
