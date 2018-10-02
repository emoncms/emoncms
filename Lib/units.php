<?php
/**
 * define UNITS if included in php or return JSON array if called directly
 */
namespace emoncms\units;

$config['units'] = [
    ["short" => "W", "long" => "Watt"],
    ["short" => "kWh", "long" => "Kilowatt Hour"],
    ["short" => "Wh", "long" => "Watt-Hour"],
    ["short" => "V", "long" => "Volt"],
    ["short" => "VA", "long" => "Volt-Ampere"],
    ["short" => "A", "long" => "Ampere"],
    ["short" => "°C", "long" => "Celsius"],
    ["short" => "K", "long" => "Kelvin"],
    ["short" => "°F", "long" => "Fahrenheit"],
    ["short" => "%", "long" => "Percent"],
    ["short" => "Hz", "long" => "Hertz"],
    ["short" => "pulses", "long" => "Pulses"],
    ["short" => "dB", "long" => "Decibel"]
];
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
