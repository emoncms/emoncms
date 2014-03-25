<?php
class ModelTest extends EmonTestCase 
{
	public $fixtures = array(
		//'User',
	);

	public function setUp() 
	{
		parent::setUp();
		$this->Model = new Model();

		$this->Model->useTable = 'users';
	}

/**
 * tear down
 */
	public function tearDown() 
	{
		unset($this->Model);

		parent::tearDown();
	}

/**
 * test create
 */
	public function testCreate() 
	{
		$this->Model->id = 999;
		$this->Model->create();

		$this->assertEmpty($this->Model->id);
	}

/**
 * test exists
 *
 * @dataProvider existsDataProvider
 */
	public function testExists($data, $expected) 
	{
		$this->Model->create();
		$this->assertTrue($this->Model->exists($data) === $expected);

		$this->Model->id = $data;
		$this->assertTrue($this->Model->exists() === $expected);
	}

	public function existsDataProvider() 
	{
		return array(
			'not exists' => array(
				999,
				false,
			),
			'user 1' => array(
				1,
				true,
			),
			'user 2' => array(
				2,
				true,
			),
		);	
	}

/**
 * test save all
 * 
 * @dataProvider saveAllDataProvider
 */
	public function testSaveAll($data, $expected) 
	{
		$result = $this->Model->saveAll($data);
		$this->assertEquals($expected, $result);
	}

	public function saveAllDataProvider() 
	{
		return array(
			'all new' => array(
				array(
					array(
						'username' => 'foobar',
					),
					array(
						'username' => 'foobaz',
					),
				),
				array(
					3 => true,
					4 => true,
				)
			),
			'mixed records' => array(
				array(
					array(
						'username' => 'foobaz',
					),
					array(
						'id' => 2,
						'username' => 'foobar',
					),
				),
				array(
					3 => true,
					2 => true,
				)
			),
			'all old' => array(
				array(
					array(
						'id' => 1,
						'username' => 'foobaz',
					),
					array(
						'id' => 2,
						'username' => 'foobar',
					),
				),
				array(
					1 => true,
					2 => true,
				)
			),
		);
	}

/**
 * test field with invalid field name
 *
 * @expectedException PDOException
 */
	public function testFieldInvalid() 
	{
		$result = $this->Model->field(sprintf('SELECT id FROM users where id = %d', 1));
		$this->assertEquals(1, $result);

		$this->Model->field(sprintf('SELECT madeup_field FROM users where id = %d', 1));
	}

/**
 * test field
 *
 * @dataProvider fieldDataProvider
 *
 * @return void
 */
	public function testField($data, $expected) 
	{
		$result = $this->Model->field(sprintf('SELECT %s FROM users where id = %d', $data['field'], $data['id']));
		$this->assertEquals($expected, $result);

		$result = $this->Model->field(sprintf('SELECT %s AS foobar FROM users where id = %d', $data['field'], $data['id']));
		$this->assertEquals($expected, $result);

		$result = $this->Model->field(sprintf('SELECT %s FROM users where id = :id', $data['field']), array(
			'id' => $data['id'],
		));
		$this->assertEquals($expected, $result);

		$result = $this->Model->field(sprintf('SELECT %s AS foobar FROM users where id = :id', $data['field']), array(
			'id' => $data['id'],
		));
		$this->assertEquals($expected, $result);
	}

	public function fieldDataProvider() 
	{
		return array(
			'user 1 username' => array(
				array(
					'id' => 1,
					'field' => 'username',
				),
				'bobsmith'
			),
			'user 1 id' => array(
				array(
					'id' => 1,
					'field' => 'id',
				),
				1
			),
			'not found' => array(
				array(
					'id' => 999,
					'field' => 'id',
				),
				null,
			)
		);
	}

/**
 * @dataProvider insertDataProvider
 */
	public function testInsert($data, $expected) 
	{
		$result = $this->Model->save($data);
		$this->assertEquals($expected, $result);

		$this->assertEquals($result['id'], $this->Model->id);
		$this->assertEquals($result['id'], $this->Model->lastInsertId());
	}

	public function insertDataProvider() 
	{
		return array(
			'user 1' => array(
				array(
					'username' => 'bob',
					'email' => 'bob@bob.com',
				),
				array(
					'id' => '3',
					'username' => 'bob',
					'email' => 'bob@bob.com',
					'password' => null,
					'salt' => null,
					'apikey_write' => null,
					'apikey_read' => null,
					'lastlogin' => null,
					'admin' => '0',
					'gravatar' => null,
					'name' => null,
					'location' => null,
					'timezone' => null,
					'language' => 'en_EN',
					'bio' => null,
				),
			)
		);
	}

/**
 * @dataProvider updateDataProvider
 */
	public function testUpdate($data, $expected) 
	{
		$result = $this->Model->save($data);
		$this->assertEquals($expected, $result);

		$this->assertEquals($data['id'], $this->Model->id);
		$this->assertEquals($data['id'], $this->Model->lastInsertId());
	}

	public function updateDataProvider() 
	{
		return array(
			'user 1' => array(
				array(
					'id' => 1,
					'username' => 'bob',
					'email' => 'bob@bob.com',
				),
				array(
					'id' => '1',
					'username' => 'bob',
					'email' => 'bob@bob.com',
					'password' => 'hash',
					'salt' => 'abc',
					'apikey_write' => 'aaa',
					'apikey_read' => 'bbb',
					'lastlogin' => '2014-01-01 00:00:00',
					'admin' => '1',
					'gravatar' => 'bob@smith.com',
					'name' => 'Bob Smith',
					'location' => 'London, UK',
					'timezone' => '0',
					'language' => 'en_UK',
					'bio' => 'some profile info',
				),
			)
		);
	}

/**
 * test query
 *
 * @dataProvider queryDataProvider
 */
	public function testQuery($data, $expected) 
	{
		$result = $this->Model->query($data['sql'], $data['values']);
		$this->assertEquals($expected, $result);
	}

	public function queryDataProvider() 
	{
		return array(
			'select simple' => array(
				array(
					'sql' => 'SELECT id, username FROM users',
					'values' => array(),
				),
				array(
					array(
						'id' => 1,
						'username' => 'bobsmith',
					),
					array(
						'id' => 2,
						'username' => 'samjones',
					),
				)
			),
			'select with values' => array(
				array(
					'sql' => 'SELECT id, email FROM users WHERE id = :the_users_id',
					'values' => array(
						'the_users_id' => 2,
					),
				),
				array(
					array(
						'id' => 2,
						'email' => 'sam@jones.com',
					),
				)
			)
		);
	}

/**
 * test rows
 *
 * @dataProvider rowsDataProvider
 */
	public function testRows($data, $expected) 
	{
		$result = $this->Model->rows($data['sql'], $data['values']);
		$this->assertEquals($expected, $result);
	}

	public function rowsDataProvider()
	{
		return array(
			'normal query' => array(
				array(
					'sql' => 'select username, email from users',
					'values' => array(),
				),
				array(
					array(
						'username' => 'bobsmith',
						'email' => 'bob@smith.com',
					),
					array(
						'username' => 'samjones',
						'email' => 'sam@jones.com',
					)
				)
			),
			'filtered query' => array(
				array(
					'sql' => 'select username, email from users where id = 2',
					'values' => array(),
				),
				array(
					array(
						'username' => 'samjones',
						'email' => 'sam@jones.com',
					)
				)
			),
			'prepared query' => array(
				array(
					'sql' => 'select username, email from users where id = :something',
					'values' => array(
						'something' => 1,
					),
				),
				array(
					array(
						'username' => 'bobsmith',
						'email' => 'bob@smith.com',
					),
				)
			),
			'empty result' => array(
				array(
					'sql' => 'select username, email from users where id = 999',
					'values' => array(),
				),
				array()
			),
		);
	}
	
}