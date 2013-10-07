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
 * Outcome Set Model Repository Mapper Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\filter_model;
use core_outcome\model\filter_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_model_filter_repository_test extends advanced_testcase {
    /**
     * @var filter_model
     */
    protected $_filter;

    public function setUp() {
        $this->resetAfterTest();

        $data = include(dirname(__DIR__).'/fixtures/base.php');
        $this->loadDataSet($this->createArrayDataSet($data));

        $this->_filter = new filter_model();
        $this->_filter->id = '1';
        $this->_filter->courseid = '2';
        $this->_filter->outcomesetid = '1';
        $this->_filter->add_filter('10', 'Math');
    }

    public function test_find() {
        $repo  = new filter_repository();
        $found = $repo->find(1);
        $this->assertEquals($this->_filter, $found);
    }

    public function test_find_miss() {
        $repo = new filter_repository();
        $this->assertFalse($repo->find(100000000000000));
    }

    public function test_find_by() {
        $repo  = new filter_repository();
        $found = $repo->find_by(array('outcomesetid' => '1'));
        $this->assertEquals(array('1' => $this->_filter), $found);
    }

    public function test_find_by_course() {
        $repo  = new filter_repository();
        $found = $repo->find_by_course(2);
        $this->assertEquals(array('1' => $this->_filter), $found);
    }

    public function test_sync() {
        global $DB;

        $model               = new filter_model();
        $model->courseid     = '2';
        $model->outcomesetid = '2';
        $model->add_filter('10', 'English');

        $repo = new filter_repository();
        $repo->sync(2, array($model));

        $this->assertGreaterThan(0, $model->id);
        $this->assertFalse($DB->record_exists('outcome_used_sets', array('id' => $this->_filter->id)));
    }

    public function test_sync_empty() {
        global $DB;

        $repo = new filter_repository();
        $repo->sync(2, array());

        $this->assertFalse($DB->record_exists('outcome_used_sets', array('id' => $this->_filter->id)));
    }

    /**
     * @expectedException coding_exception
     */
    public function test_sync_multi_course() {
        $model1               = new filter_model();
        $model1->courseid     = '2';
        $model1->outcomesetid = '2';
        $model1->add_filter('10', 'English');

        $model2               = new filter_model();
        $model2->courseid     = '1';
        $model2->outcomesetid = '2';
        $model2->add_filter('10', 'English');

        $repo = new filter_repository();
        $repo->sync(2, array($model1, $model2));
    }

    public function test_save() {
        global $DB;

        $model               = new filter_model();
        $model->courseid     = '2';
        $model->outcomesetid = '2';
        $model->add_filter('10', 'English');

        $repo = new filter_repository();
        $repo->save($model);

        $this->assertNotNull($model->id);
        $this->assertGreaterThan(0, $model->id);
        $this->assertTrue($DB->record_exists('outcome_used_sets', array('id' => $model->id)));
    }

    public function test_save_missing_id() {
        $id = $this->_filter->id;
        $this->_filter->id = null;

        $repo = new filter_repository();
        $repo->save($this->_filter);

        $this->assertEquals($id, $this->_filter->id, 'The filter should save to the same ID');
    }

    public function test_remove_with_id() {
        global $DB;

        $id = $this->_filter->id;

        $repo = new filter_repository();
        $repo->remove($this->_filter);

        $this->assertNull($this->_filter->id);
        $this->assertFalse($DB->record_exists('outcome_used_sets', array('id' => $id)));
    }

    public function test_remove_without_id() {
        global $DB;

        $id = $this->_filter->id;
        $this->_filter->id = null;

        $repo = new filter_repository();
        $repo->remove($this->_filter);

        $this->assertFalse($DB->record_exists('outcome_used_sets', array('id' => $id)));
    }

    public function test_remove_by_course() {
        global $DB;

        $repo = new filter_repository();
        $repo->remove_by_course(2);

        $this->assertFalse($DB->record_exists('outcome_used_sets', array('id' => $this->_filter->id)));
    }
}