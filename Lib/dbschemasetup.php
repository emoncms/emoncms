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

function db_schema_setup($conn, $schema, $apply)
{
	global $default_engine;

	switch ($default_engine) {
	case Engine::MYSQL:
		$retval = mysql_db_schema_setup($conn, $schema, $apply);
		break;
	case Engine::POSTGRESQL:
		$retval = pgsql_db_schema_setup($conn, $schema, $apply);
		break;
	case Engine::SQLITE:
		$retval = sqlite_db_schema_setup($conn, $schema, $apply);
		break;
	default:
		$retval = NULL;
		break;
	}

	return $retval;
}

function sqlite_db_schema_setup($conn, $schema, $apply)
{
	$operations = array();
	while ($table = key($schema)) {
	/* if table exists: */
		$sql = ("SELECT count(tbl_name) AS found FROM sqlite_master WHERE type='table' AND tbl_name = '" . $table . "';");
		$result = $conn->query($sql);
		if (($result != NULL ) && ($result !== FALSE))
			$found = $result->fetchColumn();
		else
			$found = FALSE;
		$result->closeCursor();

		if ($found) {
			/* $out[] = array('Table',$table,"ok");
			 *-----------------------------------------------------
			 * Check table fields from schema
			 *-----------------------------------------------------
			 */
			while ($field = key($schema[$table])) {
				$type = $schema[$table][$field]['type'];
				/* Convert some mysql specifics to standard SQL */
				if (strpos($type, "int(11)") !== FALSE) /* int(11) is always int with 4 bytes width */
					$type = "INTEGER";
				if (strpos($type, "tinyint(1)") !== FALSE) {
					$type = "BOOLEAN";
					if ($schema[$table][$field]['default']) {
						$default = 'TRUE';
					} else  {
						$default = 'FALSE';
					}
				} else {
					if (isset($schema[$table][$field]['default'])) {
						$default = $schema[$table][$field]['default'];
					} else  {
						$default = FALSE;
					}
				}
				if (strpos($type, "datetime") !== FALSE)
					$type = "TIMESTAMP WITH TIME ZONE";

				if (strpos($type, "float") !== FALSE)
					$type = "REAL";

				if (isset($schema[$table][$field]['Null']))
					$null = $schema[$table][$field]['Null'];
				else
					$null = "YES";

				if (isset($schema[$table][$field]['Key']))
					$key = $schema[$table][$field]['Key'];
				else
					$key = null;

				if (isset($schema[$table][$field]['Extra'])) {
					$extra = $schema[$table][$field]['Extra'];
					$pos = strpos($extra, 'auto_increment');
					if ($pos !== FALSE) {
						$type = 'INTEGER PRIMARY KEY';
						$extra = str_replace('auto_increment', '', $extra);
					}
				} else {
					$extra = NULL;
				}

				/* if field exists: */
				$sql = ("SELECT * FROM '$table';");
				$result = $conn->query($sql);
				$field_found = FALSE;
				for ($i = 0; $i < $result->ColumnCount(); $i++)
					if ($result->getColumnMeta($i)['name'] == '$field')
						$field_found = TRUE;
				if ($field_found == FALSE) {
					$query = "ALTER TABLE $table ADD $field $type;";
					if ($null)
						$query .= " NOT NULL;";
					if ($default !== FALSE)
						$query .= " DEFAULT '$default';";

					$operations[] = $query;
					if ($apply)
						$conn->query($query);
				} else {
					/* Alter datatypes if the columns exist HIGHLY experimental! */
					$sql = ("SELECT sql FROM sqlite_master WHERE type = 'table' AND tbl_name = '$table';");
					$result = $conn->query($sql);
					$result->closeCursor();
					$array = $result->fetch(PDO::FETCH_ASSOC);
					$query = "";

					$start = strpos($array['sql'], '(');
					$end = strpos($array['sql'], ')');
					$str = substr($array['sql'], $start, $end - $start);
					$data_tuple = explode(',', $str);
					foreach ($data_tuple as $tuple) {
						/* Skip anything before the first space. We ignore the fact that
						 * column names could contain spaces, because they shouldn't!
						 */
						$skip = strpos($tuple, ' ');
						if (substr_compare($tuple, $type, $skip, NULL, TRUE))
							$query .= ";";
						if (($default !== FALSE) && substr_compare($tuple, "DEFAULT", $skip, NULL, TRUE))
							$query .= " DEFAULT '$default'";
						if (substr_compare($tuple, "NOT NULL", $skip, NULL, TRUE))
							$query .= " NOT NULL";
					}
					//if ($array['Extra'] != $extra && $extra == "serial")
					//	$query .= " "; /* we can't just randomly start auto incrementing */
					//if ($array['Key'] != $key && $key == "PRI")
					//	$query .= " ADD PRIMARY KEY"; /* Changing the primary key cannot be done like this either, what if the key spans multiple columns? */

					if ($query)
						$query = "ALTER TABLE $table MODIFY $field $type" . $query;
					if ($query)
						$operations[] = $query;
					if ($query && $apply)
						$conn->query($query);
				}
				next($schema[$table]);
			}
		} else {
			/*-----------------------------------------------------
			 * Create table from schema
			 *-----------------------------------------------------
			 */
			$unique = "";
			$query = "CREATE TABLE $table (";
			while ($field = key($schema[$table])) {
				$primarykey = "";
				$type = $schema[$table][$field]['type'];
				/* Convert some mysql specifics to standard SQL */
				if (strpos($type, "int(11)") !== FALSE) /* int(11) is always int with 4 bytes width */
					$type = "INTEGER";
				if (strpos($type, "tinyint(1)") !== FALSE) {
					$type = "BOOLEAN";
					if ($schema[$table][$field]['default']) {
						$default = 'TRUE';
					} else  {
						$default = 'FALSE';
					}
				} else {
					if (isset($schema[$table][$field]['default']) !== FALSE) {
						$default = $schema[$table][$field]['default'];
					} else  {
						$default = FALSE;
					}
				}
				if (strpos($type, "datetime") !== FALSE)
					$type = "TIMESTAMP WITH TIME ZONE";
				if (strpos($type, "float") !== FALSE)
					$type = "REAL";
				if (isset($schema[$table][$field]['Null']))
					$null = $schema[$table][$field]['Null'];
				else
					$null = "YES";
				if (isset($schema[$table][$field]['Key']))
					$key = $schema[$table][$field]['Key'];
				else
					$key = null;
				if (isset($schema[$table][$field]['Extra'])) {
					$extra = $schema[$table][$field]['Extra'];
					if (strpos($extra, 'auto_increment') !== FALSE) {
						$primarykey = "PRIMARY KEY";
						$extra = str_replace("auto_increment", "", $extra);
					}
				} else {
					$extra = null;
				}

				$query .= " $field";
				$query .= " $type";
				$query .= " $primarykey";
				if ($default !== FALSE)
					$query .= " DEFAULT '$default'";
				if ($null == "NO")
					$query .= " NOT NULL";
				if ($extra)
					$query .= " $extra";
				if ($key) {
					if ($unique)
						$unique .= ", ";
					$unique .= "$field";
				}

				next($schema[$table]);
				if (key($schema[$table]))
					$query .= ", ";
			}
			if ($unique)
				$query .= ", UNIQUE ($unique)";
			$query .= ");";
			if ($query)
				$operations[] = $query;
			if ($query && $apply)
				$conn->query($query);
		}
		next($schema);
	}
	return $operations;
}

function pgsql_db_schema_setup($conn, $schema, $apply)
{
	$operations = array();
	while ($table = key($schema)) {
	/* if table exists: */
		$sql = ("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname='public' AND tablename = '" . $table . "';");
		$result = pg_query($conn, $sql);
		if (($result != null ) && (pg_num_rows($result) == 1)) {
			/* $out[] = array('Table',$table,"ok");
			 *-----------------------------------------------------
			 * Check table fields from schema
			 *-----------------------------------------------------
			 */
			while ($field = key($schema[$table])) {
				$type = $schema[$table][$field]['type'];
				/* Convert some mysql specifics to standard SQL */
				if (strpos($type, "int(11)") !== FALSE) /* int(11) is always int with 4 bytes width */
					$type = "INTEGER";
				if (strpos($type, "tinyint(1)") !== FALSE) {
					$type = "BOOLEAN";
					if ($schema[$table][$field]['default']) {
						$default = 'TRUE';
					} else  {
						$default = 'FALSE';
					}
				} else {
					if (isset($schema[$table][$field]['default'])) {
						$default = $schema[$table][$field]['default'];
					} else  {
						$default = FALSE;
					}
				}
				if (strpos($type, "datetime") !== FALSE)
					$type = "TIMESTAMP WITH TIME ZONE";

				if (strpos($type, "float") !== FALSE)
					$type = "REAL";

				if (isset($schema[$table][$field]['Null']))
					$null = $schema[$table][$field]['Null'];
				else
					$null = "YES";

				if (isset($schema[$table][$field]['Key']))
					$key = $schema[$table][$field]['Key'];
				else
					$key = null;

				if (isset($schema[$table][$field]['Extra'])) {
					$extra = $schema[$table][$field]['Extra'];
					$pos = strpos($extra, 'auto_increment');
					if ($pos !== FALSE) {
						$type = 'SERIAL';
						$extra = str_replace('auto_increment', '', $extra);
					}
				} else {
					$extra = null;
				}

			/* if field exists: */
			$sql = ("SELECT column_name FROM information_schema.columns WHERE table_name = '$table' AND column_name = '$field';");
			$result = pg_query($conn, $sql);
				if (pg_num_rows($result) == 0) {
					$query = "ALTER TABLE $table ADD $field $type;";
					if ($null)
						$query .= " NOT NULL;";
					if ($default !== FALSE)
						$query .= " DEFAULT '$default';";

					$operations[] = $query;
					if ($apply)
						pg_query($conn, $query);
				} else {
					/* Alter datatypes if the columns exist */
					$sql = ("SELECT data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = '$table';");
					$result = pg_query($conn, $sql);
					$array = pg_fetch_array($result);
					$query = "";

					if ($array['data_type'] != $type)
						$query .= ";";
					if (($default !== FALSE) && $array['column_default'] != $default)
						$query .= " DEFAULT '$default'";
					if ($array['is_nullable'] != $null && $null == "NO")
						$query .= " NOT NULL"; /* Can fail if not all fields are non-null */
					//if ($array['Extra'] != $extra && $extra == "serial")
					//	$query .= " "; /* we can't just randomly start auto incrementing */
					//if ($array['Key'] != $key && $key == "PRI")
					//	$query .= " ADD PRIMARY KEY"; /* Changing the primary key cannot be done like this either, what if the key spans multiple columns? */

					if ($query)
						$query = "ALTER TABLE $table MODIFY $field $type" . $query;
					if ($query)
						$operations[] = $query;
					if ($query && $apply)
						pg_query($conn, $query);
				}
				next($schema[$table]);
			}
		} else {
			/*-----------------------------------------------------
			 * Create table from schema
			 *-----------------------------------------------------
			 */
			$query = "CREATE TABLE $table (";
			while ($field = key($schema[$table])) {
				$type = $schema[$table][$field]['type'];
				/* Convert some mysql specifics to standard SQL */
				if (strpos($type, "int(11)") !== FALSE) /* int(11) is always int with 4 bytes width */
					$type = "INTEGER";
				if (strpos($type, "tinyint(1)") !== FALSE) {
					$type = "BOOLEAN";
					if ($schema[$table][$field]['default']) {
						$default = 'TRUE';
					} else  {
						$default = 'FALSE';
					}
				} else {
					if (isset($schema[$table][$field]['default']) !== FALSE) {
						$default = $schema[$table][$field]['default'];
					} else  {
						$default = FALSE;
					}
				}
				if (strpos($type, "datetime") !== FALSE)
					$type = "TIMESTAMP WITH TIME ZONE";
				if (strpos($type, "float") !== FALSE)
					$type = "REAL";
				if (isset($schema[$table][$field]['Null']))
					$null = $schema[$table][$field]['Null'];
				else
					$null = "YES";
				if (isset($schema[$table][$field]['Key']))
					$key = $schema[$table][$field]['Key'];
				else
					$key = null;
				if (isset($schema[$table][$field]['Extra'])) {
					$extra = $schema[$table][$field]['Extra'];
					$pos = strpos($extra, 'auto_increment');
					if ($pos !== FALSE) {
						$type = 'SERIAL';
						$extra = str_replace('auto_increment', '', $extra);
					}
				} else {
					$extra = null;
				}

				$query .= " $field";
				$query .= " $type";
				if ($default !== FALSE)
					$query .= " DEFAULT '$default'";
				if ($null == "NO")
					$query .= " NOT NULL";
				if ($extra)
					$query .= " $extra";
				if ($key)
					$query .= " PRIMARY KEY";

				next($schema[$table]);
				if (key($schema[$table]))
					$query .= ", ";
			}
			$query .= ")";
			if ($query)
				$operations[] = $query;
			if ($query && $apply)
				pg_query($conn, $query);
		}
		next($schema);
	}
	return $operations;
}

function mysql_db_schema_setup($mysqli, $schema, $apply)
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
                $type = $schema[$table][$field]['type'];
                if (isset($schema[$table][$field]['Null'])) $null = $schema[$table][$field]['Null']; else $null = "YES";
                if (isset($schema[$table][$field]['Key'])) $key = $schema[$table][$field]['Key']; else $key = null;
                if (isset($schema[$table][$field]['default'])) $default = $schema[$table][$field]['default']; else unset($default);
                if (isset($schema[$table][$field]['Extra'])) $extra = $schema[$table][$field]['Extra']; else $extra = null;

                // if field exists:
                $result = $mysqli->query("SHOW COLUMNS FROM $table LIKE '$field'");
                if ($result->num_rows==0)
                {
                    $query = "ALTER TABLE $table ADD $field $type";
                    if ($null) $query .= " NOT NULL";
                    if (isset($default)) $query .= " DEFAULT '$default'";
                    $operations[] = $query;
                    if ($apply) $mysqli->query($query);
                }
                else
                {
                  $result = $mysqli->query("DESCRIBE $table $field");
                  $array = $result->fetch_array();
                  $query = "";
                  
                  if ($array['Type']!=$type) $query .= ";";
                  if (isset($default) && $array['Default']!=$default) $query .= " Default '$default'";
                  if ($array['Null']!=$null && $null=="NO") $query .= " not null";
                  if ($array['Extra']!=$extra && $extra=="auto_increment") $query .= " auto_increment";
                  if ($array['Key']!=$key && $key=="PRI") $query .= " primary key";

                  if ($query) $query = "ALTER TABLE $table MODIFY $field $type".$query;
                  if ($query) $operations[] = $query;
                  if ($query && $apply) $mysqli->query($query);
                } 

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

                $query .= ''.$field.'';
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
            $query .= " ENGINE=MYISAM";
            if ($query) $operations[] = $query;
            if ($query && $apply) $mysqli->query($query);
        }
        next($schema);
    }
    return $operations;
}
