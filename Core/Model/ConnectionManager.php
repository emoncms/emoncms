<?php

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

		if (empty(self::$config[$db])) {
			throw new Exception('Invalid connection selected');
		}

		self::$_dataSources[$db] = new PDO(sprintf('mysql:host=%s;dbname=%s', self::$config[$db]['server'], self::$config[$db]['database']), self::$config[$db]['username'], self::$config[$db]['password']);
		self::$_dataSources[$db]->useDbConfig = $db;
		self::$_dataSources[$db]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return self::$_dataSources[$db];
	}

}