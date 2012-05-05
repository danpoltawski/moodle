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

require_once(dirname(__FILE__).'/../config.php');
require_once($CFG->dirroot.'/course/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$sectionno = required_param('section', PARAM_INT);

$PAGE->set_url('/course/deletesection.php', array('courseid' => $courseid, 'section' => $sectionno));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// Authorisation checks.
require_login($course);
require_capability('moodle/course:update', context_course::instance($course->id));
require_sesskey();

$transaction = $DB->start_delegated_transaction();

// Delete the section (throws exception if not empty).
course_delete_empty_section($course, $sectionno);

// Reduce the number of sections in the course.
$course->numsections--;
$DB->update_record('course', $course);

// Commit!
$transaction->allow_commit();

rebuild_course_cache($course->id);

// Redirect to where we were..
redirect(course_get_url($course));
