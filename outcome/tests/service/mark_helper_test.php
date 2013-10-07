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
 * Mark Helper Service Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\mark_model;
use core_outcome\model\outcome_model;
use core_outcome\service\mark_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_service_mark_helper_test extends basic_testcase {
    public function test_mark_outcomes_as_earned() {
        $outcomeids = array('1', '2');

        $outcomesmock = $this->getMock('\core_outcome\model\outcome_repository', array('find_by_ids'));
        $outcomesmock->expects($this->once())
            ->method('find_by_ids')
            ->with($this->equalTo($outcomeids))
            ->will($this->returnValue(array(
                new outcome_model(), new outcome_model())
            ));

        $mock = $this->getMock(
            '\core_outcome\service\mark_helper',
            array('mark_outcome_as_earned'),
            array(null, $outcomesmock)
        );
        $mock->expects($this->exactly(2))
            ->method('mark_outcome_as_earned')
            ->withAnyParameters();

        $mock->mark_outcomes_as_earned(5, 7, 10, $outcomeids);
    }

    public function test_mark_outcome_as_earned() {
        $model = new outcome_model();
        $model->id = 1;
        $model->assessable = 1;

        $marksmock = $this->getMock('\core_outcome\model\mark_repository', array('find_one_by', 'save'));
        $marksmock->expects($this->once())
            ->method('find_one_by')
            ->withAnyParameters()
            ->will($this->returnValue(false));

        $marksmock->expects($this->once())
            ->method('save')
            ->withAnyParameters();

        $service = new mark_helper($marksmock);
        $service->mark_outcome_as_earned('5', '7', '10', $model);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_mark_non_assessable_outcome_as_earned() {
        $model = new outcome_model();
        $model->id = 1;
        $model->assessable = 0;

        $service = new mark_helper();
        $service->mark_outcome_as_earned('5', '7', '10', $model);
    }

    public function test_update_mark_earned() {
        $mark1 = new mark_model();
        $mark1->id = 1;
        $mark1->result = mark_model::NOT_EARNED;

        $mark2 = new mark_model();
        $mark2->id = 2;
        $mark2->result = mark_model::EARNED;

        $mark3 = new mark_model();
        $mark3->id = 3;
        $mark3->result = mark_model::NOT_EARNED;

        $mark4 = new mark_model();
        $mark4->id = 4;
        $mark4->result = mark_model::EARNED;

        $marksmock = $this->getMock('\core_outcome\model\mark_repository', array('find_by_ids', 'save'));
        $marksmock->expects($this->once())
            ->method('find_by_ids')
            ->withAnyParameters()
            ->will($this->returnValue(array($mark1, $mark2, $mark3, $mark4)));

        $marksmock->expects($this->at(1))
            ->method('save')
            ->with($this->identicalTo($mark1));

        $marksmock->expects($this->at(2))
            ->method('save')
            ->with($this->identicalTo($mark2));

        $service = new mark_helper($marksmock);
        $service->update_mark_earned(7, array(1, 2, 3, 4), array(1, 4));

        $this->assertEquals(mark_model::EARNED, $mark1->result);
        $this->assertEquals(mark_model::NOT_EARNED, $mark2->result);
        $this->assertEquals(mark_model::NOT_EARNED, $mark3->result);
        $this->assertEquals(mark_model::EARNED, $mark4->result);

        $this->assertEquals(7, $mark1->graderid);
        $this->assertEquals(7, $mark2->graderid);
        $this->assertNotEquals(7, $mark3->graderid);
        $this->assertNotEquals(7, $mark4->graderid);
    }
}