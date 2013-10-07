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
 * Outcome Helper Service Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\outcome_model;
use core_outcome\service\outcome_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_service_outcome_helper_test extends advanced_testcase {
    public function test_clean_and_validate() {
        $now = time();

        $dirty = new outcome_model();
        $dirty->id = '3';
        $dirty->outcomesetid = 4;
        $dirty->parentid = '';
        $dirty->idnumber = ' idnumber ';
        $dirty->docnum = '';
        $dirty->description = ' description ';
        $dirty->subjects = array(' math ', 'science ');
        $dirty->edulevels = array(' K ', '10 ');
        $dirty->timecreated = $now;
        $dirty->timemodified = $now;

        $helper = new outcome_helper();
        $helper->clean_and_validate($dirty);

        $this->assertNull($dirty->parentid);
        $this->assertNull($dirty->docnum);
        $this->assertEquals(3, $dirty->id);
        $this->assertEquals('idnumber', $dirty->idnumber);
        $this->assertEquals('description', $dirty->description);
        $this->assertEquals(array('math', 'science'), $dirty->subjects);
        $this->assertEquals(array('K', '10'), $dirty->edulevels);
        $this->assertEquals($now, $dirty->timemodified);
        $this->assertEquals($now, $dirty->timecreated);

        // Ensure parent ID and doc num can survive.
        $dirty->parentid = '5';
        $dirty->docnum = '1.2.A.B.C';

        $helper->clean_and_validate($dirty);

        $this->assertEquals(5, $dirty->parentid);
        $this->assertEquals('1.2.A.B.C', $dirty->docnum);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_clean_and_validate_no_desc() {
        $dirty               = new outcome_model();
        $dirty->outcomesetid = 4;
        $dirty->idnumber     = ' idnumber ';
        $dirty->description  = '  ';

        $helper = new outcome_helper();
        $helper->clean_and_validate($dirty);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_clean_and_validate_no_set_id() {
        $dirty               = new outcome_model();
        $dirty->outcomesetid = '0';
        $dirty->idnumber     = ' idnumber ';
        $dirty->description  = 'description';

        $helper = new outcome_helper();
        $helper->clean_and_validate($dirty);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_clean_and_validate_no_idnumber() {
        $dirty               = new outcome_model();
        $dirty->outcomesetid = '1';
        $dirty->idnumber     = '  ';
        $dirty->description  = 'description';

        $helper = new outcome_helper();
        $helper->clean_and_validate($dirty);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_clean_and_validate_no_idnumber2() {
        $dirty               = new outcome_model();
        $dirty->outcomesetid = '1';
        $dirty->description  = 'description';

        $helper = new outcome_helper();
        $helper->clean_and_validate($dirty);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_clean_and_validate_dupe_idnumber() {
        global $DB;

        $this->resetAfterTest();

        $DB->insert_record('outcome', (object) array(
            'outcomesetid' => '2',
            'idnumber'     => 'idnumber',
            'description'  => 'desc',
            'assessable'   => 1,
            'sortorder'    => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ));

        $dirty               = new outcome_model();
        $dirty->outcomesetid = '1';
        $dirty->idnumber     = ' idnumber ';
        $dirty->description  = 'description';

        $helper = new outcome_helper();
        $helper->clean_and_validate($dirty);
    }
}