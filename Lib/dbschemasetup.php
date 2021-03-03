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

//
// Fields are specified as an array with the following keys:
//
// - 'type'    string    'int(11)' or 'text' etc.       default: none
// - 'Default' value     default value                  default: none
// - 'Null'    boolean   is this allowed to be null?    default: true
// - 'Key'     boolean   is this a primary key?         default: false
//                       you can also write 'PRI' instead of true
// - 'Extra'   boolean   is this field auto_increment?  default: false
//


//
// Takes a field specification and the result of a DESCRIBE query
// and works out if the field needs to be altered
//
// MySQL regards NOT NULL, auto_increment, and default as part of
// the data type, and so not respecifying all of them when specifying
// the field will result in them being lost.
//
function db_schema_diff_datatype($spec, $current)
{
    $changed = false;

    $spec_default = isset($spec['default']) ? $spec['default'] : null;
    // Defaults for text fields are seemingly always returned
    // single-quoted from MySQL DESCRIBE statement
    if ($spec['type'] == 'text' && $spec_default !== null) {
        $spec_default = "'$spec_default'";
    }
    if ($spec_default != $current['Default']) {
        $changed = true;
    }
    // check if field Type changed
    if ($spec['type'] !== $current['Type']) {
        $changed = true;
    }
    // Null handling is a little involved
    if (isset($spec['Null']) && ($spec['Null'] === false || $spec['Null'] === "NO")) {
        $spec_null = "NO";
    }
    // Primary keys imply NOT NULL
    elseif (isset($spec['Key'])) {
        $spec_null = "NO";
    } else {
        $spec_null = "YES";
    }

    if ($spec_null != $current['Null']) {
        $changed = true;
    }

    // 'extra' is a flag for auto_increment
    $spec_extra = isset($spec['Extra']) ? $spec['Extra'] : false;
    if ($spec_extra && $current['Extra'] != "auto_increment") {
        $changed = true;
    }

    return $changed;
}

//
// Are we adding a primary key to this field?
//
function db_schema_diff_key($spec, $current)
{
    // 'key' is a flag for primary key
    $spec_primarykey = isset($spec['Key']) ? $spec['Key'] : false;
    if ($spec_primarykey && $current['Key'] != "PRI") {
        return true;
    } else {
        return false;
    }
}

//
// Produces an SQL field specification from a PHP array
//
// Results in things like:
//   `id` int(11) NOT NULL auto_increment PRIMARY KEY
//   `name` text
//
function db_schema_make_field($mysqli, $name, $spec, $add_key = true)
{
    // Start with name and basic type
    $str =  "`$name` ". $spec['type'];

    // Default value
    if (isset($spec['default'])) {
        $str .= " DEFAULT '{$mysqli->escape_string($spec['default'])}'";
    }

    $null = false;
    // We accept either NO or false
    if (isset($spec['Null']) && ($spec['Null'] === "NO" || $spec['Null'] === false)) {
        $null = true;
    }
    // Primary keys imply NOT NULL
    elseif (isset($spec['Key']) && $spec['Key']) {
        $null = true;
    }

    if ($null) {
        $str .= " NOT NULL";
    }

    // Auto-increment
    if (isset($spec['Extra']) && $spec['Extra']) {
        $str .= " auto_increment";
    }

    // Primary key
    if ($add_key && isset($spec['Key']) && $spec['Key']) {
        $str .= " PRIMARY KEY";
    }

    // Return null if no changes to current field; full spec otherwise
    return $str;
}

//
// Given a table and a field name, return the SQL to create a new index
//
function db_schema_make_index($table, $field)
{
    return "CREATE INDEX IX_{$table}_{$field} ON $table ($field)";
}

//
// Make a compound key if the schema has more than one key
//
function db_schema_make_compound_key($schema)
{
    $fields = array();
    foreach ($schema as $field => $spec) {
        if (isset($spec['Key']) && $spec['Key']) {
            array_push($fields, "`$field`");
        }
    }
    if (count($fields) < 2) {
        return "";
    }
    $fields = join(",", $fields);
    return ", PRIMARY KEY ($fields)";
}

//
// Create a new table using the given schema
//
function db_schema_make_table($mysqli, $table, $schema, &$operations)
{
    // If table doesn't exist, create it from scratch
    $fields = array();
    $indexes = array();
    $pk = db_schema_make_compound_key($schema);
    foreach ($schema as $field => $spec) {
        $fields[] = db_schema_make_field($mysqli, $field, $spec, $pk === "");
        if (isset($spec['Index'])) {
            $indexes[] = $field;
        }
    }

    $operations[] = "CREATE TABLE `$table` (" .join(', ', $fields). $pk . ") ENGINE=MYISAM";

    foreach ($indexes as $field) {
        $operations[] = db_schema_make_index($table, $field);
    }
}

//
// Adds the specified column using the schema given in $spec
//
function db_schema_add_column($mysqli, $table, $field, $spec, &$operations)
{
    $query = "ALTER TABLE `$table` ADD ";
    $query .= db_schema_make_field($mysqli, $field, $spec);
    $operations[] = $query;

    // Add an index
    if (isset($spec['Index'])) {
        $operations[] = db_schema_make_index($table, $field);
    }
}

//
// Updates the specified column to the schema given in $spec
//
function db_schema_update_column($mysqli, $table, $field, $spec, &$operations)
{
    $result = $mysqli->query("DESCRIBE `$table` `$field`");
    $current = $result->fetch_array();

    // Check if we 1. need to change type 2. need to add a primary key
    $diff_datatype = db_schema_diff_datatype($spec, $current, $field);
    $add_key = db_schema_diff_key($spec, $current);

    // If we do, generate a new field spec and update table
    if ($diff_datatype || $add_key) {
        $field_spec = db_schema_make_field($mysqli, $field, $spec, $add_key);
        $operations[] = "ALTER TABLE `$table` MODIFY $field_spec";
    }

    // Check index separately - there is no info about INDEX as a result of
    // the DESCRIBE query above
    if (isset($spec['Index'])) {
        $result = $mysqli->query("SHOW INDEX FROM $table");

        $found = false;
        while ($array = $result->fetch_array()) {
            if ($array['Column_name'] == $field) {
                $found = true;
            }
        }

        if ($found === false) {
            $operations[] = db_schema_make_index($table, $field);
        }
    }
}

//
// Create or update tables as necessary from the specified schema
// Returns the array of SQL statements necessary to perform the update
//
function db_schema_setup($mysqli, $schema, $apply)
{
    $operations = array();

    foreach ($schema as $table => $fields) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");

        // If table doesn't exist, create it
        if ($result == null || $result->num_rows == 0) {
            db_schema_make_table($mysqli, $table, $schema[$table], $operations);
        } else {
            // Check each field in the schema
            foreach ($fields as $field => $spec) {
                // Is this field in the database?
                $result = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE '$field'");

                if ($result->num_rows == 0) {
                    // If the field doesn't exist, add the field
                    db_schema_add_column($mysqli, $table, $field, $spec, $operations);
                } else {
                    // If the field does exist, try to update it
                    db_schema_update_column($mysqli, $table, $field, $spec, $operations);
                }
            }
        }
    }

    if ($apply) {
        $error = null;

        // Go over all the operations
        foreach ($operations as $query) {
            // Perform the query, checking for errors
            if (!$mysqli->query($query)) {
                $error = $mysqli->error;
                break;
            }
        }

        // Log the error
        if ($error) {
            $operations['error'] = $error;
        }
    }

    return $operations;
}

//
// Testing the above
//
function db_schema_test($mysqli)
{
    // Test 1
    $output = db_schema_make_field($mysqli, "field", array('type' => 'int',
                                                           'default' => '0',
                                                           'Null' => true,
                                                           'Extra' => true));
    $expected = "`field` int DEFAULT '0' auto_increment";
    if ($output != $expected) {
        echo "Test 1 failed<br>";
        echo "Expected: <code>$expected</code><br>";
        echo "Output: <code>$output</code><br>";
    }

    // Test 2
    $output = db_schema_make_field($mysqli, "tags", array('type' => 'text',
                                                          'default' => null,
                                                          'Null' => true));
    $expected = "`tags` text";
    if ($output != $expected) {
        echo "Test 2 failed<br>";
        echo "Expected: <code>$expected</code><br>";
        echo "Output: <code>$output</code><br>";
    }

    // Test 3
    // Try adding a NOT NULL constraint
    $spec = array('type' => 'int',
                  'default' => 0,
                  'Null' => false,
                  'Extra' => true);
    $current = array('Default' => '0',
                     'Null' => 'YES',
                     'Extra' => 'auto_increment');
    if (db_schema_diff_datatype($spec, $current) == false) {
        echo "Test 3 failed";
    }

    // Test 4
    // Try removing a NOT NULL constraint
    $spec = array('type' => 'int',
                  'default' => 0,
                  'Null' => true,
                  'Extra' => true);
    $current = array('Default' => '0',
                     'Null' => 'NO',
                     'Extra' => 'auto_increment');
    if (db_schema_diff_datatype($spec, $current) == false) {
        echo "Test 4 failed";
    }

    // Test 5
    // Comparing empty defaults to each other
    $spec = array('type' => 'int',
                  'default' => '');
    $current = array('Default' => '',
                     'Null' => 'YES',
                     'Extra' => '');
    if (db_schema_diff_datatype($spec, $current) == true) {
        echo "Test 5 failed";
    }

    // Test index creation
    $schema = array(
        'test' => array(
            'id' => array('type' => 'int(11)', 'Null'=>false, 'Key'=>true, 'Extra'=>true),
            'userid' => array('type' => 'int(11)', 'Index'=>true),
            'name' => array('type' => 'varchar(30)')
        )
    );
    $operations = db_schema_setup($mysqli, $schema, false);
    $found = 0;
    foreach ($operations as $query) {
        if (strpos($query, "CREATE INDEX") !== false) {
            $found++;
        }
    }

    if ($found !== 1) {
        echo "Test 6 failed";
    }
}
