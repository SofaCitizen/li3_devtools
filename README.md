# Development & Debugging Tools for the Lithium Framework (1.1)

This library is a development and debugging aid for use when building applications with the Lithium Framework

Initially it will simply log database queries and the time taken for certain stages of a lithium app and then display these at the foot of the page (ala CakePHP debugging). There are existing libraries that do this (li3_perf & li3_debug) but those have not been updated in years and so do not work with the latest version of Lithium.


## Current Functionality

- Display all database queries executed during creation of the page
- Display timing info to help identify areas that should be optimised


## Integration

```php
<?php

// config/bootstrap/libraries.php: (add this as the first library to ensure the timings are as acurate as possible)

Libraries::add('li3_devtools');

?>
```

## Logging

Database queries are also logged using the in-built logger. This allows you to see the queries that have been run before a redirect etc. To enable this, set a Logging configuration that recognises the 'debug' priority.

```php
<?php

use lithium\analysis\Logger;

Logger::config(array(
	'default' => array(
		'adapter' => 'File',
		'priority' => array('emergency', 'alert', 'critical', 'error', 'debug')
)));

?>
```