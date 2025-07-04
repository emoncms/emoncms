<?php
/*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/
// CLI only
if (php_sapi_name() !== 'cli') {
    echo "This script is for CLI use only.\n";
    die;
}

/***
 * Example of how to publish to mqtt topic using the Mosquitto Client
 * This script publishes one message for each supported Emoncms MQTT input format.
 */

define('EMONCMS_EXEC', 1);
chdir(dirname(__FILE__)."/../../"); // emoncms root
require "process_settings.php";     // load mqtt settings from settings.php
$basetopic = $settings['mqtt']['basetopic'];

$mqtt = new Mosquitto\Client('Emoncms input publish example');
$qos = 2;

$mqtt->onConnect(function() use ($mqtt, $qos, $basetopic) {
    // 1. Single value (plain number)
    $mqtt->publish("$basetopic/mqtt_test/single_value", "100", $qos);

    // 2. Comma-separated values
    $mqtt->publish("$basetopic/mqtt_test_csv", "100,200,300", $qos);

    // 3. JSON object with key-value pairs
    $mqtt->publish("$basetopic/mqtt_test_json_key_val", json_encode(['power1'=>100, 'vrms'=>230.1]), $qos);

    // 4. JSON object with time (as number)
    $mqtt->publish("$basetopic/mqtt_test_json_with_time", json_encode(['power1'=>100, 'vrms'=>230.1, 'time'=>time()+300]), $qos);

    // 5. JSON object with time (as string)
    // create a date string in ISO 8601 format add 10 mins
    $date = new DateTime();
    $date->modify('+10 minutes');
    $inputtime = $date->format('c'); // ISO 8601 format
    $mqtt->publish("$basetopic/mqtt_test_json_with_date", json_encode(['power1'=>100, 'time'=>$inputtime]), $qos);

    // 6. JSON object with nested {name, value} objects
    $mqtt->publish("$basetopic/mqtt_test_nested", json_encode([
        'power'=>['name'=>'ct1','value'=>100],
        'vrms'=>['value'=>230.1]
    ]), $qos);

    // 7. Device auto-configuration (if 'describe' key is present)
    $mqtt->publish("$basetopic/mqtt_test_describe", json_encode(['describe'=>'example device']), $qos);

});

$mqtt->onPublish(function($message_id){
    printf("published %s\n", $message_id);

    // Disconnect after publishing all messages
    global $mqtt;
    if ($message_id === 7) { // Assuming the last message is the one with ID 7
        $mqtt->disconnect();
    }
});
$mqtt->onDisconnect(function() { echo "Disconnected cleanly\n"; });

$mqtt->setCredentials($settings['mqtt']['user'],$settings['mqtt']['password']);
$mqtt->connect($settings['mqtt']['host'], $settings['mqtt']['port'], 5);
$mqtt->loopForever();