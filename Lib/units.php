<?php
/**
 * define UNITS if included in php or return JSON array if called directly
 */
namespace emoncms\units;

$config['units'] = array(
    array("short" => "W", "long" => "Watt"),
    array("short" => "kWh", "long" => "Kilowatt Hour"),
    array("short" => "Wh", "long" => "Watt-Hour"),
    array("short" => "V", "long" => "Volt"),
    array("short" => "VA", "long" => "Volt-Ampere"),
    array("short" => "A", "long" => "Ampere"),
    array("short" => "°C", "long" => "Celsius"),
    array("short" => "K", "long" => "Kelvin"),
    array("short" => "°F", "long" => "Fahrenheit"),
    array("short" => "%", "long" => "Percent"),
    array("short" => "Hz", "long" => "Hertz"),
    array("short" => "pulses", "long" => "Pulses"),
    array("short" => "dB", "long" => "Decibel")
);
// list of PHP includes
$includes = get_included_files();
// if this script is not called directly (included)
if(array_search(__FILE__, $includes)>0) {
    // set the UNIT const
    if(!empty($config['units'])) define('UNITS', $config['units']);
} else {
// if this script is called directly (not included)
    // return the values as a json object
    header('Content-Type: application/json');
    echo json_encode($config['units'], JSON_UNESCAPED_UNICODE);    // this script is being included by another
}
