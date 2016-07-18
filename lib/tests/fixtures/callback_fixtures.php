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
 * Fixtures for callback testing.
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_tests\callback;

defined('MOODLE_INTERNAL') || die();

/**
 * Test callback class
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unittest_executed extends \core\callback\callback {

    /** @var  stdClass callback argument */
    protected $object;

    /**
     * Creates an instance of the callback
     *
     * @param \stdClass $object
     * @return static
     */
    public static function create($object) {
        $callback = new static();
        $callback->object = $object;
        return $callback;
    }

    /**
     * Returns a copy of the object (to prevent modification by callbacks).
     *
     * @return object
     */
    public function get_object() {
        return (object)(array)$this->object;
    }
}

/**
 * Test callbacks
 *
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unittest_callback {

    /** @var array stores information about callbacks */
    public static $info = array();

    /** @var array stores all callbacks observed since last reset */
    public static $callback = array();

    /**
     * Resets caches
     */
    public static function reset() {
        self::$info = array();
        self::$callback = array();
    }

    /**
     * First callback
     * @param unittest_executed $callback
     */
    public static function observe_one(unittest_executed $callback) {
        self::$info[] = 'observe_one-'.$callback->get_object()->id;
        self::$callback[] = $callback;
    }

    /**
     * Second callback
     * @param unittest_executed $callback
     */
    public static function observe_two(unittest_executed $callback) {
        self::$info[] = 'observe_two-'.$callback->get_object()->id;
        self::$callback[] = $callback;
    }

    /**
     * Callback that throws an exception
     * @param unittest_executed $callback
     */
    public static function broken_callback(unittest_executed $callback) {
        self::$info[] = 'broken_callback-'.$callback->get_object()->id;
        self::$callback[] = $callback;
        throw new \Exception('someerror');
    }

    /**
     * Callback that tries to recursively execute callback
     * @param unittest_executed $callback
     */
    public static function recursive_callback1(unittest_executed $callback) {
        self::$info[] = 'recursive_callback1-'.$callback->get_object()->id;
        self::$callback[] = $callback;
        $callback->dispatch();
    }

    /**
     * Callback that tries to recursively execute callback
     * @param unittest_executed $callback
     */
    public static function recursive_callback2(unittest_executed $callback) {
        self::$info[] = 'recursive_callback2-'.$callback->get_object()->id;
        self::$callback[] = $callback;
        unittest_executed::create((object)array('id' => 3))->dispatch();
    }

    /**
     * Generic callback that can be used in unittests for any callback
     * @param \core\callback\callback $callback
     */
    public static function generic_callback(\core\callback\callback $callback) {
        self::$info[] = 'generic_callback';
        self::$callback[] = $callback;
    }
}
