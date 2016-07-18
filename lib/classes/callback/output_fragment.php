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
 * Output fragment callback.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Output fragment callback.
 *
 * Used to fetch chunks of php rendered html for updating a part of a page
 * without refreshing it. JS requiredments are tracked and returned along with the HTML. This even
 * works with mforms.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class output_fragment extends callback_with_legacy_support {

    /** @var string $fragmentname */
    private $fragmentname = '';
    /** @var array $args */
    private $args = [];
    /** @var string $html */
    private $html = '';

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must echo chunks of HTML. They will be automatically collected, along
     * with their JS requirements and returned to the calling javascript.
     *
     * @param array $params - List of arguments including args (named array of arbitrary arguments) and fragmentname.
     */
    private function __construct($params = []) {
        if (isset($params['args'])) {
            $this->args = $params['args'];
        }
        if (isset($params['fragmentname'])) {
            $this->fragmentname = $params['fragmentname'];
        }
        $this->html = '';
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - Named array of params including 'args' custom array of parameters and 'fragmentname'.
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
        // The arguments are expected in a numerically indexed array.
        return $this->args;
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'output_fragment_' . $this->fragmentname;
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->html;
    }

    /**
     * Map the legacy result to the html field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        $this->html = $result;
    }

    /**
     * Get the html
     * @return string
     */
    public function get_html() {
        return $this->html;
    }

    /**
     * Get the args
     * @return array
     */
    public function get_args() {
        return $this->args;
    }

    /**
     * Get the fragmentname
     * @return string
     */
    public function get_fragmentname() {
        return $this->fragmentname;
    }

    /**
     * Update the result of the callback.
     * @param string $html
     */
    public function set_html($html) {
        $this->html = $html;
    }
}
