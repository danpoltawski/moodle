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
 * Permissions ratings callback.
 *
 * @package    core_rating
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_rating\callback;

use \core\callback\callback_with_legacy_support;

defined('MOODLE_INTERNAL') || die;

/**
 * Permissions ratings callback.
 *
 * @package    core_rating
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permissions extends callback_with_legacy_support {

    /** @var int $contextid */
    private $contextid;
    /** @var string $component */
    private $component;
    /** @var string $ratingarea */
    private $ratingarea;

    /** @var bool $canrate */
    private $canrate;
    /** @var bool $canview */
    private $canview;
    /** @var bool $canviewany */
    private $canviewany;
    /** @var bool $canviewall */
    private $canviewall;

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
        $this->canrate = false;
        $this->canview = false;
        $this->canviewany = false;
        $this->canviewall = false;
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - List of arguments for the constructor.
     * @return permissions
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
            'ratingarea' => $this->ratingarea
        );
        // The arguments are expected in a numerically indexed array.
        return array($args);
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'rating_permissions';
    }

    /**
     * Return an array of permissions.
     * @return array $result
     */
    public function get_permissions_array() {
        return array(
            'rate' => $this->canrate,
            'view' => $this->canview,
            'viewany' => $this->canviewany,
            'viewall' => $this->canviewall
        );
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->get_permissions_array();
    }

    /**
     * Map the legacy result to the visible field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        $this->canrate = $result['rate'];
        $this->canview = $result['view'];
        $this->canviewany = $result['viewany'];
        $this->canviewall = $result['viewall'];
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
     * Update the result of the callback.
     * @param bool $canrate
     */
    public function set_canrate($canrate) {
        $this->canrate = $canrate;
    }

    /**
     * Getter
     * @return bool
     */
    public function canrate() {
        return $this->canrate;
    }

    /**
     * Update the result of the callback.
     * @param bool $canview
     */
    public function set_canview($canview) {
        $this->canview = $canview;
    }

    /**
     * Getter
     * @return bool
     */
    public function canview() {
        return $this->canview;
    }

    /**
     * Update the result of the callback.
     * @param bool $canviewall
     */
    public function set_canviewall($canviewall) {
        $this->canviewall = $canviewall;
    }

    /**
     * Getter
     * @return bool
     */
    public function canviewall() {
        return $this->canviewall;
    }

    /**
     * Update the result of the callback.
     * @param bool $canviewany
     */
    public function set_canviewany($canviewany) {
        $this->canviewany = $canviewany;
    }

    /**
     * Getter
     * @return bool
     */
    public function canviewany() {
        return $this->canviewany;
    }

}
