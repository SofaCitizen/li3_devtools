<?php
/**
 * li3_roger : The RepOrt GenERator
 *
 * @copyright     Copyright 2016, Print Evolved (http://printevolved.co.uk/)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use li3_devtools\storage\Data;
use lithium\analysis\Logger;
use lithium\aop\Filters;
use lithium\data\Connections;

Filters::apply('lithium\action\Dispatcher', '_callable', function($params, $next) {
	Data::start('stages', 'filtering_queries');
	foreach (Connections::get() as $value) {
		Filters::apply(Connections::get($value), '_execute', function($params, $next) {
			// Run the query inside a timer
			Data::start('queries', ['sql' => $params['sql']]);
			$result = $next($params);
			$saved = Data::end('queries');

			if ($saved) {
				// Log the query in the logger so that we can see queries run before a redirect
				Logger::debug('QUERY: ' . $saved['sql'] . ' (' . number_format($saved['time'], 2) . 's)');
			}

			// Return result
			return $result;
		});
	};

	Data::end('stages', 'filtering_queries');
	return $next($params);
});

?>