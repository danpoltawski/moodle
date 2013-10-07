<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Download endpoint
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\controller\export_controller;
use core_outcome\controller\kernel;
use core_outcome\controller\router;

ob_start();
define('NO_DEBUG_DISPLAY', true);
define('NO_OUTPUT_BUFFERING', true);
require_once(dirname(__DIR__).'/config.php');

$action    = required_param('action', PARAM_ALPHAEXT);
$contextid = optional_param('contextid', 0, PARAM_INT);

if (empty($contextid)) {
    require_login(SITEID, false, null, false, true);
} else {
    list($context, $course, $cm) = get_context_info_array($contextid);
    require_login($course, false, $cm, false, true);
}
while (ob_get_level() > 0) {
    ob_end_clean();
}
$router = new router();
$router->add_controller(new export_controller());

$kernel   = new kernel($router);
$callback = $kernel->resolve_controller_callback($action);

// Manually call the callback - this prevents any mucking with output.
call_user_func($callback);