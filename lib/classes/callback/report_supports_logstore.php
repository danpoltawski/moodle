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
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Supports logstore callback.
 *
 * @package    core
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_supports_logstore extends callback_with_legacy_support {

    /** @var \tool_log\log\store $logstore */
    private $logstore;
    /** @var array $componentssupported */
    private $componentssupported = [];

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback must perform their visibility checks and then call "set_visible"
     * on this callback class to update the result.
     *
     * @param array $params - List of arguments including contextid, component, ratingarea, itemid and scaleid.
     */
    private function __construct($params = []) {
        // TODO: This is a broken API. \tool_log\log\store should be defined in core - not in a plugin.
        if (!$params['logstore'] instanceof \tool_log\log\store) {
            throw new coding_exception('logstore parameter must be an instance of \\tool_log\\log\\store.');
        }
        $this->logstore = $params['logstore'];
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - List of arguments including logstore.
     * @return supports_logstore
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
            $this->logstore,
        ];
        // The arguments are expected in a numerically indexed array.
        return $args;
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'supports_logstore';
    }

    /**
     * Map the legacy result to the visible field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return !empty($this->componentssupported[$this->get_called_component()]);
    }

    /**
     * Map the legacy result to the visible field.
     * @param mixed $result
     */
    public function set_legacy_result($result) {
        if ($result) {
            $this->componentssupported[$this->get_called_component()] = true;
        }
    }

    /**
     * Get the logstore
     * @return \tool_log\log\store
     */
    public function get_logstore() {
        return $this->logstore;
    }

    /**
     * Get the list of supported reports for this logstore.
     * @return array of componentnames
     */
    public function get_supported_components() {
        return array_keys($this->componentssupported);
    }

    /**
     * Set the supported flag for a report.
     * @param bool $supported
     */
    public function set_supported($supported) {
        if ($supported) {
            $this->componentssupported[$this->get_called_component()] = true;
        } else {
            unset($this->componentssupported[$this->get_called_component()]);
        }
    }

    /**
     * Return true if the last component that recieved this callback supported the logstore.
     * Only use this if you just called dispatch() direct to a component.
     * @return bool
     */
    public function is_supported() {
        return !empty($this->componentssupported[$this->get_called_component()]);
    }
}
