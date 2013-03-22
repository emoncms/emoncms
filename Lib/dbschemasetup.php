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

function db_schema_setup($mysqli, $schema)
{
    $out = array();
    while ($table = key($schema))
    { 
        // if table exists:
        $result = $mysqli->query("SHOW TABLES LIKE '".$table."'");
        if (($result != null ) && ($result->num_rows==1))
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

                // if field exists:
                $result = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE '$field'");
                if ($result->num_rows==1)
                {
                    $out[] = array('field',$field,"ok");
                }
                else
                {
                    $query = "ALTER TABLE `$table` ADD `$field` $type";
                    $out[] = array('field',$field,"added");
                    $mysqli->query($query);
                } 

                $result = $mysqli->query("DESCRIBE $table `$field`");
                $array = $result->fetch_array();
                $query = "";
                
                $out_str = ""; // Not using this at the moment but good to break this out to the array
                if ($array['Type']!=$type) { $out_str .= "Type: $type, "; $query .= ";"; }
                if ($array['Default']!=$default) { $out_str .= "Default: $default, "; $query .= " Default '$default'"; }
                if ($array['Null']!=$null && $null=="NO") { $out_str .= "Null: $null, "; $query .= " not null"; }
                if ($array['Extra']!=$extra && $extra=="auto_increment") { $out_str .= "Extra: $extra"; $query .= " auto_increment"; }
                if ($array['Key']!=$key && $key=="PRI") { $out_str .= "Key: $key, "; $query .= " primary key"; }

                if ($query) $query = "ALTER TABLE $table MODIFY `$field` $type".$query;
                if ($query) $mysqli->query($query);

                next($schema[$table]);
            }
        } else {
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

            if ($query) $mysqli->query($query);
        }
        next($schema);
    }
    return $out;
}
