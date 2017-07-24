<?php

// PHP 5.3 - 7.x compatibility
if (!class_exists('\PHPUnit\Framework\TestCase') &&
    class_exists('\PHPUnit_Framework_TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
}

class Test extends \PHPUnit\Framework\TestCase
{
	public function testOnePlusOne() {
		$this->assertEquals(1+1, 2);
  	}
}

?>
