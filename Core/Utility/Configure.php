<?php
require_once CORE . 'Utility' . DS . 'Hash.php';

class Configure {

/**
 * Array of values currently stored in Configure.
 *
 * @var array
 */
	protected static $_values = array(
		'debug' => 0
	);

/**
 * Returns true if given variable is set in Configure.
 *
 * @param string $var Variable name to check for
 * @return boolean True if variable is there
 */
	public static function check($var = null) {
		if (empty($var)) {
			return false;
		}
		return Hash::get(self::$_values, $var) !== null;
	}
	
/**
 * Used to read information stored in Configure. It's not
 * possible to store `null` values in Configure.
 *
 * Usage:
 * {{{
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 * }}}
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::read
 * @param string $var Variable to obtain. Use '.' to access array elements.
 * @return mixed value stored in configure, or null.
 */
	public static function read($var = null) {
		if ($var === null) {
			return self::$_values;
		}
		return Hash::get(self::$_values, $var);
	}

/**
 * Used to store a dynamic variable in Configure.
 *
 * Usage:
 * {{{
 * Configure::write('One.key1', 'value of the Configure::One[key1]');
 * Configure::write(array('One.key1' => 'value of the Configure::One[key1]'));
 * Configure::write('One', array(
 *     'key1' => 'value of the Configure::One[key1]',
 *     'key2' => 'value of the Configure::One[key2]'
 * );
 *
 * Configure::write(array(
 *     'One.key1' => 'value of the Configure::One[key1]',
 *     'One.key2' => 'value of the Configure::One[key2]'
 * ));
 * }}}
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::write
 * @param string|array $config The key to write, can be a dot notation value.
 * Alternatively can be an array containing key(s) and value(s).
 * @param mixed $value Value to set for var
 * @return boolean True if write was successful
 */
	public static function write($config, $value = null) {
		if (!is_array($config)) {
			$config = array($config => $value);
		}

		foreach ($config as $name => $value) {
			self::$_values = Hash::insert(self::$_values, $name, $value);
		}

		if (isset($config['debug']) && function_exists('ini_set')) {
			if (self::$_values['debug']) {
				ini_set('display_errors', 1);
			} else {
				ini_set('display_errors', 0);
			}
		}
		return true;
	}

/**
 * Used to delete a variable from Configure.
 *
 * Usage:
 * {{{
 * Configure::delete('Name'); will delete the entire Configure::Name
 * Configure::delete('Name.key'); will delete only the Configure::Name[key]
 * }}}
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::delete
 * @param string $var the var to be deleted
 * @return void
 */
	public static function delete($var = null) {
		self::$_values = Hash::remove(self::$_values, $var);
	}
}