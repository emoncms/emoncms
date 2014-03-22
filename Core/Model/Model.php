<?php
class Model 
{

	public $id = null;

	public $primaryKey = 'id';

	public $useTable = null;

	public $useDbConfig = null;

	protected $_queryLog = array();

	public function __construct($config) 
	{
		$this->useDbConfig = Configure::read('DB_CONFIG.database');
	}

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
		if ($id === null) 
		{
			$id = $this->id;
		}

		$sql = sprintf('SELECT COUNT(*) as `count` FROM `%s` WHERE `%s` = :id', $this->useTable, $this->primaryKey);
		return $this->field('count', $sql, array(
			'id' => $id,
		)) === 1;
	}

/**
 * Fetch the value of a single field
 *
 * @param string $field the field name to fetch
 * @param string $query the query to select the field
 *
 * @return string|null
 */
	public function field($field, $query, $values = array()) 
	{
		$Statement = $this->query($query, $values);
		if (!$Statement instanceof PDOStatement || !$Statement->rowCount()) 
		{
			return null;
		}
		
		return $Statement->fetchColumn();
	}	

/**
 * Fetch a row / array of data
 *
 * @param string $query the query to select the field
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

/**
 * reset the model instance
 *
 * @return void
 */
	public function create() 
	{
		$this->id = null;
	}

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

		$this->id = $this->_pdo()->lastInsertId();

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

	protected function _update(array $data) 
	{
		$set = array();
		foreach ($data as $key => $value) 
		{
			$set[] = sprintf('`%s` = :%s', $key, $key);
		}

		$sql = sprintf('UPDATE `%s` SET %s WHERE :primaryKey = :id', $this->useTable, implode(', ', $set));
		return $this->query($sql, $data);
	}

	protected function _insert(array $data)
	{
		$fields = array_keys($data);
		$values = $fields;
		foreach ($values as &$value) 
		{
			$value = ':' . $value;
		}
		$sql = 'INSERT INTO `%s` (`%s`) VALUES (%s)';
		return $this->query(sprintf($sql, $this->useTable, implode('`, `', $fields), implode(', ', $values)), $data);
	}

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
	public function query($sql, array $values = array()) 
	{
		$time = microtime(true);
		try 
		{
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
		} 
		catch (PDOException $e) 
		{
			$error = $e->getMessage();
			pr($error);
		}

		$Statement->closeCursor();

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

	protected function _pdo() 
	{
		if (empty($this->_pdo)) 
		{
			$this->_pdo = ConnectionManager::getDataSource($this->useDbConfig);
		}
		return $this->_pdo;
	}
}