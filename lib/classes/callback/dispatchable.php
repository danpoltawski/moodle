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
 * Interface marking other classes as suitable for \core\command\dispatcher::dispatch()
 *
 * @copyright 2016 Damyon Wiese
 * @package core
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\callback;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface marking other classes as suitable for \core\command\dispatcher::dispatch()
 *
 * @copyright 2016 Damyon Wiese
 * @package core
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface dispatchable {
    /**
     * Generate a key for this dispatchable.
     * @return string key used to identify this dispatchable.
     */
    public function get_key();

    /**
     * Set the component that is currently receiving this callback
     * @param string $component
     */
    public function set_called_component($component);

    /**
     * Get the component that is currently receiving this callback
     * @return string $component
     */
    public function get_called_component();

    /**
     * Get the arguments to pass when this thing is dispatched.
     * @return mixed No restrictions.
     */
    public function get_arguments();
}
