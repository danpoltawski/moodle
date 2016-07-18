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
 * Callbacks for params_for_js API.
 *
 * @package    atto_managefiles
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace atto_managefiles\callback;

use context_user;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for params_for_js API.
 *
 * @package    atto_managefiles
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class params_for_js {

    /**
     * Get the params for this component.
     *
     * @param \editor_atto\callback\params_for_js $callback
     * @throws coding_exception
     */
    public static function get_params(\editor_atto\callback\params_for_js $callback) {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/repository/lib.php');  // Load constants.

        $options = $callback->get_options();
        // Disabled if:
        // - Not logged in or guest.
        // - Files are not allowed.
        // - Only URL are supported.
        $disabled = !isloggedin() || isguestuser() ||
                (!isset($options['maxfiles']) || $options['maxfiles'] == 0) ||
                (isset($options['return_types']) && !($options['return_types'] & ~FILE_EXTERNAL));

        $params = array('disabled' => $disabled, 'area' => array(), 'usercontext' => null);

        if (!$disabled) {
            $params['usercontext'] = context_user::instance($USER->id)->id;
            foreach (array('itemid', 'context', 'areamaxbytes', 'maxbytes', 'subdirs', 'return_types') as $key) {
                if (isset($options[$key])) {
                    if ($key === 'context' && is_object($options[$key])) {
                        // Just context id is enough.
                        $params['area'][$key] = $options[$key]->id;
                    } else {
                        $params['area'][$key] = $options[$key];
                    }
                }
            }
        }

        $callback->set_params($params);
    }
}
