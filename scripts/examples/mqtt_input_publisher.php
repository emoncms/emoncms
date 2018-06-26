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

// create new instance of mosquitto client
$mqtt = new Mosquitto\Client('Emoncms input publish example');
$qos = 0;
$topic = 'emoncms';
// callback functions
$mqtt->onDisconnect( function() { echo "Disconnected cleanly\n"; });
$mqtt->onConnect(function() use ($mqtt, $qos, $topic) {
    // on connect publish messages
    // publish (topic, payload, QoS)
    $mqtt->publish("emoncms/input/5","100,200,300", $qos);
    $mqtt->publish("emoncms/input/10/power", 350.3, $qos);
    $mqtt->publish("emoncms/input/house/power", 2500, $qos);
    $mqtt->publish("emoncms/input/house/temperature", 18.2, $qos);
    $m = array(
        'apikey'=>"5cd8596404exxxxa2ccc5c2a9c24bc7",
        'time'=>time(),
        'node'=>1,
        'csv'=>array(200,300,400)
    );
    // publish message with json payload
    $mqtt->publish("emoncms/input",json_encode($m), $qos);
    // disconnect once finished
    $mqtt->disconnect();
});
$mqtt->onPublish(function($message_id){
    printf("published %s\n", $message_id); 
});
$mqtt->connect('localhost');
$mqtt->loopForever();
