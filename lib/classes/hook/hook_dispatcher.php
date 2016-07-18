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
 * Hook class.
 *
 * @package    core
 * @copyright  2014 Petr Skoda {@link http://skodak.org}
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\hook;

use core\callback\dispatcher_base;
use core\callback\dispatchable;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide quick and dirty extension points for plugins to intercept and modify data.
 *
 * The concepts used here are a "hook" (wrapper containing hook name and args stdClass)
 * The invoker (The calling code)
 * The receiver (The callback executed in response to the command)
 *
 * Valid hooks must be registered in the hooks array in db/hooks.php for either core, or a plugin.
 *
 * Receivers are registered in the receivers array in db/hooks.php. Any plugin or core may
 * register receivers.
 *
 * @package    core
 * @copyright  2014 Petr Skoda
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_dispatcher extends dispatcher_base {

    /** @var dispatcher_base Singleton instance per sub-class */
    protected static $instance;

    /**
     * We ignore the optional componentname and throwexceptions for hooks.
     *
     * @param dispatchable $dispatchable
     * @param string $componentname
     * @param bool $throwexceptions
     * @return dispatchable
     */
    public function dispatch(dispatchable $dispatchable, $componentname = null, $throwexceptions = false) {
        return parent::dispatch($dispatchable);
    }

    /**
     * This dispatcher requires commands registered in db/hooks.php.
     *
     * @return string
     */
    public function get_registration_file_name() {
        return 'db/hooks.php';
    }

    /**
     * The name of the receiever array for commands is '$receivers'
     *
     * @return string
     */
    public function get_receiver_array_name() {
        return 'receivers';
    }

    /**
     * The name of the dipatchable array is '$hooks'
     *
     * @return string
     */
    public function get_dispatchable_array_name() {
        return 'hooks';
    }

    /**
     * The name of the cache is 'hookreceivers'
     *
     * @return string
     */
    public function get_cache_name() {
        return 'hookreceivers';
    }

    /**
     * Checks that hook is listed in lib/db/hooks.php or the db/hooks.php file of a plugin or subsystem.
     *
     * This function is only executed in debugging mode.
     * @param dispatchable $dispatchable
     */
    protected function validate(dispatchable $dispatchable) {
        global $CFG;
        if (PHPUNIT_TEST) {
            // Ignore commands defined in phpunit fixtures.
            return;
        }

        $hookname = $dispatchable->get_key();
        if (!isset($this->alldispatchables[$hookname])) {
            debugging("Attempt to fire a hook '$hookname' that is not registered in db/hooks.php of any component.",
                      DEBUG_DEVELOPER);
        }

        $component = $this->get_dispatchable_component($hookname);
        $component .= '-';
        if (strncmp($component, $hookname, count($component)) != 0) {
            debugging("Hook names must begin with the component name where the hook is declared, followed by a dash. " .
                      "'$hookname' is not correct for component '$component'.",
                      DEBUG_DEVELOPER);
        }
    }
}
