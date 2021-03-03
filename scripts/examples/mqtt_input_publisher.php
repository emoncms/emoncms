<?php
/*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

*/
/***
 * Example of how to publish to mqtt topic using the Mosquitto Client
 */
 
define('EMONCMS_EXEC', 1);
chdir(dirname(__FILE__)."/../../"); // emoncms root
require "process_settings.php";     // load mqtt settings from settings.php
$basetopic = $settings['mqtt']['basetopic'];

// create new instance of mosquitto client
$mqtt = new Mosquitto\Client('Emoncms input publish example');
$qos = 2;

// callback functions
$mqtt->onConnect(function() use ($mqtt, $qos, $basetopic) {
    // on connect publish messages
    // publish (topic, payload, QoS)
    // $mqtt->publish("$basetopic/emontx/power1",100.0, $qos);
    // $mqtt->publish("$basetopic/emontx","100,200,300", $qos);

    // publish message with json payload
    $m = array('time'=>time(), 'power1'=>100, 'power2'=>200, 'power3'=>300);
    $mqtt->publish("$basetopic/emontx/0",json_encode($m), $qos);
    
});
$mqtt->onPublish(function($message_id){
    printf("published %s\n", $message_id);
    
    global $mqtt;
    if ($message_id==5) $mqtt->disconnect();
});
$mqtt->onDisconnect( function() { echo "Disconnected cleanly\n"; });

$mqtt->setCredentials($settings['mqtt']['user'],$settings['mqtt']['password']);
$mqtt->connect($settings['mqtt']['host'], $settings['mqtt']['port'], 5);
$mqtt->loopForever();
