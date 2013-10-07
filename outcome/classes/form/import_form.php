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
 * Outcome Set Import Form
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/formslib.php');

/**
 * Form used to import an outcome set.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {
    protected function definition() {
        $mform = $this->_form;

        $options = array('' => get_string('choosedots'));
        foreach (\core_component::get_plugin_list('outcomeimport') as $plugin => $path) {
            $component = 'outcomeimport_'.$plugin;
            $options[$component] = get_string('pluginname', $component);
        }

        $mform->addElement('header', 'header', get_string('importoutcomeset', 'outcome'));

        $mform->addElement('select', 'component', get_string('importformat', 'outcome'), $options);
        $mform->setType('component', PARAM_COMPONENT);
        $mform->addRule('component', null, 'required', null, 'client');

        $mform->addElement('filepicker', 'importfile', get_string('importfile', 'outcome'));
        $mform->addRule('importfile', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('importoutcomeset', 'outcome'));
    }
}