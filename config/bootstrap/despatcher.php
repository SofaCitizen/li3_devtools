<?php
/**
 * li3_roger : The RepOrt GenERator
 *
 * @copyright     Copyright 2016, Print Evolved (http://printevolved.co.uk/)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use li3_devtools\storage\Data;
use lithium\aop\Filters;
use lithium\net\http\Media;

/*
 *	Start routing stage
 */
Filters::apply('lithium\action\Dispatcher', 'run', function($params, $next) {
	Data::start('stages', 'routing');

	return $next($params);
});

/*
 *	Set remaining stages
 */
Filters::apply('lithium\action\Dispatcher', '_callable', function($params, $next) {
	Data::start('stages', 'filtering_despatch');

	Data::end('stages', 'routing');
	Data::start('stages', 'callable');
	$controller = $next($params);
	Data::end('stages', 'callable');

	if (is_a($controller, '\lithium\action\Controller')) {
		Data::start('stages', 'content');
	}

	Data::end('stages', 'filtering_despatch');
	return $controller;
});

/*
 *	Inject a filter to end final timers and output the info via the named element
 */
Filters::apply('lithium\action\Dispatcher', '_callable', function($params, $next) {
	$controller = $next($params);

	if (is_a($controller, '\lithium\action\Controller')) {
		Filters::apply($controller, '__invoke', function($params, $next) use ($controller) {
			$response = $next($params);

			if ($response->type() === 'html') {
				// Get pre-configured view object (using any paths we configured for the application)
				$view = Media::view('default', []);

				// This is the last minute to stop any timers
				Data::end();

				// Set data to be passed
				$stages  = Data::sorted('stages');
				$queries = Data::get('queries');

				// Grab the rendered output from the element
				$output = $view->render(
					array('element' => 'devtools/output'),
					compact('stages', 'queries'),
					['library' => 'li3_devtools']
				);

				// Insert the rendered content at the very end of the page
				$response->body = str_replace('</body>', $output . '</body>', $response->body);
			}

			return $response;
		});
	}

	return $controller;
});

/*
 *	Add filter to save our data on redirect to allow a redirecting call to return all relevant data
 */
Filters::apply('lithium\action\Dispatcher', '_callable', function($params, $next) {
	$controller = $next($params);
	if (is_a($controller, '\lithium\action\Controller')) {
		Filters::apply($controller, 'redirect', function($params, $next) {
			Data::save();
			return $next($params);
		});
	}

	return $controller;
});

?>