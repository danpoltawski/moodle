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
 * Print recent activity.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Print recent activity. This API asks plugins to directly print chunks of HTML (used by the recent_activities block).
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class print_recent_activity extends callback_with_legacy_support {

    /** @var stdClass $course */
    private $course;
    /** @var int $timestart */
    private $timestart;
    /** @var bool $viewfullnames */
    private $viewfullnames;

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must perform their visibility checks and then call "set_visible"
     * on this callback class to update the result.
     *
     * @param array $params - List of arguments including contextid, component, ratingarea, itemid and scaleid.
     */
    private function __construct($params = []) {
        $this->course = $params['course'];
        $this->viewfullnames = clean_param($params['viewfullnames'], PARAM_BOOL);
        $this->timestart = clean_param($params['timestart'], PARAM_INT);
        $this->hascontent = false;
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - List of arguments including course, timestart and viewfullnames.
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
            $this->viewfullnames,
            $this->timestart
        ];
        // The arguments are expected in a numerically indexed array.
        return $args;
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'print_recent_activity';
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->hascontent;
    }

    /**
     * Map the legacy result to the visible field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        $this->hascontent = $result;
    }

    /**
     * Get the course
     * @return stdClass
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * Get the viewfullnames
     * @return bool
     */
    public function get_viewfullnames() {
        return $this->viewfullnames;
    }

    /**
     * Get the timestart
     * @return int
     */
    public function get_timestart() {
        return $this->timestart;
    }

    /**
     * Update the result of the callback.
     * @param bool $hascontent
     */
    public function set_hascontent($hascontent) {
        $this->hascontent = $hascontent;
    }

    /**
     * Get the result of the print_recent_activity callback.
     * @return bool
     */
    public function has_content() {
        return $this->hascontent;
    }

}
