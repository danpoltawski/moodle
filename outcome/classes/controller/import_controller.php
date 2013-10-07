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
 * Outcome Set Import Controller
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\controller;

use core_outcome\form\import_form;
use core_outcome\model\outcome_set_model;
use core_outcome\service\import_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles actions regarding importing of outcome sets.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_controller extends controller_abstract {
    /**
     * @var import_helper
     */
    public $importhelper;

    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     */
    public function require_capability($action) {
        global $PAGE;

        require_capability('moodle/outcome:import', $PAGE->context);
    }

    public function init($action) {
        parent::init($action);
        $this->importhelper = new import_helper();
    }

    /**
     * Import an outcome set
     *
     * @throws \moodle_exception
     */
    public function outcomeset_import_action() {
        global $PAGE;

        $PAGE->set_title(get_string('importoutcomeset', 'outcome'));
        $PAGE->navbar->add(get_string('importoutcomeset', 'outcome'));

        $returnurl = $this->new_url(array('action' => 'outcomeset'));
        $mform     = new import_form($this->new_url(array('action' => 'outcomeset_import')));

        if ($mform->is_cancelled()) {
            redirect($returnurl);
        } else if ($data = $mform->get_data()) {
            $file = $this->importhelper->save_temp_file($mform, 'importfile');

            if ($file === false) {
                throw new \moodle_exception('fileuploadfailed', 'outcome');
            }
            $result = $this->importhelper->import_outcome_set($data->component, $file);
            if ($result instanceof outcome_set_model) {
                $this->flashmessages->good('importcomplete', format_string($result->name));
            } else {
                $this->flashmessages->bad('nothingimported');
            }
            redirect($returnurl);
        }
        $mform->display();
    }
}