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
 * Callbacks for strings_for_js API.
 *
 * @package    atto_indent
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace atto_indent\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for strings_for_js API.
 *
 * @package    atto_indent
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class strings_for_js {

    /**
     * Get the strings for this component.
     *
     * @param \editor_atto\callback\strings_for_js $callback
     * @throws coding_exception
     */
    public static function get_strings(\editor_atto\callback\strings_for_js $callback) {
        global $PAGE;
        $PAGE->requires->strings_for_js(array('indent', 'outdent'), 'atto_indent');
    }
}
