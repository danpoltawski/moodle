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
 * Base callback class.
 *
 * @package    core
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die();

/**
 * All other callback classes must extend this class.
 *
 * @package    core
 * @copyright  2014 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class callback implements dispatchable {

    /** @var bool $dispatching Is the callback being dispatched? */
    protected static $dispatching = false;

    /** @var string $calledcomponent. Remember the component that is currently receiving a callback */
    protected $calledcomponent = '';

    /**
     * Dispatch the callback.
     *
     * @param string $componentname when specified the callback is dispatched only to specific component or plugin
     * @param bool $throwexceptions if set to false (default) all exceptions during callbacks will be
     *      converted to debugging messages and will not prevent further dispatching to other callbacks
     * @return self
     */
    public function dispatch($componentname = null, $throwexceptions = false) {
        if (static::$dispatching) {
            // Prevent recursion.
            debugging('Callback is already being dispatched', DEBUG_DEVELOPER);
            return $this;
        }
        static::$dispatching = true;
        try {
            callback_dispatcher::instance()->dispatch($this, $componentname, $throwexceptions);
        } catch (\Exception $e) {
            static::$dispatching = false;
            throw $e;
        }
        static::$dispatching = false;
        return $this;
    }

    /**
     * Sometimes we want different behaviour if a callback exists or not.
     * @param string $componentname
     * @return bool
     */
    public function has_receiver($componentname) {
        return callback_dispatcher::instance()->has_receiver($componentname, $this);
    }

    /**
     * For callbacks the key is the absolute classname.
     */
    public function get_key() {
        return '\\' . get_called_class();
    }

    /**
     * Callbacks get the callback instance passed as an argument.
     */
    public function get_arguments() {
        return $this;
    }

    /**
     * Set the component that is currently receiving this callback
     * @param string $component
     */
    public function set_called_component($component) {
        $this->calledcomponent = $component;
    }

    /**
     * Get the component that is currently receiving this callback
     * @return string $component
     */
    public function get_called_component() {
        return $this->calledcomponent;
    }

}
