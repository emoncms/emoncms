<?php
$ltime = microtime(true);

define('EMONCMS_EXEC', 1);

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');


/**
 * Application defines
 */
if (!defined('DS')) {
    define('DS', '/');
}
define('ROOT', dirname(__FILE__) . DS);
define('CORE', ROOT . 'Core' . DS);
define('LIB', CORE . 'Lib' . DS);

/**
 * Load up required libs
 */
require_once CORE . 'Utility' . DS  . 'Configure.php';
require_once LIB . 'Enum.php';

require_once ROOT . 'process_settings.php';
require_once LIB . 'core.php';

require_once LIB . 'route.php';
require_once LIB . 'locale.php';
require_once CORE . 'Model' . DS . 'ConnectionManager.php';
require_once CORE . 'Model' . DS . 'Model.php';

if (defined('EMON_TEST_ENV') && EMON_TEST_ENV) {
	require_once CORE . 'TestSuite' . DS . 'EmonTestCase.php';
}