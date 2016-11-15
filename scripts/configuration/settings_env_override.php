<?php    
/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:  http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

// Load default settings
require_once "../../settings.php";

/*
	Load settings from environment variables overriding defaults
	Allow you to run multiple variants of the same installation (e.g. for testing).
*/

//1 #### Mysql database settings
if (isset($_ENV["MYSQL_HOST"]))     $server = $_ENV["MYSQL_HOST"];
if (isset($_ENV["MYSQL_DATABASE"])) $database = $_ENV["MYSQL_DATABASE"];
if (isset($_ENV["MYSQL_USER"]))     $username = $_ENV["MYSQL_USER"];
if (isset($_ENV["MYSQL_PASSWORD"])) $password = $_ENV["MYSQL_PASSWORD"];
if (isset($_ENV["MYSQL_PORT"]))     $port = $_ENV["MYSQL_PORT"];

//2 #### redis
// create the array if it's not already been done
if (!isset($redis_server)) $redis_server = array();

if (isset($_ENV["REDIS_ENABLED"]))  $redis_enabled = $_ENV["REDIS_ENABLED"] === 'true';
if (isset($_ENV["REDIS_HOST"]))     $redis_server['host'] = $_ENV["REDIS_HOST"];
if (isset($_ENV["REDIS_PORT"]))     $redis_server['port'] = $_ENV["REDIS_PORT"];
if (isset($_ENV["REDIS_AUTH"]))     $redis_server['auth'] = $_ENV["REDIS_AUTH"];
if (isset($_ENV["REDIS_PREFIX"]))   $redis_server['prefix'] = $_ENV["REDIS_PREFIX"];

//3 #### MQTT
// create the array if it's not already been done
if (!isset($mqtt_server)) $mqtt_server = array();
if (isset($_ENV["MQTT_ENABLED"]))  $mqtt_enabled = $_ENV["MQTT_ENABLED"] === 'true';

if (isset($_ENV["MQTT_HOST"]))     $redis_server['host'] = $_ENV["MQTT_HOST"];
if (isset($_ENV["MQTT_PORT"]))     $redis_server['port'] = $_ENV["MQTT_PORT"];
if (isset($_ENV["MQTT_USER"]))     $redis_server['user'] = $_ENV["MQTT_USER"];
if (isset($_ENV["MQTT_PASSWORD"]))     $redis_server['password'] = $_ENV["MQTT_PASSWORD"];
if (isset($_ENV["MQTT_BASETOPIC"]))     $redis_server['basetopic'] = $_ENV["MQTT_BASETOPIC"];

