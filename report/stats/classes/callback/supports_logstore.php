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
 * Callbacks for supports logstore API.
 *
 * @package    report_stats
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_stats\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for supports logstore API.
 *
 * @package    report_stats
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class supports_logstore {

    /**
     * Implements callback "supports logstore" to say which logstores this report supports.
     *
     * @param \core\callback\report_supports_logstore $callback
     */
    public static function is_supported(\core\callback\report_supports_logstore $callback) {
        $supported = false;
        $instance = $callback->get_logstore();
        if ($instance instanceof \core\log\sql_internal_table_reader || $instance instanceof \logstore_legacy\log\store) {
            $supported = true;
        }
        $callback->set_supported($supported);
    }
}
