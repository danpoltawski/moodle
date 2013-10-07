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

use core_outcome\model\area_model;
use core_outcome\model\outcome_set_model;
use core_outcome\model\outcome_set_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_model_outcome_set_repository_test extends advanced_testcase {
    /**
     * @var outcome_set_model
     */
    protected $_outcome_set;

    public function setUp() {
        $this->resetAfterTest();

        $data = include(dirname(__DIR__).'/fixtures/base.php');
        $this->loadDataSet($this->createArrayDataSet($data));
        $this->_outcome_set = $this->_expected_outcome_set($data['outcome_sets'][0]);
    }

    protected function _expected_outcome_set($data) {
        $model = new outcome_set_model();

        foreach ($data as $name => $value) {
            $model->$name = $value;
        }
        // Stuff coming from the db are always strings.
        foreach ($model as $name => $value) {
            if (is_numeric($value)) {
                $model->$name = (string) $value;
            }
        }
        return $model;
    }

    public function test_find() {
        $repo  = new outcome_set_repository();
        $found = $repo->find(1);
        $this->assertEquals($this->_outcome_set, $found);
    }

    public function test_find_miss() {
        $repo = new outcome_set_repository();
        $this->assertFalse($repo->find(100000000000000));
    }

    public function test_find_by() {
        $repo  = new outcome_set_repository();
        $found = $repo->find_by(array('idnumber' => 'ABCD'));
        $this->assertEquals(array('1' => $this->_outcome_set), $found);
    }

    public function test_find_used_by_course() {
        $repo  = new outcome_set_repository();
        $found = $repo->find_used_by_course(2);
        $this->assertEquals(array('1' => $this->_outcome_set), $found);
    }

    public function test_find_by_area() {
        $area = new area_model();
        $area->id = '1';
        $area->component = 'mod_forum';
        $area->area = 'mod';
        $area->itemid = '1';

        $repo  = new outcome_set_repository();
        $found = $repo->find_by_area($area);

        $this->assertEquals(array('1' => $this->_outcome_set), $found);
    }

    public function test_is_idnumber_unique() {
        $repo = new outcome_set_repository();
        $this->assertTrue($repo->is_idnumber_unique('ABCD', 1));
        $this->assertTrue($repo->is_idnumber_unique('987TotallyUniqueForeverAndEver'));
        $this->assertTrue($repo->is_idnumber_unique('987TotallyUniqueForeverAndEver', 1));
        $this->assertFalse($repo->is_idnumber_unique('ABCD'));
        $this->assertFalse($repo->is_idnumber_unique('ABCD', 2));
    }

    public function test_fetch_metadata_values() {
        $repo = new outcome_set_repository();
        $values = $repo->fetch_metadata_values($this->_outcome_set, 'edulevels');
        $this->assertEquals(array('10', '9'), $values);

        $values = $repo->fetch_metadata_values($this->_outcome_set, 'subjects');
        $this->assertEquals(array('English', 'Math'), $values);
    }

    public function test_fetch_mapped_courses() {
        $expected = array(
            '2' => (object) array(
                'id' => '2',
                'shortname' => 'outcomeSN',
                'idnumber' => 'outcomeID',
                'fullname' => 'outcomeFN'
            )
        );

        $repo    = new outcome_set_repository();
        $courses = $repo->fetch_mapped_courses($this->_outcome_set);
        $this->assertEquals($expected, $courses);
    }

    public function test_save() {
        global $DB;

        $model = new outcome_set_model();
        $model->name = 'phpunit';
        $model->idnumber = 'phpunit';

        $now = time();

        $repo = new outcome_set_repository();
        $repo->save($model);

        $this->assertNotNull($model->id);
        $this->assertGreaterThan(0, $model->id);
        $this->assertEquals($now, $model->timecreated, '', 2);
        $this->assertEquals($now, $model->timemodified, '', 2);
        $this->assertTrue($DB->record_exists('outcome_sets', array('id' => $model->id)));
    }

    public function test_save_update() {
        $model = clone($this->_outcome_set);
        $now   = time();

        $repo = new outcome_set_repository();
        $repo->save($model);

        $this->assertEquals($this->_outcome_set->timecreated, $model->timecreated);
        $this->assertEquals($now, $model->timemodified, '', 2);
    }

    public function test_remove() {
        $repo = new outcome_set_repository();
        $repo->remove($this->_outcome_set);
        $this->assertEquals(1, $this->_outcome_set->deleted);
    }

    public function test_restore() {
        $this->_outcome_set->deleted = '1';

        $repo = new outcome_set_repository();
        $repo->restore($this->_outcome_set);
        $this->assertEquals(0, $this->_outcome_set->deleted);
    }
}