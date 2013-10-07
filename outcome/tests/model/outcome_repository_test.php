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
 * Outcome Model Repository Mapper Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\area_model;
use core_outcome\model\filter_model;
use core_outcome\model\outcome_model;
use core_outcome\model\outcome_repository;
use core_outcome\model\outcome_set_model;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_model_outcome_repository_test extends advanced_testcase {
    /**
     * @var outcome_model
     */
    protected $_outcome;

    /**
     * @var outcome_model[]
     */
    protected $_mappable_outcomes;

    /**
     * @var outcome_model[]
     */
    protected $_set_one_outcomes;

    public function setUp() {
        $this->resetAfterTest();

        $data = include(dirname(__DIR__).'/fixtures/base.php');
        $this->loadDataSet($this->createArrayDataSet($data));
        $outcome1 = $this->_expected_outcome($data['outcome'][0]);
        $outcome2 = $this->_expected_outcome($data['outcome'][1]);
        $outcome3 = $this->_expected_outcome($data['outcome'][2]);

        $this->_outcome = clone($outcome1);
        $this->_mappable_outcomes = array('1' => clone($outcome1), '3' => clone($outcome3));

        // Expect metadata from outcome set query.
        $outcome1->edulevels = array('9', '10');
        $outcome1->subjects  = array('Math');
        $outcome3->edulevels = array('10');
        $outcome3->subjects  = array('English');

        $this->_set_one_outcomes = array('1' => $outcome1, '2' => $outcome2, '3' => $outcome3);
    }

    protected function _expected_outcome($data) {
        $model = new outcome_model();

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

    public function test_filter_to_sql_early_exit() {
        $filter               = new filter_model();
        $filter->outcomesetid = '1';
        $filter->add_filter('10', 'Math');
        $filter->add_filter(null, null);

        $repo = new outcome_repository();
        $result = $repo->filter_to_sql($filter);

        $this->assertEquals($filter->outcomesetid, reset($result[1]), 'Should just filter by outcome set ID');
        $this->assertEquals(1, count($result[1]));
    }

    public function test_find() {
        $repo  = new outcome_repository();
        $found = $repo->find(1);
        $this->assertEquals($this->_outcome, $found);
    }

    public function test_find_miss() {
        $repo = new outcome_repository();
        $this->assertFalse($repo->find(100000000000000));
    }

    public function test_find_by() {
        $repo  = new outcome_repository();
        $found = $repo->find_by(array('idnumber' => 'EFGH'));
        $this->assertEquals(array('1' => $this->_outcome), $found);
    }

    public function test_find_by_ids() {
        $repo  = new outcome_repository();
        $found = $repo->find_by_ids(array(1, 3));
        $this->assertEquals($this->_mappable_outcomes, $found);
    }

    public function test_find_by_outcome_set() {
        $outcomeset = new outcome_set_model();
        $outcomeset->id = 1;
        $repo  = new outcome_repository();
        $found = $repo->find_by_outcome_set($outcomeset, true);
        $this->assertEquals($this->_set_one_outcomes, $found);
    }

    public function test_find_by_filter() {
        $filter               = new filter_model();
        $filter->outcomesetid = '1';
        $filter->add_filter('10', 'Math');

        $repo  = new outcome_repository();
        $found = $repo->find_by_filter($filter);
        $this->assertEquals(array('1' => $this->_outcome), $found);
    }

    public function test_find_by_area() {
        $area            = new area_model();
        $area->id        = '1';
        $area->component = 'mod_forum';
        $area->area      = 'mod';
        $area->itemid    = '1';

        $repo  = new outcome_repository();
        $found = $repo->find_by_area($area);

        $this->assertEquals(array('1' => $this->_outcome), $found);
    }

    public function test_find_by_area_and_filter() {
        $filter               = new filter_model();
        $filter->outcomesetid = '1';
        $filter->add_filter('10', 'Math');

        $area            = new area_model();
        $area->id        = '1';
        $area->component = 'mod_forum';
        $area->area      = 'mod';
        $area->itemid    = '1';

        $repo  = new outcome_repository();
        $found = $repo->find_by_area_and_filter($area, $filter);

        $this->assertEquals(array('1' => $this->_outcome), $found);
    }

    public function test_find_by_area_itemids() {
        $repo  = new outcome_repository();
        $found = $repo->find_by_area_itemids('mod_forum', 'mod', array('1'));

        $this->assertEquals(array('1' => array('1' => $this->_outcome)), $found);
    }

    public function test_is_idnumber_unique() {
        $repo = new outcome_repository();
        $this->assertTrue($repo->is_idnumber_unique('EFGH', 1));
        $this->assertTrue($repo->is_idnumber_unique('987TotallyUniqueForeverAndEver'));
        $this->assertTrue($repo->is_idnumber_unique('987TotallyUniqueForeverAndEver', 1));
        $this->assertFalse($repo->is_idnumber_unique('EFGH'));
        $this->assertFalse($repo->is_idnumber_unique('EFGH', 2));
    }

    public function test_save() {
        global $DB;

        $model               = new outcome_model();
        $model->outcomesetid = 1;
        $model->description  = 'phpunit';
        $model->idnumber     = 'phpunit';
        $model->subjects     = array('Math', 'English');
        $model->edulevels    = array('9', '10');

        $now = time();

        $repo = new outcome_repository();
        $repo->save($model);

        $this->assertNotNull($model->id);
        $this->assertGreaterThan(0, $model->id);
        $this->assertEquals($now, $model->timecreated, '', 2);
        $this->assertEquals($now, $model->timemodified, '', 2);
        $this->assertTrue($DB->record_exists('outcome', array('id' => $model->id)));
    }

    public function test_save_update() {
        $model = clone($this->_outcome);
        $now   = time();

        $repo = new outcome_repository();
        $repo->save($model);

        $this->assertEquals($this->_outcome->timecreated, $model->timecreated);
        $this->assertEquals($now, $model->timemodified, '', 2);
    }

    public function test_update_sort_order() {
        global $DB;

        $this->_outcome->sortorder = 200;

        $repo = new outcome_repository();
        $repo->save($this->_outcome);

        $this->assertEquals($this->_outcome->sortorder, $DB->get_field('outcome', 'sortorder', array('id' => $this->_outcome->id)));
    }
}