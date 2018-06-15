<?php

/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
  ---------------------------------------------------------------------
  This script has been developed by Carbon Co-op - http://www.carbon.coop
 */


//********************
// User configuration
//********************
$email_domain = "carbon.coop";


//********************
// Help
//********************
$help_string = "Script for bulk creation of users, create one device for the user and add user to a group. The script outputs a csv table including: username, userid, password, apikey_read, apikey_write, device_key.\n"
        . "Arguments:\n"
        . "     -h          shows this help\n"
        . "     -d:         followed by device template. Used if device module is installed. If 'device template' doesn't exist, the script will finish\n"
        . "     -dnode:     followed by device node. Used if device module is installed\n"
        . "     -dname:     followed by device name. Used if device module is installed.\n"
        . "     -g:         followed by group name. Used if groups module is installed.  If there isn't a group with 'group name', the script will finish\n"
        . "\n Typical uses:\n"
        . "     php create_users_and_devices_add_to_group.php Ben Matt\n"
        . "     php create_users_and_devices_add_to_group.php -d:emonth Ben Matt\n"
        . "     php create_users_and_devices_add_to_group.php -d:emonth -dnode:emontx -dname:my_device Ben Matt\n"
        . "     php create_users_and_devices_add_to_group.php -g:my_group Ben Matt\n"
        . "     php create_users_and_devices_add_to_group.php -d:emonth -g:my_group -dnode:emontx -dname:my_device Ben Matt\n\n";


//********************
// 
//********************
if (php_sapi_name() != 'cli')
    die("CLI commenad only");
define('EMONCMS_EXEC', 1);
chdir(dirname(__FILE__) . "/../");
echo "\n";
$leading_line = false;


//***************
// Logger
//***************
require "Lib/EmonLogger.php";
$log = new EmonLogger(__FILE__);
$log->info("Starting create_users_and_devices script");


//*********************
// Connect to database
//*********************
require "process_settings.php";
$mysqli = @new mysqli($server, $username, $password, $database, $port);
if ($mysqli->connect_error)
    die("Can't connect to database:" . $mysqli->connect_error . "\n\n");


//***************
// Redis
//***************
if ($redis_enabled) {
    $redis = new Redis();
    if (!$redis->connect($redis_server['host'], $redis_server['port']))
        die("Could not connect to redis at " . $redis_server['host'] . ":" . $redis_server['port'] . "\n\n");
    $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
    if (!empty($redis_server['auth'])) {
        if (!$redis->auth($redis_server['auth']))
            die("Could not connect to redis at " . $redis_server['host'] . ", autentication failed" . "\n\n");
    }
}
else {
    $redis = false;
}


//********************
// Extract arguments
//********************
$usernames = [];
array_shift($argv); // first element in the array is the script name, we remove it
foreach ($argv as $arg) {
    if (substr($arg, 0, 2) == '-h')
        die($help_string);
    elseif (substr($arg, 0, 3) == '-d:')
        $device_template = substr($arg, 3);
    elseif (substr($arg, 0, 7) == '-dnode:')
        $device_node = substr($arg, 7);
    elseif (substr($arg, 0, 7) == '-dname:')
        $device_name = substr($arg, 7);
    elseif (substr($arg, 0, 3) == '-g:')
        $group_name = substr($arg, 3);
    elseif (substr($arg, 0, 1) == '-')
        echo "\033[31m" . $arg . " is not a valid argument\033[0m\n";
    else
        array_push($usernames, $arg);
}

//***************
// Models to use
//***************
global $email_verification;
$email_verification = false;
require("Modules/user/user_model.php");
$user = new User($mysqli, $redis, null);

if (!is_file("Modules/device/device_model.php")) {
    echo "\033[31mDevice module not installed, no device will be created\033[0m\n";
    $device_support = false;
}
else {
    require "Modules/device/device_model.php";
    $device = new Device($mysqli, $redis);
    $device_support = true;
}


if (!is_file("Modules/group/group_model.php")) {
    $group_support = false;
    echo "\033[31mGroup module not installed, users won't be added to group\033[0m\n";
}
else {
    require "Modules/group/group_model.php";
    $group = new Group($mysqli, $redis, $user, null, null, null);
    $group_support = true;
}


//***********************************
// Check group and device are valid
//***********************************
if ($device_support && isset($device_template) && !is_file("Modules/device/data/" . $device_template . ".json"))
    die("\033[31mDevice template not valid, die :( \033[0m\n\n");
if ($group_support && isset($group_name) && !$group->exists_name($group_name))
    die("\033[31mGroup doesn't exist, die :( \033[0m\n\n");


//************************************************
// Create users and devices and add user to group
//************************************************
$out = "username, userid, password, apikey_read, apikey_write";
if ($device_support && isset($device_template))
    $out .= ", devicekey";
$out .= "\n";

foreach ($usernames as $uname) {
    $pwd = base64_encode(openssl_random_pseudo_bytes(8));
    $result = $user->register($uname, $pwd, $uname . "@" . $email_domain);
    if ($result['success'] == false) {
        echo "\033[31mUser $uname was not created\033[0m - " . $result['message'] . "\n";
        $leading_line = true;
    }
    else {
        $out .= $uname . ", " . $result['userid'] . ", $pwd, " . $result['apikey_read'] . ", " . $result['apikey_write'];
        if ($group_support && isset($group_name)) {
            $groupid = $group->get_groupid($group_name);
            $group->add_user($groupid, $result['userid']);
        }
        if ($device_support && isset($device_template)) {
            $deviceid = $device->create($result['userid']);
            $fields = '{"type":"' . $device_template . '"';
            if (isset($device_node))
                $fields .= ', "nodeid":"' . $device_node . '"';
            if (isset($device_name))
                $fields .= ', "name":"' . $device_name . '"';
            $fields .= '}';
            $result = $device->set_fields($deviceid, $fields);
            if ($result['success'] == false)
                echo "\033[31mFields for $uname's device could not be saved\033[0m\n";
            $result = $device->get($deviceid);
            $out .= ", " . $result['devicekey'];
        }
        $out .= "\n";
    }
}

//**********************
// Print output
//**********************
if ($leading_line)
    echo"\n";
echo $out;
echo"\n";




