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
 * Outcome AJAX handler
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\controller\kernel;
use core_outcome\controller\mapping_ajax_controller;
use core_outcome\controller\outcome_ajax_controller;
use core_outcome\controller\report_ajax_controller;
use core_outcome\controller\router;

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

/** @var $CFG stdClass */
require_once(dirname(__DIR__).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

$systemcontext = context_system::instance();

$action    = required_param('action', PARAM_ALPHAEXT);
$contextid = optional_param('contextid', $systemcontext->id, PARAM_INT);

list($context, $course, $cm) = get_context_info_array($contextid);

require_login($course, false, $cm, false, true);

/** @var $PAGE moodle_page */
$PAGE->set_context($context);
$PAGE->set_url('/outcome/ajax.php', array('action' => $action, 'contextid' => $context->id));

$router = new router();
$router->add_controller(new mapping_ajax_controller());
$router->add_controller(new outcome_ajax_controller());
$router->add_controller(new report_ajax_controller());

$kernel = new kernel($router);
$kernel->handle($action);