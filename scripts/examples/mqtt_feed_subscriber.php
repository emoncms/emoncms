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

// create new instance of mosquitto client    
$mqtt = new Mosquitto\Client('Emoncms feed subscribe example');
$qos = 0;
$topic = 'emoncms';
// callback functions
$mqtt->onDisconnect( function() { echo "Disconnected cleanly\n"; });
$mqtt->onConnect( function() use ($mqtt, $qos, $topic) {
    echo "Connected to server.\n";
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
    printf("%s - Got a message on topic %s with payload:\n%s\n", $message->mid, $message->topic, $message->payload); 
});
// connect to localhost:1883 with keepalive 5
$mqtt->connect("localhost", 1883, 5);

// Call loop() in an infinite blocking loop
$mqtt->loopForever();