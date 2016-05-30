<?php
/**
 * li3_roger : The RepOrt GenERator
 *
 * @copyright     Copyright 2016, Print Evolved (http://printevolved.co.uk/)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_devtools\storage;

use lithium\core\Libraries;

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
	 * Loads data from the temporary file and removes it
	 */
	static public function load() {
		$path = Libraries::get(true, 'resources') . '/tmp/devtools-tmp.json';

		if (file_exists($path) && ($string = file_get_contents($path))) {
			if ($decoded = json_decode($string, true)) {
				// We have valid data so merge for each of our known types
				foreach (array_keys(static::$data) as $type) {
					if (isset($decoded[$type])) {
						static::$data[$type] += $decoded[$type];
					}
				}
			}
			unlink($path);
		}
	}

	/**
	 * Saves data in a temporary file
	 * This is designed to allow logging to persist between redirects
	 *
	 * @return boolean Returns `true` if the data was saved.
	 */
	static public function save() {
		$path = Libraries::get(true, 'resources') . '/tmp/devtools-tmp.json';
		$encoded = json_encode(static::$data);
		return file_put_contents($path, $encoded);
	}

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
		// Passing no parameters assumes we are ending the overall timer
		// This will also run the load method to attempt to read in data from a previous execution if it was interrupted
		if (!$type) {
			static::load();
			$type = 'stages';
			$data = array('key' => 'overall');
		}

		// Ensure we have an array of data
		if (!is_array($data)) {
			$data = array('key' => $data);
		}

		// Merge passed data with defaults
		$defaults = ['start' => microtime(true), 'key' => null];
		$data += $defaults;

		// Strip the key from the data
		$key = $data['key'];
		unset($data['key']);

		// Save data to the array
		if (!$key) {
			// We do not have a key so save it to the pending array
			static::$data['_pending'][$type] = $data;
		} else {
			// We do have a key so merge with any existing data and save it
			if (isset(static::$data[$type][$key])) {
				$data += static::$data[$type][$key];
			}
			static::$data[$type][$key] = $data;
		}
	}

	/**
	 * Completes a timed entry and moves it from pending to the correct array
	 *
	 * @param string $type The data type to use
	 * @return mixed Returns the data that was added or false on failure.
	 */
	static public function end($type = null, $data = array()) {
		// Passing no parameters assumes we are ending the overall timer
		if (!$type) {
			$type = 'stages';
			$data = array('key' => 'overall');
			self::_totals();
		}

		// Ensure we have an array of data
		if (!is_array($data)) {
			$data = array('key' => $data);
		}

		// Merge passed data with defaults
		$defaults = ['end' => microtime(true), 'key' => null];
		$data += $defaults;

		// Strip the key from the data
		$key = $data['key'];
		unset($data['key']);

		// Update data in the array
		if (!$key) {
			if (!isset(static::$data['_pending'][$type])) {
				return false;
			}

			// Merge data and remove it from pending
			$data += static::$data['_pending'][$type];
			unset(static::$data['_pending'][$type]);
		} else {
			// We do have a key so merge with any existing data and save it
			if (isset(static::$data[$type][$key])) {
				$data += static::$data[$type][$key];
			}
		}

		// Set the final time - including adding to any existing time
		$data['time'] = (isset($data['time'])? $data['time']:0) + $data['end'] - $data['start'];

		// Append data in a suitably manner
		$ok = ($key)? static::append($type, [$key => $data]) : static::append($type, [$data]);

		// Return data if we added it successfully
		return (!$ok)?:$data;
	}

	/**
	 * Calculate the total time for all queries
	 */
	static protected function _totals() {
		$data['time'] = 0;
		foreach (static::$data['queries'] as $value) {
			$data['time'] += $value['time'];
 		}

 		static::append('stages', ['total_queries' => $data]);
	}
}
?>