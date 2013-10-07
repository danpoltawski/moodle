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
 * Controller Router Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\controller\controller_interface;
use core_outcome\controller\router;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_controller_router_test extends basic_testcase {
    public function test_route_action() {
        $controller1 = $this->getMock('\core_outcome\controller\controller_interface', array('init', 'test_action'));
        $controller2 = $this->getMock('\core_outcome\controller\controller_interface', array('init', 'test_action'));

        $router = new router();
        $router->add_controller($controller1);
        $router->add_controller($controller2);

        list($routedcontroller, $method) = $router->route_action('test');

        $this->assertSame($controller1, $routedcontroller);
        $this->assertEquals('test_action', $method);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_non_public_action() {
        $controller = new outcome_controller_router_private_action_test_helper();
        $router     = new router();
        $router->add_controller($controller);
        $router->route_action('test');
    }

    /**
     * @expectedException coding_exception
     */
    public function test_route_fail() {
        $controller = $this->getMock('\core_outcome\controller\controller_interface', array('init'));
        $router     = new router();
        $router->add_controller($controller);
        $router->route_action('test');
    }
}

/**
 * Used to test a protected action
 */
class outcome_controller_router_private_action_test_helper implements controller_interface {
    public function init($action) {
    }
    protected function test_action() {
    }
}