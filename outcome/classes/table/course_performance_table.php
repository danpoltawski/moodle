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
 * Outcome course performance
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\table;

use core_outcome\form\course_performance_filter;
use core_outcome\model\filter_repository;
use core_outcome\model\outcome_repository;
use core_outcome\service\report_helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/grade/constants.php');

/**
 * Shows user performance against outcomes at the
 * course level.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_performance_table extends report_abstract {
    /**
     * Group being filtered on.
     *
     * @var int
     */
    protected $groupid;

    /**
     * @var report_helper
     */
    protected $reporthelper;

    public function __construct(course_performance_filter $mform, report_helper $reporthelper) {
        global $COURSE;

        parent::__construct(__CLASS__);

        $this->init_download(get_string('report:course_performance_table', 'outcome'));

        $this->groupid = $mform->get_cached_value('groupid');
        $this->reporthelper = $reporthelper;

        $columns    = array('docnum', 'description', 'completion', 'avegrade', 'scales', 'activities');
        $completion = new \completion_info($COURSE);
        if (!$completion->is_enabled()) {
            unset($columns[array_search('completion', $columns)]);
        }
        if ($this->is_downloading()) {
            unset($columns[array_search('scales', $columns)]);
        }
        $this->set_attribute('id', 'outcome-course-performance');
        $this->set_attribute('class', 'generaltable generalbox outcome-report-table');
        $this->define_columns($columns);
        $this->sortable(true, 'description');
        $this->no_sorting('scales');
        $this->no_sorting('activities');
        $this->collapsible(false);

        $this->generate_sql($mform, $completion);
    }

    protected function generate_sql(course_performance_filter $mform, \completion_info $completion) {
        global $COURSE;

        $params = array();
        $fields = array('o.id', 'o.docnum', 'o.description',
            '((SUM(a.rawgrade) - SUM(a.mingrade)) / (SUM(a.maxgrade) - SUM(a.mingrade)) * 100) avegrade',
            'MIN(scale.gradetype) scales', 'COUNT(DISTINCT used.cmid) activities');

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

        list($enrolledsql, $enrolledparams) = $this->reporthelper->get_gradebook_users_sql($this->groupid);

        if ($completion->is_enabled()) {
            // This field is number of completed activities divided by total number of activities that can be completed.
            // And we group these by the outcomes that are mapped to the activities.
            $fields[]      = '((SUM(CASE WHEN comp.completionstate >= :completioncomplete THEN 1 ELSE 0 END)'.
                ' / COUNT(cmcomp.id)) * 100) completion';
            $completionsql = 'LEFT OUTER JOIN {course_modules} cmcomp ON cmcomp.id = used.cmid '.
                'AND cmcomp.completion > :completionenabled AND areas.area = :completionarea '.
                'LEFT OUTER JOIN {course_modules_completion} comp ON cmcomp.id = comp.coursemoduleid AND comp.userid = u.id';

            $params['completioncomplete'] = COMPLETION_COMPLETE;
            $params['completionenabled'] = 0;
            $params['completionarea']    = 'mod';
        } else {
            $completionsql = '';
        }

        $from = "
            {outcome} o
         $filtersql->join
                    $enrolledsql
         LEFT OUTER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
         LEFT OUTER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
         LEFT OUTER JOIN ({outcome_used_areas} used INNER JOIN
                          {course_modules} acm ON used.cmid = acm.id AND acm.course = :courseid INNER JOIN
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
                         ) ON used.id = a.outcomeusedareaid AND a.userid = u.id
         LEFT OUTER JOIN (
                            SELECT cmscale.id cmid, gi.gradetype
                              FROM {course_modules} cmscale
                              JOIN {modules} mods ON mods.id = cmscale.module
                              JOIN {grade_items} gi ON gi.itemtype = :itemtype AND gi.iteminstance = cmscale.instance
                                                    AND gi.itemmodule = mods.name AND gi.itemnumber = :itemnumber
                                                    AND gi.gradetype = :gradetype AND gi.courseid = :courseid2
                         ) scale ON scale.cmid = used.cmid
         $completionsql
        ";

        $params['courseid']   = $COURSE->id;
        $params['courseid2']  = $COURSE->id;
        $params['modvisible'] = 1;
        $params['itemtype']   = 'mod';
        $params['itemnumber'] = 0;
        $params['gradetype']  = GRADE_TYPE_SCALE;

        $params = array_merge($params, $enrolledparams, $filterparams);

        $this->set_sql(implode(', ', $fields), $from, "$filtersql->where GROUP BY o.id", $params);
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
            'activities'  => '',
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
            'data-request-groupid'   => $this->groupid,
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
        return $this->panel_link($row, 'course_completion', $this->format_percentage($row->completion));
    }

    public function col_avegrade($row) {
        return $this->panel_link($row, 'course_attempts', $this->format_percentage($row->avegrade));
    }

    public function col_scales($row) {
        if ($row->scales == GRADE_TYPE_SCALE) {
            return $this->panel_link($row, 'course_scales', get_string('viewitems', 'outcome'));
        }
        return '-';
    }

    public function col_activities($row) {
        if ($this->is_downloading()) {
            return $row->activities;
        }
        if (!empty($row->activities)) {
            return $this->panel_link($row, 'course_performance_associated_content',
                get_string('associatedcontentx', 'outcome', $row->activities));
        }
        return '-';
    }
}