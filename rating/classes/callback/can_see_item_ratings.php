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
 * Can see item ratings callback.
 *
 * @package    core_rating
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_rating\callback;

use \core\callback\callback_with_legacy_support;

defined('MOODLE_INTERNAL') || die;

/**
 * Can see item ratings callback.
 *
 * @package    core_rating
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class can_see_item_ratings extends callback_with_legacy_support {

    /** @var int $contextid */
    private $contextid;
    /** @var string $component */
    private $component;
    /** @var string $ratingarea */
    private $ratingarea;
    /** @var int $itemid */
    private $itemid;
    /** @var int $scaleid */
    private $scaleid;
    /** @var bool $visible */
    private $visible;

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must perform their visibility checks and then call "set_visible"
     * on this callback class to update the result.
     *
     * @param array $params - List of arguments including contextid, component, ratingarea, itemid and scaleid.
     */
    public function __construct($params = array()) {
        $this->contextid = clean_param($params['contextid'], PARAM_INT);
        $this->component = clean_param($params['component'], PARAM_COMPONENT);
        $this->ratingarea = clean_param($params['ratingarea'], PARAM_ALPHANUMEXT);
        $this->itemid = clean_param($params['itemid'], PARAM_INT);
        $this->scaleid = clean_param($params['scaleid'], PARAM_INT);
        $this->visible = false;
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - List of arguments for the constructor.
     * @return can_see_item_ratings
     */
    public static function create($params = []) {
        return new static($params);
    }

    /**
     * Map the fields in this class to the format expected for backwards compatibility with component_callback.
     * @return mixed $args
     */
    public function get_legacy_arguments() {
        $args = array(
            'contextid' => $this->contextid,
            'component' => $this->component,
            'ratingarea' => $this->ratingarea,
            'itemid' => $this->itemid,
            'scaleid' => $this->scaleid
        );
        // The arguments are expected in a numerically indexed array.
        return array($args);
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'rating_can_see_item_ratings';
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->visible;
    }

    /**
     * Map the legacy result to the visible field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        $this->visible = $result;
    }

    /**
     * Get the context id.
     * @return int
     */
    public function get_contextid() {
        return $this->contextid;
    }

    /**
     * Get the component
     * @return string
     */
    public function get_component() {
        return $this->component;
    }

    /**
     * Get the ratingarea
     * @return string
     */
    public function get_ratingarea() {
        return $this->ratingarea;
    }

    /**
     * Get the itemid
     * @return int
     */
    public function get_itemid() {
        return $this->itemid;
    }

    /**
     * Get the scaleid
     * @return int
     */
    public function get_scaleid() {
        return $this->scaleid;
    }

    /**
     * Update the result of the callback.
     * @param bool $visible
     */
    public function set_visible($visible) {
        $this->visible = $visible;
    }

    /**
     * Get the result of the visiblity check.
     * @return bool
     */
    public function is_visible() {
        return $this->visible;
    }

}
