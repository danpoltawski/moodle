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
 * Outcome Administration
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\controller\edit_controller;
use core_outcome\controller\import_controller;
use core_outcome\controller\kernel;
use core_outcome\controller\router;

/** @var $CFG stdClass */
require_once(dirname(__DIR__).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

$action = required_param('action', PARAM_ALPHAEXT);

admin_externalpage_setup('core_outcomes');

$router = new router();
$router->add_controller(new edit_controller());
$router->add_controller(new import_controller());

$kernel = new kernel($router);
$kernel->handle($action);