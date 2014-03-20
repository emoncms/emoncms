<?php
class Model {
	public $useDbConfig = null;

	public $useTable = null;

	protected $_queryLog = array();

	public function __construct($config) {
		$this->useDbConfig = Configure::read('DB_CONFIG.database');
	}

/**
 * Fetch the value of a single field
 *
 * @param string $field the field name to fetch
 * @param string $query the query to select the field
 *
 * @return string|null
 */
	public function field($field, $query) {
		$Statement = $this->query($query);
		if ($Statement === null || !$Statement->rowCount()) {
			return $Statement;
		}
		
		return $Statement->fetch(PDO::FETCH_OBJ)->{$field};
	}	

/**
 * Fetch a row / array of data
 *
 * @param string $query the query to select the field
 *
 * @return string|null
 */
	public function row($query, array $values = array()) {
		return (array)current($this->rows($query, $values));
	}

/**
 * Fetch a row / array of data
 *
 * @param string $query the query to select the field
 *
 * @return string|null
 */
	public function rows($query, array $values = array()) 
	{
		$Statement = $this->query($query, $values);
		if (!$Statement->rowCount()) 
		{
			return array();
		}
		$rows = $Statement->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}

	public function query($sql, array $values = array()) 
	{
		$time = microtime(true);

		try 
		{
			if (!empty($values)) 
			{
				$Statement = $this->_mysqli()->prepare($sql);
				foreach ($values as $k => $v) 
				{
					$Statement->bindParam(':' . $k, $v);
				}
				$Statement->execute();
			} 
			else 
			{
				$Statement = $this->_mysqli()->query($sql);
			}
		} 
		catch (PDOException $e) 
		{
			$error = $e->getMessage();
		}

		$this->_queryLog[] = array(
			'query' => $sql,
			'affected_rows' => $Statement->rowCount(),
			'time_taken' => round(microtime(true) - $time, 3),
			'error' => isset($error) ? $error : null,
		);

		if (isset($e) || !$Statement instanceof PDOStatement) 
		{
			return null;
		}

		return $Statement;
	}

	public function queryLog() 
	{
		return $this->_queryLog;
	}

	protected function _mysqli() 
	{
		if (empty($this->_mysqli)) 
		{
			$this->_mysqli = ConnectionManager::getDataSource($this->useDbConfig);
		}
		return $this->_mysqli;
	}
}

class ConnectionManager {

/**
 * Holds an instance of the connection config
 *
 * @var array
 */
	public static $config = null;

/**
 * Indicates if the init code for this class has already been executed
 *
 * @var boolean
 */
	protected static $_init = false;

/**
 * Holds instances DataSource objects
 *
 * @var array
 */
	protected static $_dataSources = array();

/**
 * Loads connection configuration.
 *
 * @return void
 */
	protected static function _init()
	{
		if (Configure::check('DB_CONFIG')) 
		{
			self::$config = Configure::read('DB_CONFIG');
		}
		self::$_init = true;
	}

	public static function getDataSource($db) 
	{
		if (!self::$_init) 
		{
			self::_init();
		}
		if (!empty(self::$_dataSources[$db])) 
		{
			return self::$_dataSources[$db];
		}

		self::$_dataSources[$db] = new PDO(sprintf('mysql:host=%s;dbname=%s', self::$config['server'], $db), self::$config['username'], self::$config['password']);
		self::$_dataSources[$db]->useDbConfig = $db;
		self::$_dataSources[$db]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return self::$_dataSources[$db];
	}

}