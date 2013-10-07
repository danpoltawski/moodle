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
 * Outcome Mark Model Repository Mapper Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\mark_history_model;
use core_outcome\model\mark_model;
use core_outcome\model\mark_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_model_mark_repository_test extends advanced_testcase {
    /**
     * @var mark_model
     */
    protected $_model;

    public function setUp() {
        $this->resetAfterTest();

        $data = include(dirname(__DIR__).'/fixtures/mark.php');
        $this->loadDataSet($this->createArrayDataSet($data));
        $this->_model = $this->_expected_model($data['outcome_marks'][0]);
    }

    protected function _expected_model($data) {
        $model = new mark_model();
        foreach ($data as $name => $value) {
            $model->$name = $value;
        }
        return $model;
    }

    public function test_find() {
        $repo  = new mark_repository();
        $found = $repo->find(1);
        $this->assertEquals($this->_model, $found);
    }

    public function test_find_miss() {
        $repo = new mark_repository();
        $this->assertFalse($repo->find(100000000000000));
    }

    public function test_find_by() {
        $repo  = new mark_repository();
        $found = $repo->find_by(array('courseid' => '2'));
        $this->assertEquals(array('1' => $this->_model), $found);
    }

    public function test_find_by_ids() {
        $repo  = new mark_repository();
        $found = $repo->find_by_ids(array('1'));
        $this->assertEquals(array('1' => $this->_model), $found);
    }

    public function test_has_ever_been_earned_by_model() {
        $repo = new mark_repository();
        $this->assertTrue($repo->has_ever_been_earned($this->_model));
    }

    public function test_has_ever_been_earned_in_diff_course() {
        $this->_model->result = mark_model::NOT_EARNED;
        $repo = new mark_repository();
        $this->assertTrue($repo->has_ever_been_earned($this->_model));
    }

    public function test_has_ever_been_earned_in_history() {
        $this->_model->outcomeid = 5;
        $this->_model->result    = mark_model::NOT_EARNED;

        $repo = new mark_repository();
        $this->assertTrue($repo->has_ever_been_earned($this->_model));
    }

    public function test_has_ever_been_earned_not_in_history() {
        $this->_model->outcomeid = 6;
        $this->_model->result    = mark_model::NOT_EARNED;

        $repo = new mark_repository();
        $this->assertFalse($repo->has_ever_been_earned($this->_model));
    }

    public function test_save() {
        global $DB;

        $model               = clone($this->_model);
        $model->id           = null;
        $model->userid       = '3';
        $model->timecreated  = null;
        $model->timemodified = null;

        $now = time();

        $repo = new mark_repository();
        $repo->save($model);

        $this->assertNotNull($model->id);
        $this->assertGreaterThan(0, $model->id);
        $this->assertEquals($now, $model->timecreated, '', 2);
        $this->assertEquals($now, $model->timemodified, '', 2);
        $this->assertTrue($DB->record_exists('outcome_marks', array('id' => $model->id)));
        $this->assertTrue($DB->record_exists('outcome_marks_history', array(
            'outcomemarkid' => $model->id,
            'action' => mark_history_model::ACTION_CREATE
        )));
    }

    public function test_save_update() {
        global $DB;

        $now = time();
        $timecreated = $this->_model->timecreated;

        $repo = new mark_repository();
        $repo->save($this->_model);

        $this->assertEquals($timecreated, $this->_model->timecreated, '', 2);
        $this->assertEquals($now, $this->_model->timemodified, '', 2);
        $this->assertTrue($DB->record_exists('outcome_marks_history', array(
            'outcomemarkid' => $this->_model->id,
            'action'        => mark_history_model::ACTION_UPDATE
        )));
    }

    public function test_remove() {
        global $DB;

        $id = $this->_model->id;

        $repo = new mark_repository();
        $repo->remove($this->_model);

        $this->assertNull($this->_model->id);
        $this->assertFalse($DB->record_exists('outcome_marks', array('id' => $id)));
        $this->assertTrue($DB->record_exists('outcome_marks_history', array(
            'outcomemarkid' => $id,
            'action'        => mark_history_model::ACTION_DELETE
        )));
    }

    public function test_remove_by_courseid() {
        global $DB;

        $repo = new mark_repository();
        $repo->remove_by_courseid(2);

        $this->assertFalse($DB->record_exists('outcome_marks', array('id' => $this->_model->id)));
        $this->assertTrue($DB->record_exists('outcome_marks_history', array(
            'outcomemarkid' => $this->_model->id,
            'action'        => mark_history_model::ACTION_DELETE
        )));
    }
}