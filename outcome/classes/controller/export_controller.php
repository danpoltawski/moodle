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
 * Outcome Set Export Controller
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\controller;

use core_outcome\service\export_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles actions regarding exporting of outcome sets.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_controller extends controller_abstract {
    /**
     * @var export_helper
     */
    public $exporthelper;

    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     */
    public function require_capability($action) {
        global $PAGE;

        require_capability('moodle/outcome:export', $PAGE->context);
    }

    public function init($action) {
        parent::init($action);
        $this->exporthelper = new export_helper();
    }

    /**
     * Export an outcome set
     */
    public function outcomeset_export_action() {
        $outcomesetid = required_param('outcomesetid', PARAM_INT);
        $component    = optional_param('component', 'outcomeexport_general', PARAM_COMPONENT);

        list($path, $filename) = $this->exporthelper->export_outcome_set_by_id($component, $outcomesetid);
        $this->exporthelper->send_export_file($path, $filename);
    }
}