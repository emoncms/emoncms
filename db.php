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

  $mysqli = 0; 
  
  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */

  function db_connect()
  {
    global $mysqli, $server, $username, $password, $database;
    
    // ERROR CODES
    // 1: success!
    // 3: database settings are wrong 
    // 4: launch setup.php
 
    // Lets try to connect
    $mysqli = new mysqli($server, $username, $password, $database);

    if ($mysqli->connect_error) 
      return 3;
    else
    {
      $result = db_query("SELECT count(table_schema) from information_schema.tables WHERE table_schema = '$database'");
      $row = db_fetch_array($result);
                   
      if ($row[0])
        return 1;
      else
        return 4;

    }
  }

  function db_query($query)
  {
    $ret = $GLOBALS['mysqli']->query($query);
    if ($ret == false) {echo $GLOBALS['mysqli']->error;}
    return $ret;
  }

  function db_fetch_array($result)
  {
    $ret = $result->fetch_array();
    if ($ret == false) {echo $GLOBALS['mysqli']->error;}
    return $ret;
  }

  function db_fetch_object($result)
  {
    $ret = $result->fetch_object();
    if ($ret == false) {echo $GLOBALS['mysqli']->error;}
    return $ret;
  }

  function db_num_rows($result)
  {
    return $result->num_rows;
  }

  function db_real_escape_string($string)
  {
    return $GLOBALS['mysqli']->real_escape_string($string);
  }

  function db_insert_id()
  {
    return $GLOBALS['mysqli']->insert_id;
  }

  function table_exists($tablename)
  {
    $result = db_query('SELECT DATABASE()');
    $row = db_fetch_array($result);
    $database = $row[0];

    $result = db_query("
        SELECT COUNT(*) AS count 
        FROM information_schema.tables 
        WHERE table_schema = '$database' 
        AND table_name = '$tablename'
    ");

    $row = db_fetch_array($result);
    return $row[0];
  }

  function field_exists($tablename,$field)
  {
    $field_exists = 0;
    $result = db_query("SHOW COLUMNS FROM $tablename");
    while( $row = db_fetch_array($result) ){
      if ($row['Field']==$field) $field_exists = 1;
    }
    return $field_exists;
  }

  function db_schema_setup($schema)
  {
    $out = array();
    while ($table = key($schema))
    { 
      if (table_exists($table))
      {
        $out[] = array('Table',$table,"ok");
        //-----------------------------------------------------
        // Check table fields from schema
        //-----------------------------------------------------
        while ($field = key($schema[$table]))
        { 
          $type = $schema[$table][$field]['type'];
          if (isset($schema[$table][$field]['Null'])) $null = $schema[$table][$field]['Null']; else $null = "YES";
          if (isset($schema[$table][$field]['Key'])) $key = $schema[$table][$field]['Key']; else $key = null;
          if (isset($schema[$table][$field]['default'])) $default = $schema[$table][$field]['default']; else $default = null;
          if (isset($schema[$table][$field]['Extra'])) $extra = $schema[$table][$field]['Extra']; else $extra = null;

          if (field_exists($table, $field))
          {
            $out[] = array('field',$field,"ok");
          }
          else
          {
            $query = "ALTER TABLE `$table` ADD `$field` $type";
            $out[] = array('field',$field,"added");
            db_query($query);
          } 

          $result = db_query("DESCRIBE $table `$field`");
          $array = db_fetch_array($result);
          $query = "";

          if ($array['Type']!=$type) { $out .= "Type: $type, "; $query .= ";"; }
          if ($array['Default']!=$default) { $out .= "Default: $default, "; $query .= " Default '$default'"; }
          if ($array['Null']!=$null && $null=="NO") { $out .= "Null: $null, "; $query .= " not null"; }
          if ($array['Extra']!=$extra && $extra=="auto_increment") { $out .= "Extra: $extra"; $query .= " auto_increment"; }
          if ($array['Key']!=$key && $key=="PRI") { $out .= "Key: $key, "; $query .= " primary key"; }

          if ($query) $query = "ALTER TABLE $table MODIFY `$field` $type".$query;
          if ($query) db_query($query);

          next($schema[$table]);
        }
      }
      else
      {
        //-----------------------------------------------------
        // Create table from schema
        //-----------------------------------------------------
        $query = "CREATE TABLE " . $table . " (";
        while ($field = key($schema[$table]))
        {
          $type = $schema[$table][$field]['type'];

          if (isset($schema[$table][$field]['Null'])) $null = $schema[$table][$field]['Null']; else $null = "YES";
          if (isset($schema[$table][$field]['Key'])) $key = $schema[$table][$field]['Key']; else $key = null;
          if (isset($schema[$table][$field]['default'])) $default = $schema[$table][$field]['default']; else $default = null;
          if (isset($schema[$table][$field]['Extra'])) $extra = $schema[$table][$field]['Extra']; else $extra = null;

          $query .= '`'.$field.'`';
          $query .= " $type";
          if ($default) $query .= " Default '$default'";
          if ($null=="NO") $query .= " not null";
          if ($extra) $query .= " auto_increment";
          if ($key) $query .= " primary key";

          next($schema[$table]);
          if (key($schema[$table]))
          {
            $query .= ", ";
          }
        }
        $query .= ")";
        $out[] = array('Table',$table,"created");

        if ($query) db_query($query);
      }
      next($schema);
    }
    return $out;
  }

  
?>
