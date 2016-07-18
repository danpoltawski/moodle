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
 * Params for js callback
 *
 * @package    editor_atto
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace editor_atto\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Params for js callback.
 *
 * @package    editor_atto
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class params_for_js extends \core\callback\callback_with_legacy_support {

    /** @var string $elementid */
    private $elementid = '';
    /** @var array $options */
    private $options = [];
    /** @var array $fpoptions */
    private $fpoptions = [];
    /** @var array $params */
    private $params = [];

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must return a list of parameters that will be json encoded and sent to the
     * page to initialise this plugin for this instance of the editor.
     * @param array $params Named array of parameters including elementid, options and params.
     *                      options are the options suitable for using format_text for the editor, but also containing custom
     *                      behaviours like, enable_filemanagement, autosave.
     *                      fpoptions are the options for the file picker associated with this draft area.
     */
    private function __construct($params = []) {
        if (isset($params['elementid'])) {
            $this->elementid = $params['elementid'];
        }
        if (isset($params['options'])) {
            $this->options = $params['options'];
        }
        if (isset($params['fpoptions'])) {
            $this->fpoptions = $params['fpoptions'];
        }
        $this->params = [];
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params Named array of parameters including elementid, options and fpoptions.
     *                      options are the general options for the editor, containing contextid, enable_filemanagement, autosave,
     *                      fpoptions are the options for the file picker associated with this draft area.
     * @return params_for_js
     */
    public static function create($params = []) {
        return new static($params);
    }

    /**
     * Map the fields in this class to the format expected for backwards compatibility with component_callback.
     * @return mixed $args
     */
    public function get_legacy_arguments() {
        // This callback expects no arguments.
        return [$this->elementid, $this->options, $this->fpoptions];
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'params_for_js';
    }

    /**
     * Map the legacy result to the params field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->params;
    }

    /**
     * Get the elementid field.
     * @return string $elementid
     */
    public function get_elementid() {
        return $this->elementid;
    }

    /**
     * Get the options field.
     * @return array $options
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Get the fpoptions field.
     * @return array $fpoptions
     */
    public function get_fpoptions() {
        return $this->fpoptions;
    }

    /**
     * Get the result
     * @param array $params
     */
    public function set_params($params) {
        $this->params = $params;
    }

    /**
     * Get the params
     * @return array $params
     */
    public function get_params() {
        return $this->params;
    }

    /**
     * Set the params from a component_callback.
     * @param mixed $params
     */
    public function set_legacy_result($params) {
        $this->params = $params;
    }
}
