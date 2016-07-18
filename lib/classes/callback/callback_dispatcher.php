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
 * Callback dispatcher class.
 *
 * @package    core
 * @copyright  2014 Petr Skoda {@link http://skodak.org}
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\callback;

use coding_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide consistent API for inter-component communication.
 *
 * The concepts used here are a "callback" (contains modifiable data).
 * The invoker (The calling code)
 * The receiver (The callback executed in response to the callback)
 *
 * Valid callbacks must be registered in the callbacks array in db/callbacks.php for either core
 * or a core subsystems. Plugins may only register callbacks for plugins with a dependency (like sub-plugins, or explicitly
 * declared in version.php).
 *
 * Receivers are registered in the receivers array in db/callbacks.php. Any plugin or core subsystem may
 * register receivers - but only for core, core subsystems or a plugin that is a dependency.
 *
 * @package    core
 * @copyright  2014 Petr Skoda
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class callback_dispatcher extends dispatcher_base {

    /** @var dispatcher_base Singleton instance per sub-class */
    protected static $instance;

    /**
     * This dispatcher requires callbacks registered in db/callbacks.php.
     *
     * @return string
     */
    public function get_registration_file_name() {
        return 'db/callbacks.php';
    }

    /**
     * The name of the receiver array for callbacks is '$receivers'
     *
     * @return string
     */
    public function get_receiver_array_name() {
        return 'receivers';
    }

    /**
     * The name of the dipatchable array is '$callbacks'
     *
     * @return string
     */
    public function get_dispatchable_array_name() {
        return 'callbacks';
    }

    /**
     * The name of the cache is 'callbackreceivers'
     *
     * @return string
     */
    public function get_cache_name() {
        return 'callbackreceivers';
    }

    /**
     * Checks that callback classname is listed in lib/db/callbacks.php
     *
     * Plugins are only allowed to broadcast to dependant plugins.
     *
     * This function is only executed in the debugging mode.
     * @param \core\callback\dispatchable $dispatchable
     */
    protected function validate(dispatchable $dispatchable) {
        global $CFG;

        if (!$dispatchable instanceof callback) {
            throw new coding_exception('Callback dispatcher is only allowed to dispatch subclasses of \\core\\callback\\callback');
        }
        if (PHPUNIT_TEST) {
            // Ignore callbacks defined in phpunit fixtures.
            return;
        }
    }

    /**
     * Optionally sanitize the key from the registration file.
     * @param string $key The key to sanitize.
     */
    protected function sanitise_key($key) {
        return '\\' . ltrim($key, '\\');
    }
}
