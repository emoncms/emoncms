<?php
/**
 * load a config file and return units it as json
 */

require __DIR__ . '/vendor/autoload.php';
use Yosymfony\Toml\Toml;
// https://github.com/yosymfony/toml

$filename = 'custom-config.toml';
try {
    $config = Toml::ParseFile($filename);
} catch (Exception $e) {
    $config = array("success"=>false,"message"=>$e->getMessage());
}
header('Content-Type: application/json');
echo json_encode($config['units'],JSON_UNESCAPED_UNICODE);


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
