<?php
  die;

  require("settings.php");
  require("core.php");
  $path = get_application_path();

  // Database connect
  require("db.php");
  db_connect();

  $result = db_query("SELECT id FROM feeds");
  $feeds = array();

  while ($row = db_fetch_array($result)) {

      $id = $row['id'];
      $fr = db_query("SELECT userid FROM feed_relation WHERE feedid = '$id'");
      $rw = db_fetch_array($fr);
      $userid = $rw['userid'];
      echo ".";
    db_query("UPDATE feeds SET userid = '$userid' WHERE id='$id'");
  }

?>
