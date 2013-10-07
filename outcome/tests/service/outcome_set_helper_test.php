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
 * Outcome Set Helper Service Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\outcome_set_model;
use core_outcome\service\outcome_set_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_service_outcome_set_helper_test extends advanced_testcase {
    public function test_clean_and_validate() {
        $now = time();

        $dirty               = new outcome_set_model();
        $dirty->id           = '3';
        $dirty->name         = ' name ';
        $dirty->idnumber     = ' idnumber ';
        $dirty->description  = ' description ';
        $dirty->provider     = ' provider ';
        $dirty->revision     = ' revision ';
        $dirty->region       = ' region ';
        $dirty->deleted      = 1;
        $dirty->timemodified = $now;
        $dirty->timecreated  = $now;

        $helper = new outcome_set_helper();
        $helper->clean_and_validate($dirty);

        $this->assertEquals(3, $dirty->id);
        $this->assertEquals('name', $dirty->name);
        $this->assertEquals('idnumber', $dirty->idnumber);
        $this->assertEquals('description', $dirty->description);
        $this->assertEquals('provider', $dirty->provider);
        $this->assertEquals('revision', $dirty->revision);
        $this->assertEquals('region', $dirty->region);
        $this->assertEquals(1, $dirty->deleted);
        $this->assertEquals($now, $dirty->timemodified);
        $this->assertEquals($now, $dirty->timecreated);

        // These are option and should null if empty string.
        $dirty->description = '   ';
        $dirty->provider    = '  ';
        $dirty->revision    = '    ';
        $dirty->region      = '';

        $helper->clean_and_validate($dirty);

        $this->assertNull($dirty->description);
        $this->assertNull($dirty->provider);
        $this->assertNull($dirty->revision);
        $this->assertNull($dirty->region);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_clean_and_validate_no_name() {
        $dirty              = new outcome_set_model();
        $dirty->id          = '3';
        $dirty->name        = '   ';
        $dirty->idnumber    = ' idnumber ';
        $dirty->description = ' description ';
        $dirty->provider    = ' provider ';
        $dirty->revision    = ' revision ';
        $dirty->region      = ' region ';
        $dirty->deleted     = 1;

        $helper = new outcome_set_helper();
        $helper->clean_and_validate($dirty);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_clean_and_validate_no_idnumber() {
        $dirty           = new outcome_set_model();
        $dirty->idnumber = '  ';
        $dirty->name     = 'name';

        $helper = new outcome_set_helper();
        $helper->clean_and_validate($dirty);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_clean_and_validate_no_idnumber2() {
        $dirty       = new outcome_set_model();
        $dirty->name = 'name';

        $helper = new outcome_set_helper();
        $helper->clean_and_validate($dirty);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_clean_and_validate_dupe_idnumber() {
        global $DB;

        $this->resetAfterTest();

        $DB->insert_record('outcome_sets', (object) array(
            'idnumber' => 'idnumber',
            'name'     => 'name',
            'timecreated' => time(),
            'timemodified' => time(),
        ));

        $dirty           = new outcome_set_model();
        $dirty->idnumber = ' idnumber ';
        $dirty->name     = 'name';

        $helper = new outcome_set_helper();
        $helper->clean_and_validate($dirty);
    }
}