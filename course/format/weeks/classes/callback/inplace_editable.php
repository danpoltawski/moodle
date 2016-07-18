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
 * Callbacks for inplace editable API.
 *
 * @package    format_weeks
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_weeks\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for inplace editable API.
 *
 * @package    format_weeks
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_editable {

    /**
     * Implements callback inplace_editable() allowing to edit values in-place
     *
     * @param \core\callback\inplace_editable $callback
     */
    public static function update(\core\callback\inplace_editable $callback) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        if ($callback->get_itemtype() === 'sectionname' || $callback->get_itemtype() === 'sectionnamenl') {
            $section = $DB->get_record_sql(
                'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
                array($callback->get_itemid(), 'weeks'), MUST_EXIST);
            $format = course_get_format($section->course);
            $result = $format->inplace_editable_update_section_name($section, $callback->get_itemtype(), $callback->get_value());
            $callback->set_inplaceeditable($result);
        }
    }
}
