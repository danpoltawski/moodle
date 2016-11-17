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
 * Custom password-like form element for storing share secrets. Allows
 * revealing of the password with unmask option. Doesn't display password
 * element by default (to prevent problems with password managers).
 *
 * @package   core_form
 * @copyright 2016 Dan Poltawski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/form/password.php');

/**
 * Custom password-like form element for storing share secrets. Allows
 * revealing of the password with unmask option. Doesn't display password
 * element by default (to prevent problems with password managers).
 *
 * @package   core_form
 * @copyright 2016 Dan Poltawski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_sharedsecret extends MoodleQuickForm_password {
    /**
     * constructor
     *
     * @param string $elementName (optional) name of the password element
     * @param string $elementLabel (optional) label for password element
     * @param mixed $attributes (optional) Either a typical HTML attribute string
     *              or an associative array
     */
    public function __construct($elementName=null, $elementLabel=null, $attributes=null) {
        if (empty($attributes)) {
            $attributes = ['autocomplete' => 'off'];
        } else if (is_array($attributes)) {
            $attributes['autocomplete'] = 'off';
        } else {
            if (strpos($attributes, 'autocomplete') === false) {
                $attributes .= ' autocomplete="off" ';
            }
        }
        $this->_persistantFreeze = true;

        parent::__construct($elementName, $elementLabel, $attributes);
        $this->setType('sharedsecret');
    }

    /**
     * Function to export the renderer data in a format that is suitable for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $context = parent::export_for_template($output);
        $context['valuechars'] = array_fill(0, strlen($context['value']), 'x');

        return $context;
    }
}
