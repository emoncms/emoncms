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

function db_schema_setup($mysqli, $schema, $apply)
{
    $operations = array();
    while ($table = key($schema))
    { 
        // if table exists:
        $result = $mysqli->query("SHOW TABLES LIKE '".$table."'");
        if (($result != null ) && ($result->num_rows==1))
        {
            // $out[] = array('Table',$table,"ok");
            //-----------------------------------------------------
            // Check table fields from schema
            //-----------------------------------------------------
            while ($field = key($schema[$table]))
            { 
                $null = false;
                $type = $schema[$table][$field]['type'];
                if (isset($schema[$table][$field]['Null']) && $schema[$table][$field]['Null']==true) $null = true;
                if (isset($schema[$table][$field]['Key'])) $key = $schema[$table][$field]['Key']; else $key = null;
                if (isset($schema[$table][$field]['default'])) $default = $schema[$table][$field]['default']; else unset($default);
                if (isset($schema[$table][$field]['Extra'])) $extra = $schema[$table][$field]['Extra']; else $extra = null;
                if (isset($schema[$table][$field]['Index'])) $index = $schema[$table][$field]['Index']; else $index = null;

                // if field exists:
                $result = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE '$field'");
                if ($result->num_rows==0)
                {
                    $query = "ALTER TABLE `$table` ADD `$field` $type";
                    if (!$null) $query .= " NOT NULL";
                    if (isset($default)) $query .= " DEFAULT '$default'";
                    $operations[] = $query;
                    if ($apply) $mysqli->query($query);
                }
                else
                {
                  $result = $mysqli->query("DESCRIBE $table `$field`");
                  $array = $result->fetch_array();
                  $query = "";
                  
                  if (isset($default) && $array['Default']!=$default) $query .= " Default '$default'";
                  if ($array['Null']!=$null && $null=="NO") $query .= " not null";
                  if ($array['Extra']!=$extra && $extra=="auto_increment") $query .= " auto_increment";
                  if ($array['Key']!=$key && $key=="PRI") $query .= " primary key";
                  if ($array['Type']!=$type) $query .= ";";
				  
                  if ($query) $query = "ALTER TABLE $table MODIFY `$field` $type".$query;
                  if ($query) $operations[] = $query;
                  if ($query && $apply) $mysqli->query($query);
                  
                  // Check Index, there is no info about INDEX as a result of the DESCRIBE query above
                  if ($index){
                    $result = $mysqli->query("SHOW INDEX FROM $table");
                    $found = false;
                    while($array = $result->fetch_array()) if ($array['Column_name'] == $field) $found = true;
                    if ($found === false){     
                        $query = "CREATE INDEX IX_$table"."_$field ON $table ($field)";
                        $operations[] = $query;
                        if ($apply) $mysqli->query($query);
                    }
                  }
                } 

                next($schema[$table]);
            }
        } else {
            //-----------------------------------------------------
            // Create table from schema
            //-----------------------------------------------------
            $query = "CREATE TABLE " . $table . " (";
            $indexes = array();
            while ($field = key($schema[$table]))
            {
                $type = $schema[$table][$field]['type'];

                if (isset($schema[$table][$field]['Null'])) $null = $schema[$table][$field]['Null']; else $null = "YES";
                if (isset($schema[$table][$field]['Key'])) $key = $schema[$table][$field]['Key']; else $key = null;
                if (isset($schema[$table][$field]['default'])) $default = $schema[$table][$field]['default']; else $default = null;
                if (isset($schema[$table][$field]['Extra'])) $extra = $schema[$table][$field]['Extra']; else $extra = null;
                if (isset($schema[$table][$field]['Index'])) $index = $schema[$table][$field]['Index']; else $index = null;

                $query .= '`'.$field.'`';
                $query .= " $type";
                if ($default) $query .= " Default '$default'";
                if ($null=="NO") $query .= " not null";
                if ($extra) $query .= " auto_increment";
                if ($key) $query .= " primary key";
                if ($index) $indexes[] = $field;
                
                next($schema[$table]);
                if (key($schema[$table]))
                {
                  $query .= ", ";
                }
            }
            $query .= ")";
            $query .= " ENGINE=MYISAM";
            foreach($indexes as $i=>$field_for_index){ $query .= "; CREATE INDEX IX_$table"."_$field_for_index ON $table ($field_for_index)";}
            if ($query) $operations[] = $query;
            if ($query && $apply) {
                $mysqli->multi_query($query);
                while($mysqli->more_results()){ // We need to loop all the results to avoid errors in future single queries
                    $mysqli->next_result();
                }
            }
        }
        next($schema);
    }
    return $operations;
}
