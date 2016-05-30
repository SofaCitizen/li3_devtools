<?php
/**
 * li3_devtools : Development & Debugging Tools for the Lithium Framework (1.1)
 *
 * @copyright   Copyright 2016, Graeme Wheeler
 * @license     http://www.opensource.org/licenses/MIT The MIT License
 */

use li3_devtools\storage\Data;


// Start the timers
// Note: this is only acurate if li3_perf was started before all other libraries (and after lithium).
Data::start();

/**
 * This file hooks into the despatcher to grab timings and stuff
 */
require __DIR__ . '/bootstrap/despatcher.php';

/**
 * This file adds query information to the data class
 */
require __DIR__ . '/bootstrap/queries.php';

?>