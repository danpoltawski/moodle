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
 * Outcome Reports Controller
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\controller;

use core_outcome\factory;
use core_outcome\form\course_coverage_filter;
use core_outcome\form\course_performance_filter;
use core_outcome\form\marking_filter;
use core_outcome\mod_archetype;
use core_outcome\service\award_service;
use core_outcome\service\mark_helper;
use core_outcome\service\report_helper;
use core_outcome\table\course_coverage_table;
use core_outcome\table\course_outcome_sets_table;
use core_outcome\table\course_performance_table;
use core_outcome\table\marking_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles actions regarding reporting.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_controller extends controller_abstract {
    /**
     * @var award_service
     */
    protected $awardservice;

    /**
     * @var mark_helper
     */
    protected $markhelper;

    /**
     * @var report_helper
     */
    protected $reporthelper;

    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     * @throws \coding_exception
     */
    public function require_capability($action) {
        global $PAGE;

        switch($action) {
            case 'default':
            case 'report_marking_table':
            case 'report_course_performance_table':
            case 'report_course_coverage_table':
                require_capability('moodle/grade:edit', $PAGE->context);
                break;
            case 'report_course_unmapped_table':
                require_capability('moodle/outcome:mapoutcomes', $PAGE->context);
                break;
            default:
                throw new \coding_exception("Missing capability check for $action action");
        }
    }

    public function init($action) {
        parent::init($action);
        $this->awardservice = new award_service();
        $this->reporthelper = new report_helper();
        $this->markhelper   = new mark_helper();
    }

    /**
     * Overview of course outcome sets and their reports, etc.
     */
    public function default_action() {
        global $PAGE, $COURSE, $OUTPUT;

        add_to_log($COURSE->id, 'outcome', 'view course outcome sets', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('outcomesetsforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('outcomesets', 'outcome'));

        $table = new course_outcome_sets_table();
        $table->define_baseurl($this->new_url());

        echo $OUTPUT->heading(get_string('outcomesets', 'outcome'));
        $this->renderer->course_outcome_sets($table);
    }

    /**
     * Outcome marking table.
     */
    public function report_marking_table_action() {
        global $PAGE, $COURSE, $USER, $OUTPUT;

        $outcomesetid = optional_param('forceoutcomesetid', 0, PARAM_INT);

        add_to_log($COURSE->id, 'outcome', 'view outcome marking', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('completionmarkingforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('report:marking_table', 'outcome'));

        $mform = new marking_filter($this->new_url());
        if (!empty($outcomesetid)) {
            $mform->set_data(array('outcomesetid' => $outcomesetid));
        }
        $mform->handle_submit();

        if (optional_param('savemarkings', 0, PARAM_BOOL)) {
            require_sesskey();

            $outcomeids    = optional_param_array('outcomeids', array(), PARAM_INT);
            $markids       = optional_param_array('markids', array(), PARAM_INT);
            $earnedmarkids = optional_param_array('earnedmarkids', array(), PARAM_INT);
            $userid        = $mform->get_cached_value('userid');

            $updated = array_merge(
                $this->markhelper->mark_outcomes_as_earned($COURSE->id, $USER->id, $userid, $outcomeids),
                $this->markhelper->update_mark_earned($USER->id, $markids, $earnedmarkids)
            );
            $errors = $this->awardservice->update_awards_by_marks($updated);
            foreach ($errors as $error) {
                $this->flashmessages->add_string($error);
            }
            $this->flashmessages->good('changessaved', null, 'moodle');
            redirect($this->new_url());
        }

        $table = new marking_table($mform);
        $table->define_baseurl($this->new_url());

        if ($this->reporthelper->download_report($table)) {
            return;
        }
        echo $OUTPUT->heading(get_string('report:marking_table', 'outcome'));
        $this->renderer->outcome_marking($mform, $table);
    }

    /**
     * Outcomes course performance table.
     */
    public function report_course_performance_table_action() {
        global $PAGE, $COURSE, $OUTPUT;

        $outcomesetid = optional_param('forceoutcomesetid', 0, PARAM_INT);

        add_to_log($COURSE->id, 'outcome', 'view course outcome performance', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('outcomeperformanceforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('report:course_performance_table', 'outcome'));

        $mform = new course_performance_filter($this->new_url());
        if (!empty($outcomesetid)) {
            $mform->set_data(array('outcomesetid' => $outcomesetid));
        }
        $mform->handle_submit();

        $table = new course_performance_table($mform, $this->reporthelper);
        $table->define_baseurl($this->new_url());

        if ($this->reporthelper->download_report($table)) {
            return;
        }
        echo $OUTPUT->heading(get_string('report:course_performance_table', 'outcome'));
        $this->renderer->outcome_course_performance($mform, $table);
    }

    /**
     * Outcomes course coverage table.
     */
    public function report_course_coverage_table_action() {
        global $PAGE, $COURSE, $OUTPUT;

        $outcomesetid = optional_param('forceoutcomesetid', 0, PARAM_INT);

        add_to_log($COURSE->id, 'outcome', 'view course outcome coverage', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('outcomecoverageforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('report:course_coverage_table', 'outcome'));

        $mform = new course_coverage_filter($this->new_url());
        if (!empty($outcomesetid)) {
            $mform->set_data(array('outcomesetid' => $outcomesetid));
        }
        $mform->handle_submit();

        $table = new course_coverage_table($mform, new mod_archetype());
        $table->define_baseurl($this->new_url());

        if ($this->reporthelper->download_report($table)) {
            return;
        }
        echo $OUTPUT->heading(get_string('report:course_coverage_table', 'outcome'));
        $this->renderer->outcome_course_coverage($mform, $table);
    }

    /**
     * Outcomes course unmapped content tables.
     */
    public function report_course_unmapped_table_action() {
        global $PAGE, $COURSE, $OUTPUT;

        add_to_log($COURSE->id, 'outcome', 'view course outcome unmapped', 'course.php?contextid='.$PAGE->context->id);

        $PAGE->set_title(get_string('outcomeunmappedforx', 'outcome', format_string($COURSE->fullname)));
        $PAGE->navbar->add(get_string('report:course_unmapped_table', 'outcome'));

        $factory = new factory();
        $coverages = $factory->build_coverages();

        echo $OUTPUT->heading(get_string('report:course_unmapped_table', 'outcome'));
        foreach ($coverages as $coverage) {
            $coverage->set_courseid($COURSE->id);
            $this->renderer->outcome_course_unmapped($coverage);
        }
    }
}