<?php
class EmonTestCase extends PHPUnit_Framework_TestCase 
{
/**
 * Assert text equality, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $expected The expected value.
 * @param string $result The actual value.
 * @param message The message to use for failure.
 * @return boolean
 */
	public function assertTextNotEquals($expected, $result, $message = '') {
		$expected = str_replace(array("\r\n", "\r"), "\n", $expected);
		$result = str_replace(array("\r\n", "\r"), "\n", $result);
		return $this->assertNotEquals($expected, $result, $message);
	}

/**
 * Assert text equality, ignoring differences in newlines.
 * Helpful for doing cross platform tests of blocks of text.
 *
 * @param string $expected The expected value.
 * @param string $result The actual value.
 * @param message The message to use for failure.
 * @return boolean
 */
	public function assertTextEquals($expected, $result, $message = '') {
		$expected = str_replace(array("\r\n", "\r"), "\n", $expected);
		$result = str_replace(array("\r\n", "\r"), "\n", $result);
		return $this->assertEquals($expected, $result, $message);
	}
}