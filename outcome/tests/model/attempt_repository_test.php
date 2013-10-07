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
 * Outcome Attempt Model Repository Mapper Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\area_model;
use core_outcome\model\attempt_model;
use core_outcome\model\attempt_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_model_attempt_repository_test extends advanced_testcase {
    /**
     * @var attempt_model
     */
    protected $_attempt;

    public function setUp() {
        $this->resetAfterTest();

        $data = include(dirname(__DIR__).'/fixtures/base.php');
        $this->loadDataSet($this->createArrayDataSet($data));
        $this->_attempt = $this->_expected_attempt($data['outcome_attempts'][0]);
    }

    protected function _expected_attempt($data) {
        $model = new attempt_model();
        foreach ($data as $name => $value) {
            $model->$name = $value;
        }
        return $model;
    }

    public function test_find() {
        $repo  = new attempt_repository();
        $found = $repo->find(1);
        $this->assertEquals($this->_attempt, $found);
    }

    public function test_find_miss() {
        $repo = new attempt_repository();
        $this->assertFalse($repo->find(100000000000000));
    }

    public function test_find_by() {
        $repo  = new attempt_repository();
        $found = $repo->find_by(array('outcomeusedareaid' => '1'));
        $this->assertEquals(array('1' => $this->_attempt), $found);
    }

    public function test_find_by_itemid() {
        $repo  = new attempt_repository();
        $found = $repo->find_by_itemid(1, 1, array(1));
        $this->assertEquals(array('1' => $this->_attempt), $found);

        $found = $repo->find_by_itemid(1, 1);
        $this->assertEquals(array('1' => $this->_attempt), $found);
    }

    public function test_save() {
        global $DB;

        $model                    = new attempt_model();
        $model->outcomeusedareaid = '3';
        $model->userid            = '2';
        $model->percentgrade      = '50';
        $model->mingrade          = '0';
        $model->maxgrade          = '100';
        $model->rawgrade          = '50';

        $now = time();

        $repo = new attempt_repository();
        $repo->save($model);

        $this->assertNotNull($model->id);
        $this->assertGreaterThan(0, $model->id);
        $this->assertEquals($now, $model->timecreated, '', 2);
        $this->assertEquals($now, $model->timemodified, '', 2);
        $this->assertTrue($DB->record_exists('outcome_attempts', array('id' => $model->id)));
    }

    public function test_save_update() {
        $model = clone($this->_attempt);

        $repo = new attempt_repository();
        $repo->save($model);

        $this->assertEquals($this->_attempt->timecreated, $model->timecreated);
    }

    public function test_remove() {
        global $DB;

        $oldid = $this->_attempt->id;

        $repo = new attempt_repository();
        $repo->remove($this->_attempt);

        $this->assertNull($this->_attempt->id);
        $this->assertFalse($DB->record_exists('outcome_attempts', array('id' => $oldid)));
    }

    public function test_remove_by() {
        global $DB;

        $repo = new attempt_repository();
        $repo->remove_by(array('userid' => $this->_attempt->userid));

        $this->assertFalse($DB->record_exists('outcome_attempts', array('id' => $this->_attempt->id)));
    }

    /**
     * @expectedException coding_exception
     */
    public function test_remove_by_empty_conditions() {
        $repo = new attempt_repository();
        $repo->remove_by(array());
    }

    public function test_remove_by_cmid() {
        global $DB;

        $repo = new attempt_repository();
        $repo->remove_by_cmid(1);

        $this->assertFalse($DB->record_exists('outcome_attempts', array('id' => $this->_attempt->id)));
    }

    public function test_remove_by_cmid_with_excluded() {
        global $DB;

        $area = new area_model();
        $area->id = 1;
        $area->component = 'mod_forum';
        $area->area = 'mod';
        $area->itemid = 1;

        $repo = new attempt_repository();
        $repo->remove_by_cmid(1, array($area));

        $this->assertTrue($DB->record_exists('outcome_attempts', array('id' => $this->_attempt->id)));

        $area->id = 2;
        $area->itemid = 2;
        $repo->remove_by_cmid(1, array($area));

        $this->assertFalse($DB->record_exists('outcome_attempts', array('id' => $this->_attempt->id)));
    }
}
