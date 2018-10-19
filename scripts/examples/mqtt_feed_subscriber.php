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
 * Example of how to subscribe to mqtt topic the Mosquitto Client
 */
 
define('EMONCMS_EXEC', 1);
chdir(dirname(__FILE__)."/../../"); // emoncms root
require "process_settings.php";     // load mqtt settings from settings.php

// create new instance of mosquitto client    
$mqtt = new Mosquitto\Client('Emoncms feed subscribe example');
$qos = 2;
$topic = 'emoncms';

// ------------------------------------------------------------------------
// Callback functions
// ------------------------------------------------------------------------
$mqtt->onConnect( function() use ($mqtt, $qos, $topic) {
    echo "Connected to server\n";
    // subscribe to all topics
    $mqtt->subscribe("$topic/#", $qos);
});

$mqtt->onSubscribe( function($mid, $qosCount) use ($mqtt, $qos, $topic) {
    /*
    @todo: finish this. possibly dont need the loopForever() should use the loop() with wait() function?
    the disconnect not working as expected. needs more work. CTRL+C to quit the script in CLI 
    // unsubscribe
    $mqtt->unsubscribe("$topic/#");
    // disconnect
    $mqtt->disconnect();
    */
});

// print topic value once received 
// $message is instance of Mosquitto\Message
$mqtt->onMessage( function($message) { 
    printf("%s - Got a message on topic %s with payload: %s\n", $message->mid, $message->topic, $message->payload); 
});

// Disconnect
$mqtt->onDisconnect( function() { echo "Disconnected cleanly\n"; });

// ------------------------------------------------------------------------
// Connect and loop forever
// ------------------------------------------------------------------------

$mqtt->setCredentials($mqtt_server['user'],$mqtt_server['password']);
$mqtt->connect($mqtt_server['host'], $mqtt_server['port'], 5);

// Call loop() in an infinite blocking loop
$mqtt->loopForever();
