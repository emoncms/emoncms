<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The button and curl widgets act in the session of whoever views the page, so
 * a public dashboard can be used to aim them at another account. They load only
 * when an admin sets [dashboard] enable_action_widgets.
 *
 * loadwidgets.php defines a constant and a function at file scope, so each case
 * runs in its own php process against a directory holding just the dashboard
 * module.
 */
final class DashboardActionWidgetsTest extends TestCase
{
    private static string $root;
    private static string $sandbox;

    public static function setUpBeforeClass(): void
    {
        self::$root = dirname(__DIR__, 2);

        // A Modules directory containing only the dashboard module, so the
        // loader does not pull in widgets that expect a database.
        self::$sandbox = sys_get_temp_dir() . '/emoncms_widget_test_' . getmypid();
        @mkdir(self::$sandbox . '/Modules', 0777, true);
        @symlink(self::$root . '/Modules/dashboard', self::$sandbox . '/Modules/dashboard');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$sandbox . '/Modules/dashboard');
        @rmdir(self::$sandbox . '/Modules');
        @rmdir(self::$sandbox);
    }

    /**
     * Run the widget loader with the setting at $enabled and return the script
     * tags it emits.
     */
    private function loadWidgets(bool $enabled): string
    {
        $code = sprintf(
            'define("EMONCMS_EXEC",1); $path="/"; ' .
            '$GLOBALS["settings"]=["dashboard"=>["enable_action_widgets"=>%s]]; ' .
            'chdir(%s); require %s;',
            $enabled ? 'true' : 'false',
            var_export(self::$sandbox, true),
            var_export(self::$root . '/Modules/dashboard/Views/loadwidgets.php', true)
        );

        $output = shell_exec(escapeshellcmd(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1');
        $this->assertIsString($output);
        return $output;
    }

    #[Test]
    public function action_widgets_are_not_loaded_by_default(): void
    {
        $output = $this->loadWidgets(false);

        $this->assertStringNotContainsString('button_render.js', $output);
        $this->assertStringNotContainsString('curl_render.js', $output);
        // display widgets are unaffected
        $this->assertStringContainsString('dial_render.js', $output);
    }

    /**
     * A dashboard saved before the setting existed still holds the elements,
     * so they are labelled rather than left as empty boxes.
     */
    #[Test]
    public function disabled_action_widgets_get_a_placeholder(): void
    {
        $output = $this->loadWidgets(false);

        $this->assertStringContainsString('disabledwidgets.js', $output);
        $this->assertStringContainsString('disabled_widgets_init(["button","curl"])', $output);
    }

    #[Test]
    public function no_placeholder_once_enabled(): void
    {
        $output = $this->loadWidgets(true);

        $this->assertStringNotContainsString('disabledwidgets.js', $output);
        $this->assertStringNotContainsString('disabled_widgets_init', $output);
    }

    #[Test]
    public function action_widgets_are_loaded_once_enabled(): void
    {
        $output = $this->loadWidgets(true);

        $this->assertStringContainsString('button_render.js', $output);
        $this->assertStringContainsString('curl_render.js', $output);
        $this->assertStringContainsString('dial_render.js', $output);
    }

    #[Test]
    public function the_setting_ships_disabled(): void
    {
        $ini = parse_ini_file(self::$root . '/default-settings.ini', true, INI_SCANNER_TYPED);
        $this->assertFalse($ini['dashboard']['enable_action_widgets']);

        // default-settings.php assigns $_settings rather than returning it
        require self::$root . '/default-settings.php';
        $this->assertFalse($_settings['dashboard']['enable_action_widgets']);
    }
}
