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
 * Outcome Set Form
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\form;

use core_outcome\model\outcome_set_repository;
use html_writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/formslib.php');

/**
 * Form for editing an outcome set and its outcomes.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_set_form extends \moodleform {
    protected function definition() {
        global $PAGE;

        $PAGE->requires->yui_module(
            'moodle-core_outcome-editoutcome',
            'M.core_outcome.init_editoutcome',
            array(array(
                'srcNode' => '#outcomeset_outcomes',
                'dataNode' => 'input[name=outcomedata]',
                'saveNode' => 'input[name=modifiedoutcomedata]'
            ))
        );
        $PAGE->requires->strings_for_js(array('addoutcome', 'add', 'edit', 'move', 'delete',
            'editx', 'movex', 'deletex', 'addchildoutcome', 'ok', 'moveoutcome', 'outcomemodified'), 'outcome');
        $PAGE->requires->strings_for_js(array('cancel'), 'moodle');

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'outcome'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'outcome'), array('size' => '40', 'maxlength' => '255'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('html', html_writer::start_tag('div',
            array('class' => 'error', 'data-errorcode' => 'outcomesetidnumberchange', 'style' => 'display:none;')));

        $mform->addElement('static', 'outcomesetidnumberchange', '', get_string('uniqueidchangewarning', 'outcome'));

        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('text', 'idnumber', get_string('idnumber', 'outcome'), array('size' => '40', 'maxlength' => '255'));
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addRule('idnumber', null, 'required', null, 'client');
        $mform->addHelpButton('idnumber', 'idnumber', 'outcome');

        $mform->addElement('textarea', 'description', get_string('description', 'outcome'), array('rows' => '5', 'cols' => '40'));
        $mform->setType('description', PARAM_TEXT);
        $mform->setAdvanced('description');

        $mform->addElement('text', 'provider', get_string('provider', 'outcome'), array('size' => '5', 'maxlength' => '255'));
        $mform->setType('provider', PARAM_TEXT);
        $mform->setAdvanced('provider');
        $mform->addHelpButton('provider', 'provider', 'outcome');

        $mform->addElement('text', 'region', get_string('region', 'outcome'), array('size' => '5', 'maxlength' => '255'));
        $mform->setType('region', PARAM_TEXT);
        $mform->setAdvanced('region');
        $mform->addHelpButton('region', 'region', 'outcome');

        $this->definition_outcomes();

        $this->add_action_buttons();
    }

    /**
     * Defines the outcome set's outcomes
     */
    protected function definition_outcomes() {
        $mform = $this->_form;

        $mform->addElement('header', 'outcomes', get_string('outcomes', 'outcome'));

        $html = html_writer::tag('p', get_string('outcomes_help', 'outcome'), array('class' => 'outcomes_help')).
            html_writer::tag('div', '', array('id' => 'outcomeset_outcomes'));

        $mform->addElement('html', $html);

        $mform->addElement('hidden', 'outcomedata');
        $mform->setType('outcomedata', PARAM_RAW_TRIMMED);

        $mform->addElement('hidden', 'modifiedoutcomedata');
        $mform->setType('modifiedoutcomedata', PARAM_RAW_TRIMMED);

        $this->definition_edit_panel();
        $this->definition_move_panel();
    }

    /**
     * Defines the HTML for the outcome edit modal
     */
    protected function definition_edit_panel() {
        $mform = $this->_form;

        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'outcome_edit_panel')));

        $mform->addElement('hidden', 'outcome_id', 0, array('id' => 'outcome_id'));
        $mform->setType('outcome_id', PARAM_INT);

        $mform->addElement('hidden', 'outcome_parentid', 0, array('id' => 'outcome_parentid'));
        $mform->setType('outcome_parentid', PARAM_INT);

        $this->define_error('outcomeidnumberrequired');
        $this->define_error('outcomeidnumbernotunique', 'idnumbernotunique');
        $this->define_error('outcomeidnumberchange', 'uniqueidchangewarning');

        $mform->addElement('text', 'outcome_idnumber', get_string('idnumber', 'outcome'),
            array('size' => '40', 'maxlength' => '255'));
        $mform->setType('outcome_idnumber', PARAM_TEXT);
        $mform->addHelpButton('outcome_idnumber', 'idnumber', 'outcome');

        $mform->addElement('text', 'outcome_docnum', get_string('docnum', 'outcome'), array('size' => '40', 'maxlength' => '255'));
        $mform->setType('outcome_docnum', PARAM_TEXT);
        $mform->addHelpButton('outcome_docnum', 'docnum', 'outcome');

        $mform->addElement('text', 'outcome_subjects', get_string('subjects', 'outcome'),
            array('size' => '40', 'maxlength' => '1333'));
        $mform->setType('outcome_subjects', PARAM_TEXT);
        $mform->addHelpButton('outcome_subjects', 'subjects', 'outcome');

        $mform->addElement('text', 'outcome_edulevels', get_string('educationlevels', 'outcome'),
            array('size' => '40', 'maxlength' => '1333'));
        $mform->setType('outcome_edulevels', PARAM_TEXT);
        $mform->addHelpButton('outcome_edulevels', 'educationlevels', 'outcome');

        $mform->addElement('checkbox', 'outcome_assessable', '', '&nbsp;'.get_string('assessable', 'outcome'));
        $mform->addHelpButton('outcome_assessable', 'assessable', 'outcome');

        $this->define_error('outcomedescriptionrequired');

        $mform->addElement('textarea', 'outcome_description',
            get_string('description', 'outcome'), array('rows' => '5', 'cols' => '40'));
        $mform->setType('outcome_description', PARAM_TEXT);

        $mform->addElement('html', html_writer::end_tag('div'));
    }

    /**
     * Defines HTML for displaying an error in a modal.
     *
     * @param string $code The error code
     * @param string $identifier The string identifier for the error message
     */
    protected function define_error($code, $identifier = '') {
        $mform = $this->_form;

        if (empty($identifier)) {
            $identifier = $code;
        }
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'fitem')));
        $mform->addElement('html', html_writer::tag('div', '', array('class' => 'fitemtitle')));
        $mform->addElement('html', html_writer::tag('div', get_string($identifier, 'outcome'),
            array('class' => 'felement error', 'data-errorcode' => $code)));

        $mform->addElement('html', html_writer::end_tag('div'));
    }

    /**
     * Defines the HTML for the outcome move modal
     */
    protected function definition_move_panel() {
        $mform = $this->_form;

        $mform->addElement('html', html_writer::start_tag('div', array('id' => 'outcome_move_panel')));

        $options = array(
            'child'  => get_string('asfirstchild', 'outcome'),
            'before' => get_string('before', 'outcome'),
            'after'  => get_string('after', 'outcome'),
        );

        $label = html_writer::label('placeholder', 'outcome_move_fieldset', false, array('class' => 'move_label'));

        $selects = html_writer::tag('legend', get_string('moveoutcomeoptslegend', 'outcome'), array('class' => 'accesshide')).
            html_writer::label(get_string('outcome_placement', 'outcome'), 'id_outcome_placement', false, array('class' => 'accesshide')).
            html_writer::select($options, 'outcome_placement', '', false, array('id' => 'id_outcome_placement')).
            html_writer::label(get_string('outcome_reference', 'outcome'), 'id_outcome_reference', false, array('class' => 'accesshide')).
            html_writer::select(array(), 'outcome_reference', '', false, array('id' => 'id_outcome_reference'));

        $fieldset = html_writer::tag('fieldset', $selects, array('id' => 'outcome_move_fieldset'));

        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'fitem')));
        $mform->addElement('html', html_writer::tag('div', $label, array('class' => 'fitemtitle')));
        $mform->addElement('html', html_writer::tag('div', $fieldset, array('class' => 'felement')));
        $mform->addElement('html', html_writer::end_tag('div'));
        $mform->addElement('html', html_writer::end_tag('div'));
    }

    public function validation($data, $files) {
        $errors   = parent::validation($data, $files);
        $repo     = new outcome_set_repository();
        $idnumber = trim($data['idnumber']);
        if (empty($idnumber)) {
            $errors['idnumber'] = get_string('err_required', 'form');
        } else if (!$repo->is_idnumber_unique($idnumber, $data['id'])) {
            $errors['idnumber'] = get_string('idnumbernotunique', 'outcome');
        }
        $name = trim($data['name']);
        if (empty($name)) {
            $errors['name'] = get_string('err_required', 'form');
        }
        return $errors;
    }
}