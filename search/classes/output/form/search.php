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
 * Global Search search form definition
 *
 * @package   core_search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_search\output\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class search extends \moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('header', 'search', get_string('search', 'search'));

        // Help info depends on the selected search engine.
        $mform->addElement('text', 'queryfield', get_string('enteryoursearchquery', 'search'));
        $mform->addHelpButton('queryfield', 'searchinfo', $this->_customdata['searchengine']);
        $mform->setType('queryfield', PARAM_TEXT);
        $mform->addRule('queryfield', get_string('required'), 'required', null, 'client');

        $mform->addElement('header', 'filterquerysection', get_string('filterqueryheader', 'search'));
        $mform->setExpanded('filterquerysection', false);

        $mform->addElement('text', 'title', get_string('title', 'search'));
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('text', 'author', get_string('author', 'search'));
        $mform->setType('author', PARAM_TEXT);

        $search = \core_search\manager::instance();

        $searchcomponents = \core_search\manager::get_search_components_list(true);
        $componentnames = array('' => get_string('allcomponents', 'search'));
        foreach ($searchcomponents as $key => $componentsearch) {
            $componentnames[$key] = $componentsearch->get_component_visible_name();
        }
        $mform->addElement('select', 'component', get_string('component', 'search'), $componentnames);

        $mform->addElement('date_time_selector', 'timestart', get_string('fromtime', 'search'), array('optional' => true));
        $mform->setDefault('timestart', 0);

        $mform->addElement('date_time_selector', 'timeend', get_string('totime', 'search'), array('optional' => true));
        $mform->setDefault('timeend', 0);

        $this->add_action_buttons(false, get_string('search', 'search'));
    }
}
