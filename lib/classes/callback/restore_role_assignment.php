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
 * Restore role assignment callback.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Restore role assignment callback.
 *
 * This allows plugins to do custom processing when restoring role assignments from a backup, if this plugin
 * was the component that created the role_assignment in the first place.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_role_assignment extends callback_with_legacy_support {

    /** @var restore_role_assignment_structure_step $step */
    private $step;
    /** @var stdclass $data */
    private $data;
    /** @var bool $roleassignmentrestored */
    private $roleassignmentrestored;

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must perform their visibility checks and then call "role_assignment_restored"
     * on this callback class to confirm the role assignment was restored.
     *
     * @param array $params - Named array of arguments including step and data.
     */
    private function __construct($params = []) {
        $this->step = $params['step'];
        $this->data = $params['data'];
        $this->roleassignmentrestored = false;
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - Named array of arguments including step and data.
     * @return inplace_editable
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
            $this->step,
            $this->data
        ];
        // The arguments are expected in a numerically indexed array.
        return $args;
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'restore_role_assignment';
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->roleassignmentrestored;
    }

    /**
     * Map the legacy result to the visible field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        $this->roleassignmentrestored = $result;
    }

    /**
     * Get the step
     * @return restore_groups_members_structure_step
     */
    public function get_step() {
        return $this->step;
    }

    /**
     * Get the data from the backup file.
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Update the result of the callback.
     * @param bool $roleassignmentrestored
     */
    public function set_roleassignmentrestored($roleassignmentrestored) {
        $this->roleassignmentrestored = $roleassignmentrestored;
    }

    /**
     * Get the result of the callback.
     * @return bool
     */
    public function is_roleassignmentrestored() {
        return $this->roleassignmentrestored;
    }

}
