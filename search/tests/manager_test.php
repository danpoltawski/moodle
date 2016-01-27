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
 * Search manager unit tests.
 *
 * @package     core_search
 * @category    phpunit
 * @copyright   2015 David Monllao {@link http://www.davidmonllao.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/testable_core_search.php');

/**
 * Unit tests for search manager.
 *
 * @package     core_search
 * @category    phpunit
 * @copyright   2015 David Monllao {@link http://www.davidmonllao.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_manager_testcase extends advanced_testcase {

    public function test_search_enabled() {

        $this->resetAfterTest();

        // Disabled by default.
        $this->assertFalse(\core_search\manager::is_global_search_enabled());

        set_config('enableglobalsearch', true);
        $this->assertTrue(\core_search\manager::is_global_search_enabled());

        set_config('enableglobalsearch', false);
        $this->assertFalse(\core_search\manager::is_global_search_enabled());
    }

    public function test_search_components() {

        $this->resetAfterTest();

        set_config('enableglobalsearch', true);

        $this->assertTrue(\core_search\manager::is_component_supported('mod_forum'));

        // Returns the instance as long as the component is supported.
        $componentsearch = \core_search\manager::get_search_component('mod_forum');
        $this->assertInstanceOf('\core_search\base', $componentsearch);

        $this->assertFalse(\core_search\manager::get_search_component('mod_unexisting'));

        $this->assertArrayHasKey('mod_forum', \core_search\manager::get_search_components_list());
        $this->assertArrayNotHasKey('mod_unexisting', \core_search\manager::get_search_components_list());

        // Enabled by default once global search is enabled.
        $this->assertArrayHasKey('mod_forum', \core_search\manager::get_search_components_list(true));

        set_config('enablesearch', false, 'mod_forum');
        \core_search\manager::clear_static();

        $this->assertArrayNotHasKey('mod_forum', \core_search\manager::get_search_components_list(true));

        set_config('enablesearch', true, 'mod_forum');

        // Although the result is wrong, we want to check that \core_search\manager::get_search_components_list returns cached results.
        $this->assertArrayNotHasKey('mod_forum', \core_search\manager::get_search_components_list(true));

        \core_search\manager::clear_static();
        $this->assertArrayHasKey('mod_forum', \core_search\manager::get_search_components_list(true));
    }

    public function test_search_config() {

        $this->resetAfterTest();

        $search = testable_core_search::instance();

        // We should test both plugin types and core subsystems. No core subsystems available yet.
        $componentsearch = $search->get_search_component('mod_forum');

        list($componentconfigname, $varname) = $componentsearch->get_config_var_name();

        // Just with a couple of vars should be enough.
        $start = time() - 100;
        $end = time();
        set_config($varname . '_indexingstart', $start, $componentconfigname);
        set_config($varname . '_indexingend', $end, $componentconfigname);

        $configs = $search->get_components_config(array('mod_forum' => $componentsearch));
        $this->assertEquals($start, $configs['mod_forum']->indexingstart);
        $this->assertEquals($end, $configs['mod_forum']->indexingend);

        try {
            $search->reset_config('mod_unexisting');
            $this->fail('An exception should be triggered if the provided component does not exist.');
        } catch (moodle_exception $ex) {
            $this->assertContains('mod_unexisting search component is not available.', $ex->getMessage());
        }

        $search->reset_config('mod_forum');
        $this->assertEquals(0, get_config($componentconfigname, $varname . '_indexingstart'));
        $this->assertEquals(0, get_config($componentconfigname, $varname . '_indexingend'));
        $this->assertEquals(0, get_config($componentconfigname, $varname . '_lastindexrun'));

        // No caching.
        $configs = $search->get_components_config(array('mod_forum' => $componentsearch));
        $this->assertEquals(0, $configs['mod_forum']->indexingstart);
        $this->assertEquals(0, $configs['mod_forum']->indexingend);

        set_config($varname . '_indexingstart', $start, $componentconfigname);
        set_config($varname . '_indexingend', $end, $componentconfigname);

        // All components config should be reset.
        $search->reset_config();
        $this->assertEquals(0, get_config($componentconfigname, $varname . '_indexingstart'));
        $this->assertEquals(0, get_config($componentconfigname, $varname . '_indexingend'));
        $this->assertEquals(0, get_config($componentconfigname, $varname . '_lastindexrun'));

        // No caching.
        $configs = $search->get_components_config(array('mod_forum' => $componentsearch));
        $this->assertEquals(0, $configs['mod_forum']->indexingstart);
        $this->assertEquals(0, $configs['mod_forum']->indexingend);
    }

    /**
     * Adding this test here as get_components_user_accesses process is the same, results just depend on the context level.
     *
     * @return void
     */
    public function test_search_user_accesses() {
        global $DB;

        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $noaccess = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($student->id, $course1->id, 'student');

        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id));
        $forum2 = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id));
        $forum3 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id));
        $context1 = context_module::instance($forum1->cmid);
        $context2 = context_module::instance($forum2->cmid);
        $context3 = context_module::instance($forum3->cmid);

        $search = testable_core_search::instance();

        $this->setAdminUser();
        $this->assertTrue($search->get_components_user_accesses());

        $this->setUser($noaccess);
        $this->assertEquals(array(), $search->get_components_user_accesses());

        $this->setUser($teacher);
        $contexts = $search->get_components_user_accesses();
        $bothcontexts = array($context1->id => $context1->id, $context2->id => $context2->id);
        $this->assertEquals($bothcontexts, $contexts['mod_forum']);

        $this->setUser($student);
        $contexts = $search->get_components_user_accesses();
        $this->assertEquals($bothcontexts, $contexts['mod_forum']);

        // Hide the activity.
        set_coursemodule_visible($forum2->cmid, 0);
        $contexts = $search->get_components_user_accesses();
        $this->assertEquals(array($context1->id => $context1->id), $contexts['mod_forum']);
    }
}
