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
    'outcome_marks' => array(
        array(
            'id'           => '1',
            'courseid'     => '2',
            'outcomeid'    => '1',
            'userid'       => '1',
            'graderid'     => '2',
            'result'       => '1',
            'timemodified' => '1234567890',
            'timecreated'  => '1234567890',
        ),
        // Same as above, but earned in another course.
        array(
            'id'           => '2',
            'courseid'     => '3',
            'outcomeid'    => '1',
            'userid'       => '1',
            'graderid'     => '2',
            'result'       => '1',
            'timemodified' => '1234567890',
            'timecreated'  => '1234567890',
        ),
    ),
    'outcome_marks_history' => array(
        // Used case: at first the outcome was not earned and then it was after second pass.
        array(
            'id'            => '1',
            'action'        => '1',
            'outcomemarkid' => '9',
            'courseid'      => '3',
            'outcomeid'     => '5',
            'userid'        => '1',
            'graderid'      => '2',
            'result'        => '0',
            'timecreated'   => '1234567880',
        ),
        array(
            'id'            => '2',
            'action'        => '1',
            'outcomemarkid' => '9',
            'courseid'      => '3',
            'outcomeid'     => '5',
            'userid'        => '1',
            'graderid'      => '2',
            'result'        => '1',
            'timecreated'   => '1234567890',
        ),

        // Used case: the outcome was earned, but then it was revoked.
        array(
            'id'            => '3',
            'action'        => '1',
            'outcomemarkid' => '10',
            'courseid'      => '3',
            'outcomeid'     => '6',
            'userid'        => '1',
            'graderid'      => '2',
            'result'        => '1',
            'timecreated'   => '1234567880',
        ),
        array(
            'id'            => '4',
            'action'        => '1',
            'outcomemarkid' => '10',
            'courseid'      => '3',
            'outcomeid'     => '6',
            'userid'        => '1',
            'graderid'      => '2',
            'result'        => '0',
            'timecreated'   => '1234567890',
        ),
    ),
);