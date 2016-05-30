<?php
/**
 * li3_roger : The RepOrt GenERator
 *
 * @copyright     Copyright 2016, Print Evolved (http://printevolved.co.uk/)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_devtools\storage;

/**
 * A very simple static class to help pass around data between the various
 * filters that are running so that the output can display all the data that
 * was collected along the way.
 * Based on li3_perf/extensions/util/Data.php
*/
class Data extends \lithium\core\StaticObject {
	static $data = array(
		'stages'    => array(),
		'queries'   => array(),
		'_pending'  => array(),
	);

	/**
	 * Sets data.
	 *
	 * @param string $type The data type to use
	 * @param mixed $value The value to store
	 * @return boolean Returns `true` if the data was stored.
	 */
	static public function set($type = null, $value = null) {
		if(!empty($type)) {
			static::$data[$type] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Appends data.
	 *
	 * @param string $type The data type to use
	 * @param mixed $value The value to store
	 * @return boolean Returns `true` if the data was stored.
	 */
	static public function append($type = null, $value = null) {
		if(!empty($type)) {
			static::$data[$type] = array_merge(static::$data[$type], $value);
			return true;
		}
		return false;
	}

	/**
	 * Gets data.
	 *
	 * @param string $type The data type to use
	 * @return mixed The value stored at the specified key, or false if not set.
	 */
	static public function get($type = null) {
		return (isset(static::$data[$type])) ? static::$data[$type]:false;
	}

	/**
	 * Starts a timed entry and stores it in a pending array
	 *
	 * @param string $type The data type to use
	 */
	static public function start($type = null, $data = array()) {
		// Ensure we have an array of data and merge with defaults
		if (!is_array($data)) {
			$data = array('key' => $data);
		}
		$defaults = ['start' => microtime(true), 'type' => $type, 'key' => 'current'];
		$data += $defaults;

		// Strip type and key and use them to insert into the pending array
		$type = $data['type'];
		$key = $data['key'];
		unset($data['type'], $data['key']);

		static::$data['_pending'][$type][$key] = $data;
	}

	/**
	 * Completes a timed entry and moves it from pending to the correct array
	 *
	 * @param string $type The data type to use
	 * @return mixed Returns the data that was added or false on failure.
	 */
	static public function end($type = null, $data = array()) {
		// Ensure we have an array of data and merge with defaults
		if (!is_array($data)) {
			$data = array('key' => $data);
		}
		$defaults = ['end' => microtime(true), 'type' => $type, 'key' => 'current'];
		$data += $defaults;

		// Strip type and key from data
		$type = $data['type'];
		$key = $data['key'];
		unset($data['type'], $data['key']);


		// Read data in from the pending array (if we can) and remove it
		if (!isset(static::$data['_pending'][$type][$key])) {
			return false;
		}
		$data += static::$data['_pending'][$type][$key];
		unset(static::$data['_pending'][$type][$key]);

		// Calculate the time difference
		$data['time'] = $data['end'] - $data['start'];

		if ($key == 'current') {
			$ok = static::append($type, [$data]);
		} else {
			$ok = static::append($type, [$key => $data]);
		}

		// Return data if we added it successfully
		return (!$ok)?:$data;
	}

}
?>