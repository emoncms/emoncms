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

function db_connect($server, $port, $username, $password, $database)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$conn_str = "host='$server' port='$port' user='$username' dbname='$database'";
		$conn = pg_connect($conn_str);
		break;
	case (Engine::MYSQL):
		$conn = @new mysqli($server, $username, $password, $database);
		break;
	default:
		$conn = NULL;
		break;
	}

	return $conn;
}

function db_connect_error($conn)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		if ($conn)
			$retval = pg_last_error($conn);
		else
			$retval = "unable to connect to db";
		break;
	case (Engine::MYSQL):
		$retval = $conn->connect_error();
		break;
	default:
		$retval = "no db engine available";
		break;
	}

	return $retval;
}

function db_check($conn, $database)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$sql = ("SELECT count(schemaname) FROM pg_catalog.pg_tables WHERE schemaname = 'public';");
		break;
	case (Engine::MYSQL):
		$sql = ("SELECT count(table_schema) FROM information_schema.tables WHERE table_schema = '$database';");
		break;
	default:
		break;
	}
	$result = db_query($conn, $sql);
	$row = db_fetch_array($result);

	return ($row['0'] > 0) ? TRUE : FALSE;
}

function db_query($conn, $query)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$retval = pg_query($conn, $query);
		break;
	case (Engine::MYSQL):
		$retval = $conn->query($query);
		break;
	default:
		$retval = NULL;
		break;
	}

	return $retval;;
}

function db_num_rows($conn, $result)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$retval = pg_num_rows($result);
		break;
	case (Engine::MYSQL):
		$retval = $conn->num_rows($result);
		break;
	default:
		$retval = 0;
		break;
	}

	return $retval;
}

function db_fetch_array($result)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$retval = pg_fetch_array($result, NULL, PGSQL_BOTH);
		break;
	case (Engine::MYSQL):
		$retval = $result->fetch_array(MYSQLI_BOTH);
		break;
	default:
		$retval = NULL;
		break;
	}

	return $retval;
}

function db_fetch_object($result)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$retval = pg_fetch_object($result);
		break;
	case (Engine::MYSQL):
		$retval = $result->fetch_object();
		break;
	default:
		$retval = NULL;
		break;
	}

	return $retval;
}

function db_real_escape_string($conn, $string)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$retval = pg_escape_string($string);
		break;
	case (Engine::MYSQL):
		$retval = $conn->real_escape_string($string);
		break;
	default:
		$retval = NULL;
		break;
	}

	return $retval;
}

function db_lastval($conn, $result)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$lastval = pg_query($conn, "SELECT lastval();");
		$row = pg_fetch_row($lastval);
		$retval = $row[0];
		break;
	case (Engine::MYSQL):
		$retval = $conn->insert_id;
		break;
	default:
		$retval = NULL;
		break;
	}

	return $retval;
}

function db_affected_rows($conn, $result)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$retval = pg_affected_rows($result);
		break;
	case (Engine::MYSQL):
		$retval = $conn->affected_rows($result);
		break;
	default:
		$retval = 0;
		break;
	}

	return $retval;
}

function db_close($conn)
{
	global $default_engine;

	switch ($default_engine) {
	case (Engine::POSTGRESQL):
		$retval = pg_close($conn);
		break;
	case (Engine::MYSQL):
		$retval = $conn->close();
		break;
	default:
		$retval = FALSE;
		break;
	}

	return $retval;
}

?>
