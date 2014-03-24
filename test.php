<?php
$root = dirname(__FILE__) . DIRECTORY_SEPARATOR;

$ltime = microtime(true);

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');

define('EMONCMS_EXEC', 1);
define('EMON_TEST_ENV', 1);

// 1) Load settings and core scripts
require_once $root . 'process_settings.php';
require_once $root . 'core.php';
require_once $root . 'route.php';
require_once $root . 'locale.php';

require_once CORE . 'Model' . DS . 'ConnectionManager.php';
require_once CORE . 'Model' . DS . 'Model.php';
require_once CORE . 'TestSuite' . DS . 'EmonTestCase.php';

/**
 * AllEmonTest
 *
 * Load up tests and run them
 */
class AllEmonTest extends PHPUnit_Framework_TestSuite {

/**
 * Load up all test files to be run
 *
 * @return void
 */
	public static function suite() {
		echo sprintf("EmonCMS Test Suite v%s\n", Configure::read('EmonCMS.version'));
		echo implode("\n", array(
			'==============================',
			sprintf('Base Path: %s', ROOT),
			sprintf('Core Path: %s', CORE),
			'==============================',
			'',
			''
		));
		$suite = new PHPUnit_Framework_TestSuite('All EmonCMS tests');

	    $dir = new RecursiveDirectoryIterator(CORE);
	    $ite = new RecursiveIteratorIterator($dir);
	    $files = new RegexIterator($ite, '/.*Test\.php/', RegexIterator::GET_MATCH);
	    $fileList = array();
	    foreach($files as $file) {
	    	foreach ($file as $k => $v) {
	    		if (strstr($v, 'StringTest.php') || strstr($v, 'HashTest.php')) {
	    			//unset($file[$k]);
	    		}
	    	}
	    	if (empty($file)) {
	    		continue;
	    	}
	        $fileList = array_merge($fileList, $file);
	    }

		$suite->addTestFiles($fileList);

		return $suite;
	}
}