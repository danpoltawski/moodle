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
 * Fixtures for hook testing.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_tests\hook;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Test hooks
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unittest_hook {

    /** @var array stores information about callbacks */
    public static $info = array();

    /**
     * Resets caches
     */
    public static function reset() {
        self::$info = array();
    }

    /**
     * First callback
     * @param stdClass $args
     */
    public static function observe_one(stdClass $args) {
        $info = json_encode($args);
        $args->param1 = 1;
        self::$info[] = 'observe_one-'.$info;
    }

    /**
     * Second callback
     * @param stdClass $args
     */
    public static function observe_two(stdClass $args) {
        $info = json_encode($args);
        $args->param1 = 2;
        self::$info[] = 'observe_two-'.$info;
    }

    /**
     * Callback that throws an exception
     * @param stdClass $args
     */
    public static function broken_hook(stdClass $args) {
        $info = json_encode($args);
        self::$info[] = 'broken_hook-'.$info;
        $args->param1 = 'broken';
        throw new \Exception('someerror');
    }

    /**
     * Generic callback that can be used in unittests for any callback
     * @param \stdClass $args
     */
    public static function generic_callback(\stdClass $args) {
        $info = json_encode($args);
        self::$info[] = 'generic_callback-'.$info;
        $args->param1 = 'generic';
        self::$callback[] = $callback;
    }
}
