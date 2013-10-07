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

return array(
    'course' => array(
        array('id' => '2', 'category' => '1', 'shortname' => 'outcomeSN', 'idnumber' => 'outcomeID', 'fullname' => 'outcomeFN'),
    ),
    'outcome_sets' => array(
        array(
            'id'           => '1',
            'idnumber'     => 'ABCD',
            'name'         => 'Outcome Set 1',
            'timecreated'  => '1234567890',
            'timemodified' => '1234567890'
        ),
        array(
            'id'           => '2',
            'idnumber'     => '1234',
            'name'         => 'Outcome Set 2',
            'timecreated'  => '1234567890',
            'timemodified' => '1234567890'
        ),
    ),
    'outcome' => array(
        array(
            'id'           => '1',
            'outcomesetid' => '1',
            'parentid'     => '0',
            'idnumber'     => 'EFGH',
            'description'  => 'Outcome 1',
            'assessable'   => '1',
            'sortorder'    => '0',
            'timecreated'  => '1234567890',
            'timemodified' => '1234567890'
        ),
        array(
            'id'           => '2',
            'outcomesetid' => '1',
            'parentid'     => '0',
            'idnumber'     => 'HGFE',
            'description'  => 'Outcome 2',
            'assessable'   => '0',
            'sortorder'    => '1',
            'timecreated'  => '1234567890',
            'timemodified' => '1234567890'
        ),
        array(
            'id'           => '3',
            'outcomesetid' => '1',
            'parentid'     => '2',
            'idnumber'     => '4321',
            'description'  => 'Outcome 3',
            'assessable'   => '1',
            'sortorder'    => '2',
            'timecreated'  => '1234567890',
            'timemodified' => '1234567890'
        ),
    ),
    'outcome_metadata' => array(
        array('id' => '1', 'outcomeid' => '1', 'name' => 'edulevels', 'value' => '9'),
        array('id' => '2', 'outcomeid' => '1', 'name' => 'edulevels', 'value' => '10'),
        array('id' => '3', 'outcomeid' => '1', 'name' => 'subjects', 'value' => 'Math'),
        array('id' => '4', 'outcomeid' => '3', 'name' => 'edulevels', 'value' => '10'),
        array('id' => '5', 'outcomeid' => '3', 'name' => 'subjects', 'value' => 'English'),
    ),
    'outcome_used_sets' => array(
        array(
            'id'           => '1',
            'courseid'     => '2',
            'outcomesetid' => '1',
            'filter'       => serialize(array(array('edulevels' => '10', 'subjects' => 'Math')))
        ),
    ),
    'outcome_areas' => array(
        array('id' => '1', 'component' => 'mod_forum', 'area' => 'mod', 'itemid' => '1'),
    ),
    'outcome_area_outcomes' => array(
        array('id' => '1', 'outcomeid' => '1', 'outcomeareaid' => '1'),
    ),
    'outcome_used_areas' => array(
        array('id' => '1', 'cmid' => '1', 'outcomeareaid' => '1'),
    ),
    'outcome_attempts' => array(
        array(
            'id' => '1',
            'outcomeusedareaid' => '1',
            'userid' => '1',
            'itemid' => '1',
            'percentgrade' => '90',
            'mingrade' => '0.00000',
            'maxgrade' => '100.00000',
            'rawgrade' => '90.00000',
            'timemodified' => '1234567890',
            'timecreated' => '1234567890',
        ),
    ),
);