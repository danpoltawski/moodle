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
 * Course related unit tests
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class course_section_controller_testcase extends advanced_testcase {

    var $course;
    var $section;
    var $page;
    var $forum;
    var $forum_cm;

    public function setUp() {
        global $DB;
        parent::setUp();
        $this->resetAfterTest(true);

        $this->course = $this->getDataGenerator()->create_course(array('shortname' => 'FROG101', 'fullname' => 'Introduction to pond life'));

        $this->page = $this->getDataGenerator()->create_module('page', array('course'=>$this->course->id));
        $this->forum = $this->getDataGenerator()->create_module('forum', array('course'=>$this->course->id));
        $this->forum_cm = $DB->get_record('course_modules', array('id' => $this->forum->cmid));
        $sections = get_all_sections($this->course->id);
        $this->section = $DB->get_record('course_sections', 
            array('section' => $this->forum_cm->section,
                  'course' => $this->course->id));
    }

    public function test_normal() {
        rebuild_course_cache($this->course->id);

        $controller = new course_section_controller($this->course);
        $result = $controller->get_section($this->section);

        $this->assertContains('forum', $result);
        $this->assertContains('page', $result);

        // backward compatilbity test hack hack
        get_all_mods($this->course->id, $mods, $modnames, $modnamesplural, $modnamesused);

        $this->expectOutputString($result);
        print_section($this->course, $this->section, $mods, $modnamesused);
    }

    public function test_hidden_normal() {
        global $DB, $USER;

        $this->forum_cm->visible = 0;
        $DB->update_record('course_modules', $this->forum_cm);
        rebuild_course_cache($this->course->id);

        $controller = new course_section_controller($this->course);
        $result = $controller->get_section($this->section);

        $this->assertNotContains('forum', $result);
        $this->assertContains('page', $result);

        // backward compatilbity test hack hack
        get_all_mods($this->course->id, $mods, $modnames, $modnamesplural, $modnamesused);

        $this->expectOutputString($result);
        print_section($this->course, $this->section, $mods, $modnamesused);
    }

    public function test_hidden_admin() {
        global $DB, $USER;

        $this->forum_cm->visible = 0;
        $DB->update_record('course_modules', $this->forum_cm);
        rebuild_course_cache($this->course->id);

        $this->setUser(get_admin());

        $controller = new course_section_controller($this->course);
        $result = $controller->get_section($this->section);

        $this->assertContains('forum', $result);
        $this->assertContains('page', $result);
        $this->assertContains('dimmed', $result);

        // backward compatilbity test hack hack
        get_all_mods($this->course->id, $mods, $modnames, $modnamesplural, $modnamesused);

        $this->expectOutputString($result);
        print_section($this->course, $this->section, $mods, $modnamesused);
    }

    public function test_editting() {
        global $DB, $USER, $PAGE;

        $this->setUser(get_admin());
        $USER->editing = true;

        $controller = new course_section_controller($this->course, true);
        $result = $controller->get_section($this->section);

        $this->assertContains('edit', $result);

        // backward compatilbity test hack hack
        get_all_mods($this->course->id, $mods, $modnames, $modnamesplural, $modnamesused);

        $this->expectOutputString($result);
        print_section($this->course, $this->section, $mods, $modnamesused);
    }

    public function test_editting_moving() {
        global $DB, $USER, $PAGE;

        $this->setUser(get_admin());
        $USER->editing = true;
        $USER->activitycopy = 3;
        $USER->activitycopyname = 'fsfdsfs';
        $USER->activitycopycourse = $this->course->id;

        $controller = new course_section_controller($this->course, true);
        $result = $controller->get_section($this->section);

        $this->assertContains('edit', $result);
        $this->assertContains('move', $result);

        // backward compatilbity test hack hack
        get_all_mods($this->course->id, $mods, $modnames, $modnamesplural, $modnamesused);

        /**FIXME: disabled
        $this->expectOutputString($result);
        print_section($this->course, $this->section, $mods, $modnamesused);
         */
    }
}
