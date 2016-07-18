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
 * Allow this group member to be removed from the group.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Allow this group member to be removed from the group.
 *
 * Called whenever anybody tries (from the normal interface) to remove a group
 * member which is registered as being created by this component. (Not called
 * when deleting an entire group or course at once.)
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class allow_group_member_remove extends callback_with_legacy_support {

    /** @var int $itemid */
    private $itemid = 0;
    /** @var int $groupid */
    private $groupid = 0;
    /** @var int $userid */
    private $userid = 0;
    /** @var bool $allowed */
    private $allowed = true;

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must perform their visibility checks and then call "group_member_restored"
     * on this callback class to confirm the group member was restored.
     *
     * @param array $params - Named array of arguments including step and data.
     */
    private function __construct($params = []) {
        if (isset($params['itemid'])) {
            $this->itemid = $params['itemid'];
        }
        if (isset($params['groupid'])) {
            $this->groupid = $params['groupid'];
        }
        if (isset($params['userid'])) {
            $this->userid = $params['userid'];
        }
        $this->allowed = true;
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - Named array of arguments including itemid, groupid and userid.
     * @return allow_group_member_remove
     */
    public static function create($params = []) {
        return new static($params);
    }

    /**
     * Map the fields in this class to the format expected for backwards compatibility with component_callback.
     * @return mixed $args
     */
    public function get_legacy_arguments() {
        $args = [
            $this->itemid,
            $this->groupid,
            $this->userid
        ];
        // The arguments are expected in a numerically indexed array.
        return $args;
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'allow_group_member_remove';
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->allowed;
    }

    /**
     * Map the legacy result to the visible field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        $this->allowed = $result;
    }

    /**
     * Get the userid
     * @return int
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Get the itemid
     * @return int
     */
    public function get_itemid() {
        return $this->itemid;
    }

    /**
     * Get the groupid
     * @return int
     */
    public function get_groupid() {
        return $this->groupid;
    }

    /**
     * Update the result of the callback.
     * @param bool $allowed
     */
    public function set_allowed($allowed) {
        $this->allowed = $allowed;
    }

    /**
     * Get the result of the callback.
     * @return bool
     */
    public function is_allowed() {
        return $this->allowed;
    }

}
