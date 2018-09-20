<?php
/**
 * load a config file and return units it as json
 */
namespace emoncms\units;

require __DIR__ . '/vendor/autoload.php';
use Yosymfony\Toml\Toml;
// https://github.com/yosymfony/toml

// currently only returns the 'units' part of the config file
// @todo: this would be better as a generic config reader with a "units" option selected

$filename = 'custom-config.toml';
try {
    $config = Toml::ParseFile($filename);
} catch (Exception $e) {
    $config = array("success"=>false,"message"=>$e->getMessage());
}
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


/**
 * @ todo: create api endpoint to add to the config file??
 * you can use the TomlBuilder class to build toml data from PHP
 */
// use Yosymfony\Toml\TomlBuilder;
// $tb = new TomlBuilder();
// $values = $tb->addComment('edited by [user] on [date]')
//     ->addTable('widget')
//     ->addValue('height', "120px")
//     ->getTomlString();
// file_put_contents ($filename , $values, FILE_APPEND);
