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
 * Outcome marking
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\table;

use core_outcome\form\marking_filter;
use core_outcome\model\filter_repository;
use core_outcome\model\outcome_repository;
use html_writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/grade/constants.php');

/**
 * Shows an individual user's performance against outcomes
 * and allows another user to mark outcomes as earned or not.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class marking_table extends report_abstract {
    /**
     * Current user being marked
     *
     * @var int
     */
    protected $userid;

    public function __construct(marking_filter $mform) {
        global $COURSE;

        parent::__construct(__CLASS__);

        $this->init_download(get_string('report:marking_table', 'outcome'));

        $this->userid = $mform->get_cached_value('userid');

        $columns = array('docnum', 'description', 'completion', 'avegrade', 'scales', 'complete');

        $completion = new \completion_info($COURSE);
        if (!$completion->is_enabled()) {
            unset($columns[array_search('completion', $columns)]);
        }
        if ($this->is_downloading()) {
            unset($columns[array_search('scales', $columns)]);
        }
        $this->set_attribute('id', 'outcome-marking');
        $this->set_attribute('class', 'generaltable generalbox outcome-report-table');
        $this->define_columns($columns);
        $this->sortable(true, 'description');
        $this->no_sorting('scales');
        $this->no_sorting('complete');
        $this->collapsible(false);

        $this->generate_sql($mform, $completion);
    }

    protected function generate_sql(marking_filter $mform, \completion_info $completion) {
        global $COURSE;

        $params = array();
        $fields = array('o.id', 'o.docnum', 'o.description',
            '((SUM(a.rawgrade) - SUM(a.mingrade)) / (SUM(a.maxgrade) - SUM(a.mingrade)) * 100) avegrade',
            'MIN(gi.gradetype) scales', 'm.id markid', 'm.result markresult');

        $outcomerepo = new outcome_repository();
        $filterrepo  = new filter_repository();

        $filter = $filterrepo->find_one_by(array(
            'outcomesetid' => $mform->get_cached_value('outcomesetid'),
            'courseid'     => $COURSE->id,
        ));
        list($filtersql, $filterparams) = $outcomerepo->filter_to_sql($filter);

        // Filter down to assessable outcomes.
        $filtersql->where .= ' AND o.deleted = :deleted AND o.assessable = :assessable';
        $filterparams['deleted']    = 0;
        $filterparams['assessable'] = 1;

        if ($completion->is_enabled()) {
            // This field is number of completed activities divided by total number of activities that can be completed.
            // And we group these by the outcomes that are mapped to the activities.
            $fields[]      = '((SUM(CASE WHEN comp.completionstate >= :completioncomplete THEN 1 ELSE 0 END)'.
                ' / COUNT(cmcomp.id)) * 100) completion';
            $completionsql = 'LEFT OUTER JOIN {course_modules} cmcomp ON cmcomp.id = used.cmid '.
                'AND cmcomp.completion > :completionenabled AND areas.area = :completionarea '.
                'LEFT OUTER JOIN {course_modules_completion} comp ON cmcomp.id = comp.coursemoduleid '.
                'AND comp.userid = :completionuserid';

            $params['completioncomplete'] = COMPLETION_COMPLETE;
            $params['completionenabled'] = 0;
            $params['completionarea']    = 'mod';
            $params['completionuserid']  = $this->userid;
        } else {
            $completionsql = '';
        }

        $from = "
            {outcome} o
         $filtersql->join
         LEFT OUTER JOIN {outcome_marks} m ON o.id = m.outcomeid AND m.courseid = :courseid AND m.userid = :markuserid
         LEFT OUTER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
         LEFT OUTER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
         LEFT OUTER JOIN ({outcome_used_areas} used INNER JOIN
                          {course_modules} acm ON used.cmid = acm.id AND acm.course = :courseid2 INNER JOIN
                          {modules} amods ON amods.id = acm.module AND amods.visible = :modvisible
                          ) ON areas.id = used.outcomeareaid
         LEFT OUTER JOIN (
                          {outcome_attempts} a INNER JOIN (
                            SELECT outcomeusedareaid, userid, MAX(timemodified) timemodified
                              FROM {outcome_attempts}
                          GROUP BY outcomeusedareaid, userid
                         ) latest ON a.outcomeusedareaid = latest.outcomeusedareaid
                                 AND a.userid = latest.userid
                                 AND a.timemodified = latest.timemodified
                         ) ON used.id = a.outcomeusedareaid AND a.userid = :attemptuserid
         LEFT OUTER JOIN {course_modules} cmscale ON cmscale.id = used.cmid
         LEFT OUTER JOIN {modules} mods ON mods.id = cmscale.module
         LEFT OUTER JOIN {grade_items} gi ON gi.itemtype = :itemtype AND gi.iteminstance = cmscale.instance
                                             AND gi.itemmodule = mods.name AND gi.itemnumber = :itemnumber
                                             AND gi.gradetype = :gradetype
         $completionsql
        ";

        $params['courseid']      = $COURSE->id;
        $params['courseid2']     = $COURSE->id;
        $params['modvisible']    = 1;
        $params['markuserid']    = $this->userid;
        $params['attemptuserid'] = $this->userid;
        $params['itemtype']      = 'mod';
        $params['itemnumber']    = 0;
        $params['gradetype']     = GRADE_TYPE_SCALE;

        $this->set_sql(implode(', ', $fields), $from, "$filtersql->where GROUP BY o.id, m.id", array_merge($params, $filterparams));
        $this->set_count_sql("SELECT COUNT(1) FROM (SELECT o.id FROM {outcome} o $filtersql->join WHERE $filtersql->where $filtersql->groupby) x", $filterparams);
    }

    /**
     * Based on columns, define default headers
     *
     * @param array $columns
     */
    function define_columns($columns) {
        parent::define_columns($columns);

        $allheaders = array(
            'docnum'      => get_string('id', 'outcome'),
            'description' => get_string('outcome', 'outcome'),
            'completion'  => get_string('completion', 'outcome'),
            'avegrade'    => get_string('averagegrade', 'outcome'),
            'scales'      => get_string('scaleitems', 'outcome'),
            'complete'    => get_string('complete', 'outcome'),
        );

        $headers = array();
        foreach ($columns as $column) {
            if (array_key_exists($column, $allheaders)) {
                $headers[] = $allheaders[$column];
            }
        }
        $this->define_headers($headers);
    }

    protected function panel_data($row, $action) {
        return array(
            'data-request-userid'    => $this->userid,
            'data-request-outcomeid' => $row->id,
        );
    }

    public function col_docnum($row) {
        return format_string($row->docnum);
    }

    public function col_description($row) {
        return $this->format_text($row->description);
    }

    public function col_completion($row) {
        return $this->panel_link($row, 'user_completion', $this->format_percentage($row->completion));
    }

    public function col_avegrade($row) {
        return $this->panel_link($row, 'user_attempts', $this->format_percentage($row->avegrade));
    }

    public function col_scales($row) {
        if ($row->scales == GRADE_TYPE_SCALE) {
            return $this->panel_link($row, 'user_scales', get_string('viewitems', 'outcome'));
        }
        return '-';
    }

    public function col_complete($row) {
        if ($this->is_downloading()) {
            if (!empty($row->markid) and !empty($row->markresult)) {
                return '1';
            }
            return '0';
        }
        $id = html_writer::random_id('mark');
        if (!empty($row->markid)) {
            $input = html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'markids[]', 'value' => $row->markid)).
                   html_writer::checkbox('earnedmarkids[]', $row->markid, !empty($row->markresult), '', array('id' => $id));
        } else {
            $input = html_writer::checkbox('outcomeids[]', $row->id, false, '', array('id' => $id));
        }
        return $input.html_writer::label(get_string('markasearned', 'outcome'), $id, false, array('class' => 'accesshide'));
    }

    function wrap_html_start() {
        if ($this->is_downloading()) {
            return;
        }
        /** @var $url \moodle_url */
        $url = clone($this->baseurl);
        $url->param('sesskey', sesskey());
        $url->param('savemarkings', 1);

        echo html_writer::start_tag('div', array('class' => 'outcome-marking-wrapper'));
        echo html_writer::start_tag('form', array('action' => $url->out_omit_querystring(), 'method' => 'post'));
        echo html_writer::input_hidden_params($url);
    }

    function wrap_html_finish() {
        if ($this->is_downloading()) {
            return;
        }
        echo html_writer::start_tag('div', array('class' => 'outcome-marking-submit'));
        echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('savechanges')));
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('form');
        echo html_writer::end_tag('div');
    }
}