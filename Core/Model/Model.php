<?php
class Model 
{

	public $id = null;

	public $primaryKey = 'id';

	public $useTable = null;

	public $useDbConfig = 'default';

	protected $_queryLog = array();

/**
 * constructor
 *
 * @param array $configuration
 *
 * @return Model
 */
	public function __construct(array $config = array()) 
	{
		if (defined('EMON_TEST_ENV') && EMON_TEST_ENV) 
		{
			$this->useDbConfig = 'test';
		}
	}

/**
 * reset the model instance
 *
 * @return void
 */
	public function create() 
	{
		$this->id = null;
	}

/**
 * get the last insert id
 *
 * @return null|integer
 */
	public function lastInsertId() 
	{
		return $this->id;
	}

/**
 * Check if the give record exists
 *
 * @param integer $id the record id to check
 *
 * @return boolean
 */
	public function exists($id = null) 
	{
		$sql = sprintf('SELECT COUNT(*) as `count` FROM `%s` WHERE `%s` = :id', $this->useTable, $this->primaryKey);
		return (int)$this->field($sql, array(
			'id' => (int)($id ?: $this->id),
		)) === 1;
	}

/**
 * Fetch the value of a single field
 *
 * Returns the first value of a result
 *
 * @param string $field the field name to fetch
 * @param string $query the query to select the field
 *
 * @return string|null
 */
	public function field($query, $values = array()) 
	{
		$Statement = $this->_query($query, $values);
		$Statement->execute();
		if (!$Statement instanceof PDOStatement || !$Statement->rowCount()) 
		{
			return null;
		}
		
		$return = $Statement->fetchColumn();
		$Statement->closeCursor();
		return $return;
	}

/**
 * Fetch a row / array of data
 *
 * @param string $query the query to select the field
 * @param array $values the values to be used in the query
 *
 * @return string|null
 */
	public function row($query, array $values = array()) 
	{
		return (array)current($this->rows($query, $values));
	}

/**
 * Fetch a row / array of data
 *
 * @param string $query the query to select the field
 * @param array $values the values to be used in the query
 *
 * @return string|null
 */
	public function rows($query, array $values = array()) 
	{
		$Statement = $this->_query($query, $values);
		if (!$Statement->rowCount()) 
		{
			return array();
		}
		$rows = $Statement->fetchAll(PDO::FETCH_ASSOC);
		$Statement->closeCursor();
		return $rows;
	}

/**
 * Save data to the db
 *
 * Records with primary key set will do an update, records with no primary key do an insert
 *
 * Returns the newly created / updated data set
 *
 * @param array $data the data to be saved
 *
 * @return array
 */
	public function save(array $data) 
	{
		if (!empty($data[$this->primaryKey]) && $this->exists($data[$this->primaryKey]))
		{
			$Statement = $this->_update($data);
		}
		else 
		{
			$Statement = $this->_insert($data);
		}

		$this->id = !empty($data['id']) ? $data['id'] : $this->_pdo()->lastInsertId();
		$Statement->closeCursor();

		return $this->_findById();

	}

/**
 * Save multiple records
 * 
 * Wrapper for save to do multiple records
 *
 * @param array $data the data to be saved
 *
 * @return array
 */
	public function saveAll(array $data) 
	{
		$saved = array();
		foreach ($data as $d) 
		{
			$this->create();
			$row = $this->save($d);
			$saved[$this->id] = (bool)$row;
		}

		return $saved;
	}

/**
 * Update a record
 *
 * @param array $data the data to update
 *
 * @return PDOStatement
 */
	protected function _update(array $data) 
	{
		$set = array();
		foreach ($data as $key => $value) 
		{
			if ($key == $this->primaryKey) 
			{
				continue;
			}
			$set[] = sprintf('`%s` = :%s', $key, $key);
		}

		$sql = sprintf('UPDATE `%s` SET %s WHERE `%s` = :id', $this->useTable, implode(', ', $set), $this->primaryKey);
		return $this->_query($sql, $data);
	}

/**
 * insert a record
 *
 * @param array $data the data to inser
 *
 * @return PDOStatement
 */
	protected function _insert(array $data)
	{
		$fields = array_keys($data);
		$values = $fields;
		foreach ($values as &$value) 
		{
			$value = ':' . $value;
		}
		$sql = 'INSERT INTO `%s` (`%s`) VALUES (%s)';
		return $this->_query(sprintf($sql, $this->useTable, implode('`, `', $fields), implode(', ', $values)), $data);
	}

/**
 * find a record by the primary key
 *
 * @param integer $id the id to search for
 *
 * @return array
 */
	protected function _findById($id = null) 
	{
		if ($id === null) 
		{
			$id = $this->id;
		}

		return $this->row(sprintf('SELECT * FROM `%s` WHERE `%s` = :id', $this->useTable, $this->primaryKey), array(
			'id' => $id,
		));
	}

/**
 * Wrapper method for doing queries on the database
 *
 * Returns an associate array of data for the selected query
 *
 * @param string $sql the query to run
 * @param array $value parameters to bind to the query
 *
 * @return array
 */
	public function query($sql, array $values) 
	{
		$Statement = self::_query($sql, $values);
		$results = $Statement->fetchAll(PDO::FETCH_ASSOC);
		$Statement->closeCursor();

		return $results;
	}

/**
 * Run a query
 *
 * Optional arguments passed as array are used in prepared statements (prefered method, more secure)
 *
 * Generally this method should not be used directly, instead use field(), row(), rows()
 *
 * @param string $sql the query being run
 * @param array $values the values for the prepared statement
 *
 * @return PDOStatement
 */
	protected function _query($sql, array $values = array()) 
	{
		$time = microtime(true);
		if (!empty($values)) 
		{
			$Statement = $this->_pdo()->prepare($sql);
			$bind = array();
			foreach ($values as $k => $v) 
			{
				$bind[':' . $k] = $v;
			}
			$Statement->execute($bind);
		} 
		else 
		{
			$Statement = $this->_pdo()->query($sql);
		}

		$this->_queryLog[] = array(
			'query' => $sql,
			'affected_rows' => $Statement->rowCount(),
			'time_taken' => round(microtime(true) - $time, 3),
			'error' => isset($error) ? $error : null,
		);

		return $Statement;
	}

/**
 * Get the query log
 *
 * Returns a log of queries that have been run, with data such as affected rows, time taken etc.
 *
 * @return array
 */
	public function queryLog() 
	{
		return $this->_queryLog;
	}

/**
 * Get the PDO instance for doing queries
 *
 * @return PDO
 */
	protected function _pdo() 
	{
		if (empty($this->_pdo)) 
		{
			$this->_pdo = ConnectionManager::getDataSource($this->useDbConfig);
		}
		return $this->_pdo;
	}

}