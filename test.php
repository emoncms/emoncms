<?php
if (!class_exists('PHPUnit_Framework_TestSuite')) {
	throw new Exception('PHPUnit is required for running unit tests - http://phpunit.de/');
}

define('EMON_TEST_ENV', 1);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

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