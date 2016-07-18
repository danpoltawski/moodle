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
 * @package    atto_equation
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace atto_equation\callback;

use context_system;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for params_for_js API.
 *
 * @package    atto_equation
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
        $texexample = '$$\pi$$';

        // Format a string with the active filter set.
        // If it is modified - we assume that some sort of text filter is working in this context.
        $options = $callback->get_options();
        $result = format_text($texexample, true, $options);

        $texfilteractive = ($texexample !== $result);
        $context = $options['context'];
        if (!$context) {
            $context = context_system::instance();
        }

        // Tex example librarys.
        $library = array(
                'group1' => array(
                    'groupname' => 'librarygroup1',
                    'elements' => get_config('atto_equation', 'librarygroup1'),
                ),
                'group2' => array(
                    'groupname' => 'librarygroup2',
                    'elements' => get_config('atto_equation', 'librarygroup2'),
                ),
                'group3' => array(
                    'groupname' => 'librarygroup3',
                    'elements' => get_config('atto_equation', 'librarygroup3'),
                ),
                'group4' => array(
                    'groupname' => 'librarygroup4',
                    'elements' => get_config('atto_equation', 'librarygroup4'),
                ));

        $params = array(
            'texfilteractive' => $texfilteractive,
            'contextid' => $context->id,
            'library' => $library,
            'texdocsurl' => get_docs_url('Using_TeX_Notation')
        );
        $callback->set_params($params);
    }
}
