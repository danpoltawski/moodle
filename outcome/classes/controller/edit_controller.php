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
 * Outcome Set Controller
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\controller;

use core_outcome\form\outcome_set_form;
use core_outcome\model\outcome_repository;
use core_outcome\model\outcome_set_model;
use core_outcome\model\outcome_set_repository;
use core_outcome\normalizer;
use core_outcome\service\outcome_helper;
use core_outcome\service\outcome_set_helper;
use core_outcome\table\manage_outcome_sets_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles actions regarding outcome set editing.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_controller extends controller_abstract {
    /**
     * @var normalizer
     */
    public $normalizer;

    /**
     * @var outcome_repository
     */
    public $outcomes;

    /**
     * @var outcome_set_repository
     */
    public $outcomesets;

    /**
     * @var outcome_helper
     */
    public $outcomehelper;

    /**
     * @var outcome_set_helper
     */
    public $outcomesethelper;

    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     */
    public function require_capability($action) {
        global $PAGE;

        require_capability('moodle/outcome:edit', $PAGE->context);
    }

    public function init($action) {
        parent::init($action);
        $this->normalizer       = new normalizer();
        $this->outcomes         = new outcome_repository();
        $this->outcomesets      = new outcome_set_repository();
        $this->outcomehelper    = new outcome_helper($this->outcomes);
        $this->outcomesethelper = new outcome_set_helper($this->outcomesets);
    }

    /**
     * Administer all outcome sets at the site level
     */
    public function outcomeset_action() {
        global $COURSE;

        add_to_log($COURSE->id, 'outcome', 'view outcome sets', 'admin.php?action=outcomeset');

        $table = new manage_outcome_sets_table();
        $table->define_baseurl($this->new_url());

        $this->renderer->outcome_sets_admin($table);
    }

    /**
     * Editing an outcome set
     */
    public function outcomeset_edit_action() {
        global $PAGE, $COURSE;

        $PAGE->set_title(get_string('editingoutcomeset', 'outcome'));
        $PAGE->navbar->add(get_string('editingoutcomeset', 'outcome'));

        $outcomesetid = optional_param('outcomesetid', 0, PARAM_INT);

        $returnurl = $this->new_url(array('action' => 'outcomeset'));
        $formurl   = $this->new_url(array('action' => 'outcomeset_edit', 'outcomesetid' => $outcomesetid));
        $mform     = new outcome_set_form($formurl);

        if (!empty($outcomesetid)) {
            $outcomeset = $this->outcomesets->find($outcomesetid, MUST_EXIST);
        } else {
            $outcomeset = new outcome_set_model();
        }
        $outcomes = $this->outcomes->find_by_outcome_set($outcomeset, true);

        if ($mform->is_cancelled()) {
            redirect($returnurl);
        } else if ($data = $mform->get_data()) {
            // Save the outcome set and outcomes.
            $this->outcomesethelper->save_outcome_set_form_data($outcomeset, $data);
            $this->outcomehelper->save_outcome_form_data($outcomeset, $outcomes, $data);
            $this->outcomehelper->fix_sort_order($outcomeset);

            // Report success to user, log and redirect.
            $this->flashmessages->good('changessavedtox', format_string($outcomeset->name));
            add_to_log($COURSE->id, 'outcome', 'edit outcome set', 'admin.php?action=outcomeset', $outcomeset->id);
            redirect($returnurl);
        }
        if (!empty($outcomesetid)) {
            $normalized = $this->normalizer->normalize_outcomes($outcomes, true);

            $mform->set_data(get_object_vars($outcomeset));
            $mform->set_data(array('outcomedata' => json_encode($normalized)));
        }
        $mform->display();
    }

    /**
     * Delete an outcome set
     */
    public function outcomeset_delete_action() {
        global $COURSE;

        require_sesskey();

        $outcomesetid = required_param('outcomesetid', PARAM_INT);

        $outcomeset = $this->outcomesets->find($outcomesetid, MUST_EXIST);
        $this->outcomesets->remove($outcomeset);

        $restoreurl = $this->new_url(array(
            'action'       => 'outcomeset_restore',
            'sesskey'      => sesskey(),
            'outcomesetid' => $outcomeset->id
        ));
        $this->flashmessages->good('outcomesetdeleted', array(
            'name' => format_string($outcomeset->name),
            'undo' => \html_writer::link($restoreurl, get_string('undo', 'outcome')),
        ));

        add_to_log($COURSE->id, 'outcome', 'delete outcome set', 'admin.php?action=outcomeset', $outcomeset->id);

        redirect($this->new_url(array('action' => 'outcomeset')));
    }

    /**
     * Restore a previously deleted outcome set
     */
    public function outcomeset_restore_action() {
        global $COURSE;

        require_sesskey();

        $outcomesetid = required_param('outcomesetid', PARAM_INT);

        $outcomeset = $this->outcomesets->find($outcomesetid, MUST_EXIST);
        $this->outcomesets->restore($outcomeset);

        $this->flashmessages->good('outcomesetrestored', format_string($outcomeset->name));

        add_to_log($COURSE->id, 'outcome', 'restore outcome set', 'admin.php?action=outcomeset', $outcomeset->id);

        redirect($this->new_url(array('action' => 'outcomeset')));
    }
}