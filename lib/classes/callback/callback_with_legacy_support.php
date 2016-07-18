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
 * Base callback_with_legacy_support class.
 *
 * @package    core
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die();

/**
 * Base callback_with_legacy_support class.
 *
 * Legacy callback is similar to callback, except that it also triggers a call to component_callback
 * to maintain backwards compatibility.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class callback_with_legacy_support extends callback {

    /**
     * Dispatch the callback.
     *
     * @param string $componentname when specified the callback is dispatched only to specific component or plugin
     * @param bool $throwexceptions if set to false (default) all exceptions during callbacks will be
     *      converted to debugging messages and will not prevent further dispatching to other callbacks
     * @return self
     */
    public function dispatch($componentname = null, $throwexceptions = false) {
        $result = parent::dispatch($componentname, $throwexceptions);

        if ($componentname) {
            // We pass the result of the new callbacks as the default for the old style ones.
            $default = $result->get_legacy_result();
            $this->set_called_component($componentname);
            $result = component_callback($componentname, $this->get_legacy_function(), $this->get_legacy_arguments(), $default);
            $this->set_legacy_result($result);
        } else {
            $allplugins = get_plugins_with_function($this->get_legacy_function());
            $default = $result->get_legacy_result();
            foreach ($allplugins as $plugintype => $plugins) {
                foreach ($plugins as $pluginname => $functionname) {
                    $componentname = $plugintype . '_' . $pluginname;
                    $this->set_called_component($componentname);
                    $funcname = $this->get_legacy_function();
                    $result = component_callback($componentname, $funcname, $this->get_legacy_arguments(), $default);
                    $this->set_legacy_result($result);
                }
            }
        }
        return $this;
    }

    /**
     * Get the legacy arguments exactly as they were expected by the previous usage of component_callback.
     *
     * @return array $args
     */
    abstract public function get_legacy_arguments();

    /**
     * Get the name of the legacy function for component_callback.
     *
     * @return string $functionname
     */
    abstract public function get_legacy_function();

    /**
     * After calling component_callback - this function is used to store the result in the callback class.
     * Override it to set the correct internal field.
     *
     * @param mixed $result
     */
    abstract public function set_legacy_result($result);

    /**
     * We need to map the correct field to the value that was returned by the old callback.
     * Override it to get the correct internal field.
     *
     * @return mixed $result
     */
    abstract public function get_legacy_result();
}
