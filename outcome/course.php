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
 * Outcome Course Administration
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\controller\kernel;
use core_outcome\controller\report_controller;
use core_outcome\controller\router;

/** @var $CFG stdClass */
require_once(dirname(__DIR__).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

$action    = optional_param('action', 'default', PARAM_ALPHAEXT);
$contextid = required_param('contextid', PARAM_INT);

list($context, $course, $cm) = get_context_info_array($contextid);

require_login($course, false, $cm);

// Force navigation to focus on outcome link.
navigation_node::override_active_url(new moodle_url('/outcome/course.php', array('contextid' => $context->id)));

/** @var $PAGE moodle_page */
$PAGE->set_context($context);
$PAGE->set_url('/outcome/course.php', array('action' => $action, 'contextid' => $context->id));
$PAGE->set_pagelayout('report');

$router = new router();
$router->add_controller(new report_controller());

$kernel = new kernel($router);
$kernel->handle($action);