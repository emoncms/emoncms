<?php

class ProcessSettingsTest extends PHPUnit_Framework_TestCase
{
    public function testNonExistantFileLoad()
    {
        // when the settings file does not exist
        putenv('EMONCMS_CONFIG_FILE=/some/nonexistant/file.php');

        // and I load the settings
        require(basename(__DIR__).'/../process_settings.php');

        // it sets $failed_settings_validation
        $this->assertTrue($failed_settings_validation);

        // it outputs the error message
        $this->expectOutputString("<div style='width:600px; background-color:#eee; padding:20px; font-family:arial;'><h3>settings.php file error</h3>Copy and modify default.settings.php to settings.php<br>For more information about configure settings.php file go to <a href=\"http://emoncms.org\">http://emoncms.org</a></div>");
    }

    public function testValidFileLoad()
    {
        // when the settings file exists
        putenv('EMONCMS_CONFIG_FILE='.basename(__DIR__).'/../default.settings.php');

        // and I load the settings
        require(basename(__DIR__).'/../process_settings.php');

        // it sets $failed_settings_validation, without output
        $this->assertFalse($failed_settings_validation);

        // it sets the MySQL global values to the expected defaults
        $this->assertEquals($server, "localhost");
        $this->assertEquals($database, "emoncms");
        $this->assertEquals($username, "_DB_USER_");
        $this->assertEquals($password, "_DB_PASSWORD_");
        $this->assertEquals($port, "3306");
        $this->assertTrue($dbtest);

        // it sets the Redis global values to the expected defaults
        $this->assertFalse($redis_enabled);
        $this->assertEquals($redis_server, array(
            'host'   => 'localhost',
            'port'   => 6379,
            'auth'   => '',
            'prefix' => 'emoncms'
          ));

        // it sets the MQTT global values to the expected defaults
        $this->assertFalse($mqtt_enabled);
        $this->assertEquals($mqtt_server, array(
            'host'     => 'localhost',
            'port'     => 1883,
            'user'     => '',
            'password' => '',
            'basetopic'=> 'emon'
          ));
    }

    public function testEnvironmentVariableLoad()
    {
        // when the settings file exists
        putenv('EMONCMS_CONFIG_FILE='.basename(__DIR__).'/../default.settings.php');

        // and the environment variables are set
        $_ENV['EMONCMS_MYSQL_HOST'] = '1';
        $_ENV['EMONCMS_MYSQL_DATABASE'] = '2';
        $_ENV['EMONCMS_MYSQL_USER'] = '3';
        $_ENV['EMONCMS_MYSQL_PASSWORD'] = '4';
        $_ENV['EMONCMS_MYSQL_PORT'] = '5';
        $_ENV['EMONCMS_REDIS_ENABLED'] = 'false';
        $_ENV['EMONCMS_REDIS_HOST'] = '7';
        $_ENV['EMONCMS_REDIS_PORT'] = '8';
        $_ENV['EMONCMS_REDIS_AUTH'] = '9';
        $_ENV['EMONCMS_REDIS_PREFIX'] = '10';
        $_ENV['EMONCMS_MQTT_ENABLED'] = 'false';
        $_ENV['EMONCMS_MQTT_HOST'] = '12';
        $_ENV['EMONCMS_MQTT_PORT'] = '13';
        $_ENV['EMONCMS_MQTT_USER'] = '14';
        $_ENV['EMONCMS_MQTT_PASSWORD'] = '15';
        $_ENV['EMONCMS_MQTT_BASETOPIC'] = '16';

        // and I load the settings
        require(basename(__DIR__).'/../process_settings.php');

        // it sets $failed_settings_validation, without output
        $this->assertFalse($failed_settings_validation);

        // it sets the MySQL global values to the environment variable values
        $this->assertEquals($server, '1');
        $this->assertEquals($database, '2');
        $this->assertEquals($username, '3');
        $this->assertEquals($password, '4');
        $this->assertEquals($port, '5');

        // it sets the Redis global values to the environment variable values
        $this->assertFalse($redis_enabled);
        $this->assertEquals($redis_server, array(
            'host'   => '7',
            'port'   => '8',
            'auth'   => '9',
            'prefix' => '10'
          ));

        // it sets the MQTT global values to the environment variable values
        $this->assertFalse($mqtt_enabled);
        $this->assertEquals($mqtt_server, array(
            'host'     => '12',
            'port'     => '13',
            'user'     => '14',
            'password' => '15',
            'basetopic'=> '16'
          ));

        // it sets $failed_settings_validation to false
        $this->assertFalse($failed_settings_validation);
    }
}
