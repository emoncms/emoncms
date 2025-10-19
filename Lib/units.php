<?php
/**
 * define UNITS if included in php or return JSON array if called directly
 */
namespace emoncms\units;

$config['units'] = array(
    array("short" => "W", "long" => "Watt"),
    array("short" => "kW", "long" => "Kilowatt"),
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
    array("short" => "rpm", "long" => "Revolutions per minute"),
    array("short" => "pulses", "long" => "Pulses"),
    array("short" => "dB", "long" => "Decibel"),
    array("short" => "hPa", "long" => "Hectopascal"),
    array("short" => "ppm", "long" => "Parts per million"),
    array("short" => "µg/m³", "long" => "micro grams per m3"),
    array("short" => "m³", "long" => "m3"),
    array("short" => "m³/h", "long" => "m3/hr"),
    array("short" => "l/m", "long" => "liters/minute"),
    array("short" => "l/h", "long" => "liters/hour")
);
// list of PHP includes
$includes = get_included_files();
// if this script is not called directly (included)
if (array_search(__FILE__, $includes)>0) {
    // set the UNIT const
    if (!empty($config['units'])) {
        define('UNITS', $config['units']);
    }
} else {
// if this script is called directly (not included)
    // return the values as a json object
    header('Content-Type: application/json');
    echo json_encode($config['units'], JSON_UNESCAPED_UNICODE);    // this script is being included by another
}
