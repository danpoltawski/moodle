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
 * Rescale activity grades callback.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Rescale activity grades callback.
 *
 * Activities can implement this function to perform custom grade scaling when the maxgrade
 * for an activity has been changed "AFTER" some grading has already happened for this activity.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rescale_activity_grades extends callback_with_legacy_support {

    /** @var stdClass $course */
    private $course = null;
    /** @var stdClass $module */
    private $module = null;
    /** @var int $itemnumber */
    private $itemnumber = 0;
    /** @var int $oldgrademin */
    private $oldgrademin = 0;
    /** @var int $oldgrademax */
    private $oldgrademax = 100;
    /** @var int $newgrademin */
    private $newgrademin = 0;
    /** @var int $newgrademax */
    private $newgrademax = 100;
    /** @var bool $gradesscaled */
    private $gradesscaled = false;

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must perform their visibility checks and then call "set_visible"
     * on this callback class to update the result.
     *
     * @param array $params - List of arguments including contextid, component, ratingarea, itemid and scaleid.
     */
    private function __construct($params = []) {
        if (isset($params['course'])) {
            $this->course = $params['course'];
        }
        if (isset($params['module'])) {
            $this->module = $params['module'];
        }
        if (isset($params['itemnumber'])) {
            $this->itemnumber = $params['itemnumber'];
        }
        if (isset($params['oldgrademin'])) {
            $this->oldgrademin = $params['oldgrademin'];
        }
        if (isset($params['oldgrademax'])) {
            $this->oldgrademax = $params['oldgrademax'];
        }
        if (isset($params['newgrademin'])) {
            $this->newgrademin = $params['newgrademin'];
        }
        if (isset($params['newgrademax'])) {
            $this->newgrademax = $params['newgrademax'];
        }

        $this->gradesscaled = false;
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - Named array of arguments course, module, itemnumber, oldgrademin, oldgrademax,
     *                        newgrademin, newgrademax
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
            $this->course,
            $this->module,
            $this->oldgrademin,
            $this->oldgrademax,
            $this->newgrademin,
            $this->newgrademax,
            $this->itemnumber
        ];
        // The arguments are expected in a numerically indexed array.
        return $args;
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'rescale_activity_grades';
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->gradesscaled;
    }

    /**
     * Map the legacy result to the returned field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        $this->gradesscaled = $result;
    }

    /**
     * Get the course
     * @return stdClass
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * Get the module
     * @return stdClass
     */
    public function get_module() {
        return $this->module;
    }

    /**
     * Get the itemnumber
     * @return int
     */
    public function get_itemnumber() {
        return $this->itemnumber;
    }

    /**
     * Get the oldgrademin
     * @return int
     */
    public function get_oldgrademin() {
        return $this->oldgrademin;
    }

    /**
     * Get the oldgrademax
     * @return int
     */
    public function get_oldgrademax() {
        return $this->oldgrademax;
    }

    /**
     * Get the newgrademin
     * @return int
     */
    public function get_newgrademin() {
        return $this->newgrademin;
    }

    /**
     * Get the newgrademax
     * @return int
     */
    public function get_newgrademax() {
        return $this->newgrademax;
    }

    /**
     * Update the result of the callback.
     * @param bool $gradesscaled Setting this to false, or not calling it will generate an error.
     */
    public function set_gradesscaled($gradesscaled) {
        $this->gradesscaled = $gradesscaled;
    }

    /**
     * Get the result of the rescaleactivitygrades callback.
     * @return bool
     */
    public function is_gradesscaled() {
        return $this->gradesscaled;
    }

}
