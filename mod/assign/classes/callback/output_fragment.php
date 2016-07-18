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
 * Callbacks for output_fragment API.
 *
 * @package    mod_assign
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_assign\callback;

use assign;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for output_fragment API.
 *
 * @package    mod_assign
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class output_fragment {

    /**
     * Render a fragment of HTML and return it to javascript.
     *
     * @param \core\callback\output_fragment $callback
     * @throws coding_exception
     */
    public static function get_html(\core\callback\output_fragment $callback) {
        global $CFG;

        $args = $callback->get_args();
        $context = $args['context'];

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $assign = new assign($context, null, null);

        $userid = clean_param($args['userid'], PARAM_INT);
        $attemptnumber = clean_param($args['attemptnumber'], PARAM_INT);
        $formdata = array();
        if (!empty($args['jsonformdata'])) {
            $serialiseddata = json_decode($args['jsonformdata']);
            parse_str($serialiseddata, $formdata);
        }
        $viewargs = array(
            'userid' => $userid,
            'attemptnumber' => $attemptnumber,
            'formdata' => $formdata
        );

        $callback->set_html($assign->view('gradingpanel', $viewargs));
    }
}
