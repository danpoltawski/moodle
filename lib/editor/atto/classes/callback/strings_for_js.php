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
 * Strings for js callback
 *
 * @package    editor_atto
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace editor_atto\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Strings for js callback.
 *
 * @package    editor_atto
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class strings_for_js extends \core\callback\callback_with_legacy_support {

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must call strings_for_js to add all their strings to the page requirements.
     *
     */
    private function __construct() {
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @return strings_for_js
     */
    public static function create() {
        return new static();
    }

    /**
     * Map the fields in this class to the format expected for backwards compatibility with component_callback.
     * @return mixed $args
     */
    public function get_legacy_arguments() {
        // This callback expects no arguments.
        return [];
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'strings_for_js';
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return false;
    }

    /**
     * This callback has no return type.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
    }
}
