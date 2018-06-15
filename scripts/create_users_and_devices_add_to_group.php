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
$help_string = "Script for bulk creation of users. If device module is installed it can create one device for the user. If group module is installed it can add the user to a group as a passive member.\n"
        . "The script outputs a csv table including: username, userid, password, apikey_read, apikey_write, device_key. This table can be copied and pasted into a csv file for importing into a spreadsheet.\n"
        . "\nArguments:\n"
        . "     -h         shows this help\n"
        . "     -u         followed by a user name. At least one user name must be present. To create more than one user add as many -u as needed"
        . "     -d         followed by device template. Used if device module is installed. If 'device template' doesn't exist, the script will finish\n"
        . "     -dnode     followed by device node. Used if device module is installed\n"
        . "     -dname     followed by device name. Used if device module is installed.\n"
        . "     -g         followed by group name. Used if groups module is installed.  If there isn't a group with 'group name', the script will finish\n"
        . "\n Typical uses:\n"
        . "     php create_users_and_devices_add_to_group.php -u Ben -u Matt\n"
        . "     php create_users_and_devices_add_to_group.php -d emonth -u Ben -u Matt\n"
        . "     php create_users_and_devices_add_to_group.php -d emonth -dnode emontx -dname my_device -u Ben -u Matt\n"
        . "     php create_users_and_devices_add_to_group.php -g my_group -u Ben -u Matt\n"
        . "     php create_users_and_devices_add_to_group.php -d emonth -g my_group -dnode emontx -dname my_device -u Ben -u Matt\n\n";


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
$args = getopt('u:d:dnode:dname:g:h');
if (array_key_exists('h', $args))
    die($help_string);
if (!array_key_exists('u', $args))
    die("\033[31mMissing usernames.\033[0m Run -h to get some help\n\n");
else {
    $usernames = [];
    if (!is_array($args['u']))
        array_push($usernames, $args['u']);
    else {
        foreach ($args['u'] as $user)
            array_push($usernames, $user);
    }
}
if (array_key_exists('d', $args))
    $device_template = $args['d'];
if (array_key_exists('dnode', $args))
    $device_node = $args['dnode'];
if (array_key_exists('dname', $args))
    $device_name = $args['dname'];
if (array_key_exists('g', $args))
    $group_name = $args['g'];


//***************
// Models to use
//***************
global $email_verification;
$email_verification = false;
require("Modules/user/user_model.php");
$user = new User($mysqli, $redis, null);

if (isset($device_template) && !is_file("Modules/device/device_model.php")) {
    echo "\033[31mDevice module not installed, no device will be created\033[0m\n";
    $device_support = false;
}
else {
    require "Modules/device/device_model.php";
    $device = new Device($mysqli, $redis);
    $device_support = true;
}


if (isset($group_name) && !is_file("Modules/group/group_model.php")) {
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




