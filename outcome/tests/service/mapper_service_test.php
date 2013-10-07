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
 * Mapper Service Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\area_model;
use core_outcome\model\filter_model;
use core_outcome\model\outcome_model;
use core_outcome\model\outcome_set_model;
use core_outcome\service\mapper_service;
use core_outcome\normalizer;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_service_mapper_service_test extends basic_testcase {
    public function test_get_outcome_set_mappings() {

        $outcomeset1 = new outcome_set_model();
        $outcomeset1->id = 1;
        $outcomeset1->name = 'Test 1';

        $outcomeset2 = new outcome_set_model();
        $outcomeset2->id = 2;
        $outcomeset2->name = 'Test 2';

        $filter1 = new filter_model();
        $filter1->outcomesetid = $outcomeset1->id;
        $filter1->add_filter('9', 'Math');
        $filter1->add_filter('10', 'Math');

        $filter2 = new filter_model();
        $filter2->outcomesetid = $outcomeset2->id;
        $filter2->add_filter(null, null);

        $outcomesetsmock = $this->getMock(
            '\core_outcome\model\outcome_set_repository',
            array('find_used_by_course')
        );

        $outcomesetsmock->expects($this->once())
            ->method('find_used_by_course')
            ->will($this->returnValue(array(
                $outcomeset1->id => $outcomeset1,
                $outcomeset2->id => $outcomeset2
            )));

        $filtersmock = $this->getMock(
            '\core_outcome\model\filter_repository',
            array('find_by')
        );

        $filtersmock->expects($this->once())
            ->method('find_by')
            ->will($this->returnValue(array($filter1, $filter2)));

        $service = new mapper_service(null, $outcomesetsmock, $filtersmock);
        $return = $service->get_outcome_set_mappings(1);

        $expected = array(
            array(
                'outcomesetid' => $outcomeset1->id,
                'name' => $outcomeset1->name,
                'edulevels' => '9',
                'rawedulevels' => '9',
                'subjects' => 'Math',
                'rawsubjects' => 'Math',
            ),
            array(
                'outcomesetid' => $outcomeset1->id,
                'name' => $outcomeset1->name,
                'edulevels' => '10',
                'rawedulevels' => '10',
                'subjects' => 'Math',
                'rawsubjects' => 'Math',
            ),
            array(
                'outcomesetid' => $outcomeset2->id,
                'name' => $outcomeset2->name,
                'edulevels' => null,
                'rawedulevels' => null,
                'subjects' => null,
                'rawsubjects' => null,
            ),
        );

        $this->assertEquals(json_encode($expected), $return);
    }

    public function test_save_outcome_set_mappings() {
        $filtersmock = $this->getMock(
            '\core_outcome\model\filter_repository',
            array('sync')
        );

        $filtersmock->expects($this->once())
            ->method('sync');

        $filter = new filter_model();
        $filter->outcomesetid = 1;
        $filter->add_filter('9', 'Math');
        $filter->add_filter('10', 'Math');

        $service = new mapper_service(null, null, $filtersmock);
        $service->save_outcome_set_mappings(1, array($filter));

        $this->assertEquals($filter->courseid, 1, '$filter->courseid was updated');
    }

    /**
     * @expectedException coding_exception
     */
    public function test_save_outcome_set_mappings_with_invalid_param() {
        $filtersmock = $this->getMock(
            '\core_outcome\model\filter_repository',
            array('sync')
        );

        $filtersmock->expects($this->never())
            ->method('sync');

        $filter = new filter_model();
        $filter->outcomesetid = 1;
        $filter->add_filter('9', 'Math');
        $filter->add_filter('10', 'Math');

        $service = new mapper_service(null, null, $filtersmock);
        $service->save_outcome_set_mappings(1, array(get_object_vars($filter)));
    }

    public function test_get_outcome_mappings_for_form() {

        $area                 = new area_model();
        $outcome              = new outcome_model();
        $outcome->id          = 1;
        $outcomeset           = new outcome_set_model();
        $outcomeset->id       = 1;
        $filter               = new filter_model();
        $filter->outcomesetid = 1;
        $filter->add_filter('10', 'Math');

        $areasmock = $this->getMock(
            '\core_outcome\model\area_repository',
            array('find_one')
        );

        $areasmock->expects($this->once())
            ->method('find_one')
            ->will($this->returnValue($area));

        $filtersmock = $this->getMock(
            '\core_outcome\model\filter_repository',
            array('find_by_course')
        );

        $filtersmock->expects($this->once())
            ->method('find_by_course')
            ->with($this->equalTo(2))
            ->will($this->returnValue(array($filter)));

        $outcomesmock = $this->getMock(
            '\core_outcome\model\outcome_repository',
            array('find_by_area_and_filter')
        );

        $outcomesmock->expects($this->once())
            ->method('find_by_area_and_filter')
            ->with($this->equalTo($area), $this->equalTo($filter))
            ->will($this->returnValue(array($outcome->id => $outcome)));

        $outcomesetsmock = $this->getMock(
            '\core_outcome\model\outcome_set_repository',
            array('find_by_area')
        );

        $outcomesetsmock->expects($this->once())
            ->method('find_by_area')
            ->with($this->equalTo($area))
            ->will($this->returnValue(array($outcomeset->id => $outcomeset)));

        $normalizer = new normalizer();
        $service = new mapper_service($outcomesmock, $outcomesetsmock, $filtersmock, $areasmock);
        $result = $service->get_outcome_mappings_for_form('mod_foo', 'mod', 1, 2);

        $expected = array(
            'outcomesets' => $normalizer->normalize_outcome_sets(array($outcomeset)),
            'outcomes' => $normalizer->normalize_outcomes(array($outcome)),
        );

        $this->assertEquals(json_encode($expected), $result);
    }

    public function test_save_outcome_mapping() {
        $outcome     = new outcome_model();
        $outcome->id = 1;

        $areasmock = $this->getMock(
            '\core_outcome\model\area_repository',
            array('find_one', 'save', 'remove_area_outcomes', 'save_area_outcomes')
        );

        $areasmock->expects($this->once())
            ->method('find_one')
            ->will($this->returnValue(false));

        $areasmock->expects($this->once())
            ->method('save')
            ->withAnyParameters();

        $areasmock->expects($this->once())
            ->method('remove_area_outcomes')
            ->withAnyParameters();

        $areasmock->expects($this->once())
            ->method('save_area_outcomes')
            ->withAnyParameters();

        $outcomesmock = $this->getMock(
            '\core_outcome\model\outcome_repository',
            array('find_by_area')
        );

        $outcomesmock->expects($this->once())
            ->method('find_by_area')
            ->withAnyParameters()
            ->will($this->returnValue(array($outcome->id => $outcome)));

        $service = new mapper_service($outcomesmock, null, null, $areasmock);
        $result  = $service->save_outcome_mapping('mod_foo', 'mod', 1, 1);
        $this->assertInstanceOf('\core_outcome\model\area_model', $result);
    }

    public function test_save_outcome_mapping_with_no_outcome() {
        $area            = new area_model();
        $area->component = 'mod_foo';
        $area->area      = 'mod';
        $area->itemid    = 1;

        $areasmock = $this->getMock(
            '\core_outcome\model\area_repository',
            array('find_one', 'remove')
        );

        $areasmock->expects($this->once())
            ->method('find_one')
            ->will($this->returnValue($area));

        $areasmock->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($area));

        $service = new mapper_service(null, null, null, $areasmock);
        $result  = $service->save_outcome_mapping('mod_foo', 'mod', 1, null);

        $this->assertFalse($result);
    }

    public function test_save_outcome_mappings() {
        $outcome     = new outcome_model();
        $outcome->id = 1;

        $areasmock = $this->getMock(
            '\core_outcome\model\area_repository',
            array('find_one', 'save', 'save_area_outcomes')
        );

        $areasmock->expects($this->once())
            ->method('find_one')
            ->will($this->returnValue(false));

        $areasmock->expects($this->once())
            ->method('save')
            ->withAnyParameters();

        $areasmock->expects($this->once())
            ->method('save_area_outcomes')
            ->withAnyParameters();

        $outcomesmock = $this->getMock(
            '\core_outcome\model\outcome_repository',
            array('find_by_ids', 'find_by_area')
        );

        $outcomesmock->expects($this->once())
            ->method('find_by_ids')
            ->with($this->equalTo(array(1)))
            ->will($this->returnValue(array($outcome->id => $outcome)));

        $outcomesmock->expects($this->exactly(2))
            ->method('find_by_area')
            ->withAnyParameters()
            ->will($this->returnValue(array($outcome->id => $outcome)));

        $service = new mapper_service($outcomesmock, null, null, $areasmock);
        $service->save_outcome_mappings('mod_foo', 'mod', 1, array(1));
    }

    public function test_save_outcome_mappings_with_no_outcomes() {

        $area = new area_model();
        $area->component = 'mod_foo';
        $area->area = 'mod';
        $area->itemid = 1;

        $areasmock = $this->getMock(
            '\core_outcome\model\area_repository',
            array('find_one', 'remove')
        );

        $areasmock->expects($this->once())
            ->method('find_one')
            ->will($this->returnValue($area));

        $areasmock->expects($this->once())
            ->method('remove')
            ->with($area);

        $service = new mapper_service(null, null, null, $areasmock);
        $result = $service->save_outcome_mappings('mod_foo', 'mod', 1, array());

        $this->assertFalse($result);
    }
}