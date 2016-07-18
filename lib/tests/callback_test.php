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
 * Tests for callback dispatcher, callback class and callbacks.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/callback_fixtures.php');

/**
 * Tests for callback dispatcher, callback class and callbacks.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_callback_testcase extends advanced_testcase {

    /**
     * Test parsing callbacks lists
     */
    public function test_callbacks_parsing() {
        global $CFG;

        $receivers = array(
            array(
                'name'    => '\core_tests\callback\unittest_executed',
                'callback'    => '\core_tests\callback\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/callback_fixtures.php',
            ),
            array(
                'name' => '\core\callback\unknown_executed',
                'callback' => '\core_tests\callback\unittest_callback::broken_callback',
                'priority' => 100,
            ),
            array(
                'name' => '\core_tests\callback\unittest_executed',
                'callback' => '\core_tests\callback\unittest_callback::observe_two',
                'priority' => 200,
            ),
        );

        $result = \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(2, $result);

        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\callback\unittest_callback::observe_two';
        $callback->priority = 200;
        $callback->includefile = null;
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;
        $callback = new stdClass();
        $callback->callable = '\core_tests\callback\unittest_callback::observe_one';
        $callback->priority = 0;
        $callback->includefile = $CFG->dirroot.'/lib/tests/fixtures/callback_fixtures.php';
        $callback->component = 'core_phpunit';
        $expected[1] = $callback;

        $this->assertEquals($expected, $result['\core_tests\callback\unittest_executed']);

        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\callback\unittest_callback::broken_callback';
        $callback->priority = 100;
        $callback->includefile = null;
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;

        $this->assertEquals($expected, $result['\core\callback\unknown_executed']);

        // Now test broken stuff...

        $receivers = array(
            array(
                'name'    => 'core_tests\callback\unittest_executed', // Fix leading backslash.
                'callback'    => '\core_tests\callback\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/callback_fixtures.php',
            ),
        );
        $result = \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(1, $result);
        $expected = array();
        $callback = new stdClass();
        $callback->callable = '\core_tests\callback\unittest_callback::observe_one';
        $callback->priority = 0;
        $callback->includefile = $CFG->dirroot.'/lib/tests/fixtures/callback_fixtures.php';
        $callback->component = 'core_phpunit';
        $expected[0] = $callback;
        $this->assertEquals($expected, $result['\core_tests\callback\unittest_executed']);

        $receivers = array(
            array(
                // Missing class.
                'callback'    => '\core_tests\callback\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/callback_fixtures.php',
            ),
        );
        $result = \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $receivers = array(
            array(
                'name'    => '', // Empty class.
                'callback'    => '\core_tests\callback\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/callback_fixtures.php',
            ),
        );
        $result = \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $receivers = array(
            array(
                'name'    => '\core_tests\callback\unittest_executed',
                // Missing callable.
                'includefile' => 'lib/tests/fixtures/callback_fixtures.php',
            ),
        );
        $result = \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $receivers = array(
            array(
                'name'    => '\core_tests\callback\unittest_executed',
                'callback'    => '', // Empty callable.
                'includefile' => 'lib/tests/fixtures/callback_fixtures.php',
            ),
        );
        $result = \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();

        $receivers = array(
            array(
                'name'    => '\core_tests\callback\unittest_executed',
                'callback'    => '\core_tests\callback\unittest_callback::observe_one',
                'includefile' => 'lib/tests/fixtures/callback_fixtures.php_xxx', // Missing file.
            ),
        );
        $result = \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        $this->assertCount(0, $result);
        $this->assertDebuggingCalled();
    }

    /**
     * Test situations when one of receivers throws an exception.
     */
    public function test_callbacks_exceptions() {
        $receivers = array(

            array(
                'name' => '\core_tests\callback\unittest_executed',
                'callback' => '\core_tests\callback\unittest_callback::observe_one',
            ),

            array(
                'name' => '\core_tests\callback\unittest_executed',
                'callback' => '\core_tests\callback\unittest_callback::broken_callback',
                'priority' => 100,
            ),
        );

        \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        \core_tests\callback\unittest_callback::reset();

        // Execute ignoring exceptions.
        $callback1 = \core_tests\callback\unittest_executed::create((object)array('id' => 1, 'name' => 'something'));
        $callback1->dispatch();
        $this->assertDebuggingCalled();

        // Assert that both callbacks were executed even though the first one threw exception.
        $this->assertSame(
            array('broken_callback-1', 'observe_one-1'),
            \core_tests\callback\unittest_callback::$info);

        // Execute throwing exceptions.
        \core_tests\callback\unittest_callback::reset();
        $callback1 = \core_tests\callback\unittest_executed::create((object)array('id' => 2, 'name' => 'something'));
        try {
            $callback1->dispatch(null, true);
            $this->fail('Exception expected');
        } catch (Exception $e) {
            $this->assertEquals('someerror', $e->getMessage());
        }

        // Assert that only first callback was executed and then execution stopped because of exception.
        $this->assertSame(
            array('broken_callback-2'),
            \core_tests\callback\unittest_callback::$info);
    }

    /**
     * Test executing callback for one component only.
     */
    public function test_execute_for_component() {
        $receivers = array(
            array(
                'name' => '\core_tests\callback\unittest_executed',
                'callback' => '\core_tests\callback\unittest_callback::observe_one',
            ),
        );
        $r = \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);

        // Execute callback for the component 'core_phpunit'.
        \core_tests\callback\unittest_callback::reset();
        $a = \core_tests\callback\unittest_executed::create((object)array('id' => 1));
        $a->dispatch('core_phpunit');
        $this->assertSame(
            array('observe_one-1'),
            \core_tests\callback\unittest_callback::$info);

        // Execute callback for another component.
        \core_tests\callback\unittest_callback::reset();
        \core_tests\callback\unittest_executed::create((object)array('id' => 2))->dispatch('tool_anothercomponent');
        $this->assertEmpty(\core_tests\callback\unittest_callback::$info);
    }

    /**
     * Test executing callback recursively.
     */
    public function test_execute_recursive() {
        $receivers = array(
            array(
                'name' => '\core_tests\callback\unittest_executed',
                'callback' => '\core_tests\callback\unittest_callback::recursive_callback1',
            ),
        );
        \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        \core_tests\callback\unittest_callback::reset();

        // Execute callback.
        \core_tests\callback\unittest_executed::create((object)array('id' => 1))->dispatch();
        $this->assertDebuggingCalled('Callback is already being dispatched');
        $this->assertSame(
            array('recursive_callback1-1'),
            \core_tests\callback\unittest_callback::$info);

        // Another recursive callback.
        $receivers = array(
            array(
                'name' => '\core_tests\callback\unittest_executed',
                'callback' => '\core_tests\callback\unittest_callback::recursive_callback2',
            ),
        );
        \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        \core_tests\callback\unittest_callback::reset();

        // Execute callback.
        \core_tests\callback\unittest_executed::create((object)array('id' => 1))->dispatch();
        $this->assertDebuggingCalled('Callback is already being dispatched');
        $this->assertSame(
            array('recursive_callback2-1'),
            \core_tests\callback\unittest_callback::$info);
    }

    public function test_violation_of_interplugin_communication() {

        $dispatchables = array(
            '\core_tests\callback\unittest_executed'
        );
        \core\callback\callback_dispatcher::instance()->phpunit_replace_dispatchables($dispatchables, '', 'mod_forum');
        $receivers = array(

            array(
                'name' => '\core_tests\callback\unittest_executed',
                'callback' => '\core_tests\callback\unittest_callback::observe_one',
            ),

        );

        \core\callback\callback_dispatcher::instance()->phpunit_replace_receivers($receivers);
        \core_tests\callback\unittest_callback::reset();

        // Execute ignoring exceptions.
        $callback1 = \core_tests\callback\unittest_executed::create((object)array('id' => 1, 'name' => 'something'));
        $callback1->dispatch();

        // We are subscribing to a plugin callback from core.
        $this->assertDebuggingCalled();
    }
}
