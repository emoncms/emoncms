<?php
require_once 'Modules/admin/update_class.php';
require_once CORE . 'Model' . DS  . 'dbschemasetup.php';

class EmonTestCase extends PHPUnit_Extensions_Database_TestCase 
{
	protected $_testDbConnection = 'test';

	public $fixtures = array(
	);

	protected $_allFixtures = array();

	public $conn = null;

	protected $_databaseInit = false;

	protected function _loadDatabase() {
		if (Configure::read('Test.dbLoaded')) {
			return;
		}

		foreach (ConnectionManager::tables($this->_testDbConnection) as $table) 
		{
			ConnectionManager::drop($this->_testDbConnection, $table);
		}

		$connection = Configure::read('DB_CONFIG.' . $this->_testDbConnection);
		$mysqli = mysqli_connect($connection['server'], $connection['username'], $connection['password'], $connection['database']);
        $Update = new Update($mysqli);
        db_schema_setup($mysqli, load_db_schema(), true);
        foreach ($Update->methodsToRun() as $method) 
        {
            $Update->{$method}(true);
        }

        Configure::write('Test.dbLoaded', true);
	}

	public function setUp() 
	{
		$this->_loadDatabase();
		$this->_loadAllFixtures();

		parent::setUp();
	}

	public function tearDown() 
	{
		foreach (ConnectionManager::tables($this->_testDbConnection) as $table) 
		{
			ConnectionManager::truncate($this->_testDbConnection, $table);
		}

		parent::tearDown();
	}

	protected function _loadAllFixtures() 
	{
		if (empty($this->_allFixtures)) 
		{
			$DirectoryIterator = new RecursiveDirectoryIterator(ROOT);
			$Iterator = new RecursiveIteratorIterator($DirectoryIterator);
			$files = new RegexIterator($Iterator, '/.*Fixture\.xml/', RegexIterator::GET_MATCH);
			foreach($files as $file) 
			{
				$this->_allFixtures = array_merge($this->_allFixtures, $file);
			}
		}

		return $this->_allFixtures;
	}
 
	public function getConnection() 
	{
		if ($this->conn === null) 
		{
			$this->conn = ConnectionManager::getDataSource($this->_testDbConnection);
		}
		return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($this->conn, $this->_testDbConnection);
	}
 
	public function getDataSet($fixtures = array())
	{ 
		$data = array();
		foreach ($this->_loadAllFixtures() as $fixture) {
			$data[] = file_get_contents($fixture);
		}
		$data = '<?xml version="1.0" ?><dataset>' . implode("\n", $data) . '</dataset>';
		if (!file_put_contents('/tmp/emoncms.fixture.xml', $data)) {
			throw new Exception('Could not cache fixture data');
		}

		return new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet('/tmp/emoncms.fixture.xml');
	}
 
	public function loadDataSet($dataSet) 
	{
		$this->getDatabaseTester()->setDataSet($dataSet);
		$this->getDatabaseTester()->onSetUp();
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
	public function assertTextNotEquals($expected, $result, $message = '') 
	{
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
	public function assertTextEquals($expected, $result, $message = '') 
	{
		$expected = str_replace(array("\r\n", "\r"), "\n", $expected);
		$result = str_replace(array("\r\n", "\r"), "\n", $result);
		return $this->assertEquals($expected, $result, $message);
	}
}