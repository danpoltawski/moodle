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
 * Get item fields ratings callback.
 *
 * @package    core_rating
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_rating\callback;

use \core\callback\callback_with_legacy_support;

defined('MOODLE_INTERNAL') || die;

/**
 * Get the item table name, the item id field, and the item user field for the given rating item
 * from the related component.
 *
 * @package    core_rating
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_item_fields extends callback_with_legacy_support {

    /** @var context $context */
    private $context;
    /** @var string $component */
    private $component;
    /** @var string $ratingarea */
    private $ratingarea;
    /** @var array $items */
    private $items;
    /** @var int $scaleid */
    private $scaleid;
    /** @var string $aggregate */
    private $aggregate;

    /** @var string $itemtablename */
    private $itemtablename;
    /** @var string $itemidfield */
    private $itemidfield;
    /** @var string $itemuserfield */
    private $itemuserfield;

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must perform their visibility checks and then call "set_visible"
     * on this callback class to update the result.
     *
     * @param array $params - List of arguments including contextid, component, ratingarea, itemid and scaleid.
     */
    public function __construct($params = array()) {
        $this->context = $params['context'];
        $this->component = clean_param($params['component'], PARAM_COMPONENT);
        $this->ratingarea = clean_param($params['ratingarea'], PARAM_ALPHANUMEXT);
        $this->items = $params['items'];
        $this->scaleid = clean_param($params['scaleid'], PARAM_INT);
        $this->aggregate = clean_param($params['aggregate'], PARAM_ALPHANUMEXT);
        $this->itemtablename = null;
        $this->itemidfield = 'id';
        $this->itemuserfield = 'userid';
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - List of arguments for the constructor.
     * @return get_item_fields
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
            'context' => $this->context,
            'component' => $this->component,
            'ratingarea' => $this->ratingarea,
            'items' => $this->items,
            'scaleid' => $this->scaleid,
            'aggregate' => $this->aggregate
        );
        // The arguments are expected in a numerically indexed array.
        return array($args);
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'rating_get_item_fields';
    }

    /**
     * Map the legacy result to the itemtable fields.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return array($this->itemtablename, $this->itemidfield, $this->itemuserfield);
    }

    /**
     * Map the legacy result to the visible field.
     * @param mixed $result
     */
    public function set_legacy_result($params) {
        if (!is_array($params) || count($params) < 3) {
            throw coding_exception('callback returned invalid data');
        }
        $this->itemtablename = $params[0];
        $this->itemidfield = $params[1];
        $this->itemuserfield = $params[2];
    }

    /**
     * Get the context.
     * @return \context
     */
    public function get_context() {
        return $this->context;
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
     * Get the items
     * @return array
     */
    public function get_items() {
        return $this->items;
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
     * @param string $itemtablename
     */
    public function set_itemtablename($itemtablename) {
        $this->itemtablename = $itemtablename;
    }

    /**
     * Update the result of the callback.
     * @param string $itemidfield
     */
    public function set_itemidfield($itemidfield) {
        $this->itemidfield = $itemidfield;
    }

    /**
     * Update the result of the callback.
     * @param string $itemuserfield
     */
    public function set_itemuserfield($itemuserfield) {
        $this->itemuserfield = $itemuserfield;
    }

    /**
     * Read the result of the callback
     * @return string
     */
    public function get_itemtablename() {
        return $this->itemtablename;
    }

    /**
     * Read the result of the callback
     * @return string
     */
    public function get_itemidfield() {
        return $this->itemidfield;
    }

    /**
     * Read the result of the callback
     * @return string
     */
    public function get_itemuserfield() {
        return $this->itemuserfield;
    }
}
