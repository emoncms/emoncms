<?php
class ModelTest extends EmonTestCase 
{
	public function setUp() {
		parent::setUp();
		$this->Model = new Model(array(

		));

		$this->Model->useTable = 'users';
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testCreate() {
		$this->Model->id = 999;
		$this->Model->create();

		$this->assertEmpty($this->Model->id);
	}

	public function testExists() {
		$this->assertFalse($this->Model->exists(999));

		$this->Model->id = 999;
		$this->assertFalse($this->Model->exists());
	}

/**
 * @dataProvider insertDataProvider
 */
	public function testInsert($data, $expected) {
		$result = $this->Model->save($data);
		$this->assertEquals($expected, $result);
	}

	public function insertDataProvider() {
		return array(
			'user 1' => array(
				array(
					'username' => 'bob',
					'email' => 'bob@bob.com',
				),
				array(
					'id' => 1,
					'username' => 'bob',
					'email' => 'bob@bob.com',
				),
			)
		);
	}
}