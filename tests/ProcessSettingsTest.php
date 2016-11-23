<?php
use PHPUnit\Framework\TestCase;

class ProcessSettingsTest extends TestCase
{
    public function testNonExistantFileLoad()
    {
        // when the settings file does not exist
        $_ENV["EMONCMS_CONFIG_FILE"] = '/some/nonexistant/file.php';

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
        $_ENV["EMONCMS_CONFIG_FILE"] = basename(__DIR__).'/../default.settings.php';
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
}
