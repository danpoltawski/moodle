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
 * @package    core_tag
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_tag\callback;

use context_system;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for inplace editable API.
 *
 * @package    core_tag
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
        $result = false;
        $itemtype = $callback->get_itemtype();
        $itemid = $callback->get_itemid();
        $value = $callback->get_value();
        \external_api::validate_context(context_system::instance());
        if ($itemtype === 'tagname') {
            $result = \core_tag\output\tagname::update($itemid, $value);
        } else if ($itemtype === 'tagareaenable') {
            $result = \core_tag\output\tagareaenabled::update($itemid, $value);
        } else if ($itemtype === 'tagareacollection') {
            $result = \core_tag\output\tagareacollection::update($itemid, $value);
        } else if ($itemtype === 'tagareashowstandard') {
            $result = \core_tag\output\tagareashowstandard::update($itemid, $value);
        } else if ($itemtype === 'tagcollname') {
            $result = \core_tag\output\tagcollname::update($itemid, $value);
        } else if ($itemtype === 'tagcollsearchable') {
            $result = \core_tag\output\tagcollsearchable::update($itemid, $value);
        } else if ($itemtype === 'tagflag') {
            $result = \core_tag\output\tagflag::update($itemid, $value);
        } else if ($itemtype === 'tagisstandard') {
            $result = \core_tag\output\tagisstandard::update($itemid, $value);
        }
        if ($result) {
            $callback->set_inplaceeditable($result);
        }
    }
}
