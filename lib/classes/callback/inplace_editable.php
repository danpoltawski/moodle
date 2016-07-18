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
 * Inplace editable callback.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Inplace editable callback.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_editable extends callback_with_legacy_support {

    /** @var string $component */
    private $component;
    /** @var string $itemtype */
    private $itemtype;
    /** @var int $itemid */
    private $itemid;
    /** @var mixed $value */
    private $value;
    /** @var \core\output\inplace_editable $inplaceeditable */
    private $inplaceeditable;

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must perform their visibility checks and then call "set_visible"
     * on this callback class to update the result.
     *
     * @param array $params - List of arguments including contextid, component, ratingarea, itemid and scaleid.
     */
    private function __construct($params = []) {
        $this->itemtype = clean_param($params['itemtype'], PARAM_ALPHANUMEXT);
        $this->itemid = clean_param($params['itemid'], PARAM_INT);
        $this->value = clean_param($params['value'], PARAM_RAW);
        $this->inplaceeditable = null;
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - List of arguments including contextid, component, ratingarea, itemid and scaleid.
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
            $this->itemtype,
            $this->itemid,
            $this->value
        ];
        // The arguments are expected in a numerically indexed array.
        return $args;
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'inplace_editable';
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->inplaceeditable;
    }

    /**
     * Map the legacy result to the visible field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        $this->inplaceeditable = $result;
    }

    /**
     * Get the itemtype
     * @return string
     */
    public function get_itemtype() {
        return $this->itemtype;
    }

    /**
     * Get the itemid
     * @return int
     */
    public function get_itemid() {
        return $this->itemid;
    }

    /**
     * Get the value
     * @return value
     */
    public function get_value() {
        return $this->value;
    }

    /**
     * Update the result of the callback.
     * @param \core\output\inplace_editable $inplaceeditable
     */
    public function set_inplaceeditable(\core\output\inplace_editable $inplaceeditable) {
        $this->inplaceeditable = $inplaceeditable;
    }

    /**
     * Get the result of the inplaceeditable callback.
     * @return \core\output\inplace_editable
     */
    public function get_inplaceeditable() {
        return $this->inplaceeditable;
    }

}
