<?php
$root = dirname(__FILE__) . DIRECTORY_SEPARATOR;

$ltime = microtime(true);

define('EMONCMS_EXEC', 1);

// 1) Load settings and core scripts
require $root . 'process_settings.php';
require $root . 'core.php';
require $root . 'route.php';
require $root . 'locale.php';
require CORE . 'Model' . DS . 'ConnectionManager.php';
require CORE . 'Model' . DS . 'Model.php';

require CORE . 'TestSuite' . DS . 'EmonTestCase.php';

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
			sprintf('Base Path: %s', CORE),
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
	        $fileList = array_merge($fileList, $file);
	    }

		$suite->addTestFiles($fileList);

		return $suite;
	}
}