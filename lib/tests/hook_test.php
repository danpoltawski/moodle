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
 * Tests for hook dispatcher, hook class and callbacks.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/hook_fixtures.php');

/**
 * Tests for hook dispatcher, hook class and callbacks.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_hook_testcase extends advanced_testcase {

    /**
     * Test parsing hooks lists
     */
    public function test_hooks_parsing() {
        global $CFG;

        $receivers = array(
            array(
                'name'    => 'one',
                'callback'    => '\core_tests\hook\unittest_hook::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
            array(
                'name' => 'broken',
                'callback' => '\core_tests\hook\unittest_hook::broken_hook',
                'priority' => 100,
            ),
            array(
                'name' => 'one',
                'callback' => '\core_tests\hook\unittest_hook::observe_two',
                'priority' => 200,
            ),
        );

        $result = \core\hook\hook_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(2, $result);

        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\hook\unittest_hook::observe_two';
        $callback->priority = 200;
        $callback->includefile = null;
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;
        $callback = new stdClass();
        $callback->callable = '\core_tests\hook\unittest_hook::observe_one';
        $callback->priority = 0;
        $callback->includefile = $CFG->dirroot.'/lib/tests/fixtures/hook_fixtures.php';
        $callback->component = 'core_phpunit';
        $expected[1] = $callback;

        $this->assertEquals($expected, $result['one']);

        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\hook\unittest_hook::broken_hook';
        $callback->priority = 100;
        $callback->includefile = null;
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;

        $this->assertEquals($expected, $result['broken']);

        // Now test broken stuff...

        $receivers = array(
            array(
                'name'    => 'one', // Fix leading backslash.
                'callback'    => '\core_tests\hook\unittest_hook::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\hook_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(1, $result);
        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\hook\unittest_hook::observe_one';
        $callback->priority = 0;
        $callback->includefile = $CFG->dirroot.'/lib/tests/fixtures/hook_fixtures.php';
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;
        $this->assertEquals($expected, $result['one']);

        $receivers = array(
            array(
                // Missing name.
                'callback'    => '\core_tests\hook\unittest_hook::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\hook_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $receivers = array(
            array(
                'name'    => '', // Empty name.
                'callback'    => '\core_tests\hook\unittest_hook::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\hook_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $receivers = array(
            array(
                'name'    => 'one',
                // Missing callable.
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\hook_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $receivers = array(
            array(
                'name'    => 'one',
                'callback'    => '', // Empty callable.
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php',
            ),
        );
        $result = \core\hook\hook_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $receivers = array(
            array(
                'name'    => 'one',
                'callback'    => '\core_tests\hook\unittest_hook::observe_one',
                'includefile' => 'lib/tests/fixtures/hook_fixtures.php_xxx', // Missing file.
            ),
        );
        $result = \core\hook\hook_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();
    }

    /**
     * Test situations when one of receivers throws an exception.
     */
    public function test_hooks_exceptions() {
        $receivers = array(

            array(
                'name' => 'one',
                'callback' => '\core_tests\hook\unittest_hook::observe_one',
            ),

            array(
                'name' => 'one',
                'callback' => '\core_tests\hook\unittest_hook::broken_hook',
                'priority' => 100,
            ),
        );

        \core\hook\hook_dispatcher::instance()->phpunit_replace_receivers($receivers);
        \core_tests\hook\unittest_hook::reset();

        $args = new stdClass();
        $args->param1 = 5;
        // Execute ignoring exceptions.
        \core\hook\hook::fire('one', $args);
        $this->assertDebuggingCalled();

        // Assert that both callbacks were executed even though the first one threw exception.
        $expected = array(
            0 => 'broken_hook-{"param1":5}',
            1 => 'observe_one-{"param1":"broken"}'
        );
        $this->assertSame(
            $expected,
            \core_tests\hook\unittest_hook::$info);
    }
}
