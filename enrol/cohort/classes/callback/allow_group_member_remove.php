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
 * Callbacks for allow_group_member_remove API.
 *
 * @package    enrol_cohort
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cohort\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for allow_group_member_remove API.
 *
 * @package    enrol_cohort
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class allow_group_member_remove {

    /**
     * Check if this user can be removed from this group.
     *
     * @param \core\callback\allow_group_member_remove $callback
     * @throws coding_exception
     */
    public static function check(\core\callback\allow_group_member_remove $callback) {
        // Prevent removal of group members added by enrol_cohort.
        $callback->set_allowed(false);
    }
}
