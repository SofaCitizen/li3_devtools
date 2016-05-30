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
		static::end();
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
	 * @return mixed The value stored at the specified type, or false if not set.
	 */
	static public function get($type = null) {
		return (isset(static::$data[$type])) ? static::$data[$type]:false;
	}

	/**
	 * Gets sorted data.
	 *
	 * @param string $type The data type to use
	 * @return mixed The value stored at the specified type, or false if not set.
	 */
	static public function sorted($type = null) {
		$data = (isset(static::$data[$type])) ? array_values(static::$data[$type]):false;

		if ($data) {
			usort($data, function($a, $b) {
			    return ($a['time'] > $b['time']) ? -1 : 1;
			});
		}

		return $data;
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
			$data = array('name' => 'overall');
		}

		// Ensure we have an array of data
		if (!is_array($data)) {
			$data = array('name' => $data);
		}

		// Merge passed data with defaults
		$defaults = ['start' => microtime(true), 'name' => 0];
		$data += $defaults;

		// We do have a key so merge with any existing data and save it to the pending array
		if (isset(static::$data['_pending'][$type][$data['name']])) {
			$data += static::$data['_pending'][$type][$data['name']];
		}
		static::$data['_pending'][$type][$data['name']] = $data;
	}

	/**
	 * Completes a timed entry and moves it from pending to the correct array
	 *
	 * @param string $type The data type to use
	 * @return mixed Returns the data that was added, true if we closed all timers or false on failure.
	 */
	static public function end($type = null, $data = array()) {
		// Passing no parameters assumes we are ending all pending timers
		if (!$type) {
			// Loop through and end all timers
			foreach (static::$data['_pending'] as $type => $value) {
				if (!empty($value)) {
					foreach ($value as $k => $v) {
						static::end($type, $v);
					}
				}
				unset(static::$data['_pending'][$type]);
			}

			// Calculate totals & sort
			self::_totals();

			return true;
		}

		// Ensure we have an array of data
		if (!is_array($data)) {
			$data = array('name' => $data);
		}

		// Merge passed data with defaults
		$defaults = ['end' => microtime(true), 'name' => 0, 'count' => 1];
		$data += $defaults;

		// We should have existing data so merge it in and remove it
		if (isset(static::$data['_pending'][$type][$data['name']])) {
			$data += static::$data['_pending'][$type][$data['name']];
			unset(static::$data['_pending'][$type][$data['name']]);
		}

		// Set the final time
		$data['time'] = $data['end'] - $data['start'];

		// If we have a key then we may have prior data to merge/add in
		if ($data['name'] && isset(static::$data[$type][$data['name']])) {
			$data['time']  += static::$data[$type][$data['name']]['time'];
			$data['count'] += static::$data[$type][$data['name']]['count'];
			$data += static::$data[$type][$data['name']];
		}

		// Append data in a suitable manner
		$ok = ($data['name'])? static::append($type, [$data['name'] => $data]) : static::append($type, [$data]);

		// Return data if we added it successfully
		return (!$ok)?:$data;
	}

	/**
	 * Calculate all totals and percentages
	 */
	static protected function _totals() {
		$total = [
			'name'  => 'total_queries',
			'time'  => 0,
			'count' => 0,
		];

		// Loop queries and calculate count & time
		foreach (static::$data['queries'] as $value) {
			$total['count']++;
			$total['time'] += $value['time'];
 		}

 		// Save
 		static::append('stages', [$total['name'] => $total]);

 		// Now use new total to set percentages on each query
		foreach (static::$data['queries'] as &$item) {
			$item['percentage'] = ($item['time'] / $total['time']) * 100;
 		}

 		// Do the same for stages
 		$total = static::$data['stages']['overall'];
		foreach (static::$data['stages'] as &$item) {
			$item['percentage'] = ($item['time'] / $total['time']) * 100;
 		}
	}
}
?>