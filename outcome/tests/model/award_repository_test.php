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
 * Outcome Award Model Repository Mapper Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\award_model;
use core_outcome\model\award_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_model_award_repository_test extends advanced_testcase {
    /**
     * @var award_model
     */
    protected $_model;

    public function setUp() {
        $this->resetAfterTest();

        $data = include(dirname(__DIR__).'/fixtures/award.php');
        $this->loadDataSet($this->createArrayDataSet($data));

        $this->_model              = new award_model();
        $this->_model->id          = '1';
        $this->_model->outcomeid   = '1';
        $this->_model->userid      = '2';
        $this->_model->timecreated = '1234567890';
    }

    public function test_find() {
        $repo  = new award_repository();
        $found = $repo->find(1);
        $this->assertEquals($this->_model, $found);
    }

    public function test_find_miss() {
        $repo = new award_repository();
        $this->assertFalse($repo->find(100000000000000));
    }

    public function test_find_by() {
        $repo  = new award_repository();
        $found = $repo->find_by(array('outcomeid' => '1'));
        $this->assertEquals(array('1' => $this->_model), $found);
    }

    public function test_insert() {
        global $DB;

        $model            = new award_model();
        $model->outcomeid = 2;
        $model->userid    = 3;

        $repo = new award_repository();
        $repo->insert($model);

        $this->assertNotNull($model->id);
        $this->assertGreaterThan(0, $model->id);
        $this->assertEquals(time(), $model->timecreated, '', 2);
        $this->assertTrue($DB->record_exists('outcome_awards', array('id' => $model->id)));
    }

    public function test_remove_by() {
        global $DB;

        $repo = new award_repository();
        $repo->remove_by(array('userid' => 2));

        $this->assertFalse($DB->record_exists('outcome_awards', array('id' => $this->_model->id)));
    }
}