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
 * Outcome Mark Model History Repository Mapper Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\mark_history_model;
use core_outcome\model\mark_history_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * NOTE: Most of this class is actually tested by outcome_model_mark_repository_test
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_model_mark_history_repository_test extends advanced_testcase {
    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_remove_old() {
        global $DB;

        $record = (object) array(
            'action'        => mark_history_model::ACTION_CREATE,
            'outcomemarkid' => 1,
            'courseid'      => 2,
            'outcomeid'     => 1,
            'userid'        => 2,
            'graderid'      => 2,
            'result'        => 1,
            'timecreated'   => 1,
        );
        $DB->insert_record('outcome_marks_history', $record);

        $record->timecreated = 2;
        $DB->insert_record('outcome_marks_history', $record);

        $record->timecreated = 3;
        $id = $DB->insert_record('outcome_marks_history', $record);

        $repo = new mark_history_repository();
        $repo->remove_old(3);

        $this->assertEquals(1, $DB->count_records('outcome_marks_history'));
        $this->assertTrue($DB->record_exists('outcome_marks_history', array('id' => $id)));
    }
}