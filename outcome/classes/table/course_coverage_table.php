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
 * Outcome course overage
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\table;

use core_outcome\form\course_coverage_filter;
use core_outcome\mod_archetype;
use core_outcome\model\filter_repository;
use core_outcome\model\outcome_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * This report shows the course content that has
 * been mapped to outcomes.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_coverage_table extends report_abstract {
    /**
     * @var mod_archetype
     */
    protected $modarchetype;

    public function __construct(course_coverage_filter $mform, mod_archetype $modarchetype) {
        parent::__construct(__CLASS__);

        $this->modarchetype = $modarchetype;

        $this->init_download(get_string('report:course_coverage_table', 'outcome'));

        $this->set_attribute('id', 'outcome-course-coverage');
        $this->set_attribute('class', 'generaltable generalbox outcome-report-table');
        $this->define_columns(array('docnum', 'description', 'resources', 'activities', 'questions'));
        $this->define_headers(array(
            get_string('id', 'outcome'),
            get_string('outcome', 'outcome'),
            get_string('resources', 'outcome'),
            get_string('activities', 'outcome'),
            get_string('questions', 'outcome'),
        ));
        $this->sortable(true, 'description');
        $this->collapsible(false);

        $this->generate_sql($mform);
    }

    protected function generate_sql(course_coverage_filter $mform) {
        global $COURSE, $DB;

        $system  = \context_system::instance();
        $context = \context_course::instance($COURSE->id);

        $activitymods = $this->modarchetype->get_mods_by_archetype(MOD_ARCHETYPE_OTHER);
        $resourcemods = $this->modarchetype->get_mods_by_archetype(MOD_ARCHETYPE_RESOURCE);

        list($activitiesinsql, $activitiesinparmas) = $DB->get_in_or_equal($activitymods, SQL_PARAMS_NAMED);
        list($resourcesinsql, $resourcesinparams) = $DB->get_in_or_equal($resourcemods, SQL_PARAMS_NAMED);
        $componentlikesql = $DB->sql_like('areas3.component', ':componentqtype');
        $contextlikesql   = $DB->sql_like('ctx.path', ':ctxpath');

        $params = array();
        $fields = array('o.id', 'o.docnum', 'o.description', 't1.resources', 't2.activities', 't3.questions', 't3.questionsused');

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

        $from = "
            {outcome} o
         $filtersql->join
          LEFT JOIN (
                    SELECT o.id, COUNT(used.id) resources
                      FROM {outcome} o
                INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
                INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
                INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
                INNER JOIN {course_modules} cm ON cm.id = used.cmid AND cm.course = :courseid
                INNER JOIN {modules} mods ON mods.id = cm.module AND mods.name $resourcesinsql
             GROUP BY o.id
          ) t1
                ON t1.id = o.id

          LEFT JOIN (
                    SELECT o.id, COUNT(DISTINCT cm2.id) activities
                      FROM {outcome} o
                INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
                INNER JOIN {outcome_areas} areas2 ON areas2.id = ao.outcomeareaid
                INNER JOIN {outcome_used_areas} used2 ON areas2.id = used2.outcomeareaid
                INNER JOIN {course_modules} cm2 ON cm2.id = used2.cmid AND cm2.course = :courseid2
                INNER JOIN {modules} mods2 ON mods2.id = cm2.module AND mods2.name $activitiesinsql
                  GROUP BY o.id
          ) t2
                ON t2.id = o.id

          LEFT JOIN (
                    SELECT o.id, COUNT(DISTINCT ao.id) questions, COUNT(DISTINCT cm3.id) questionsused
                      FROM {outcome} o
                INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
                INNER JOIN {outcome_areas} areas3 ON areas3.id = ao.outcomeareaid AND areas3.area = :typequestion
                                                     AND $componentlikesql
                INNER JOIN {question} q ON q.id = areas3.itemid
                INNER JOIN {question_categories} qc ON qc.id = q.category
                INNER JOIN {context} ctx ON (ctx.id = qc.contextid
                                             AND (ctx.id = :contextid OR $contextlikesql OR ctx.id = :systemid1))
                 LEFT JOIN {outcome_used_areas} used3 ON areas3.id = used3.outcomeareaid
                 LEFT JOIN {course_modules} cm3 ON cm3.id = used3.cmid AND cm3.course = :courseid3
                     WHERE (ctx.id != :systemid2 OR (ctx.id = :systemid3 AND cm3.id IS NOT NULL))
                  GROUP BY o.id
            ) t3
                ON t3.id = o.id
        ";

        $params['courseid']       = $params['courseid2'] = $params['courseid3'] = $COURSE->id;
        $params['componentqtype'] = 'qtype_%';
        $params['typequestion']   = 'qtype';
        $params['contextid']      = $context->id;
        $params['ctxpath']        = $context->path.'/%';
        $params['systemid1']      = $system->id;
        $params['systemid2']      = $system->id;
        $params['systemid3']      = $system->id;

        $params = array_merge($params, $activitiesinparmas, $resourcesinparams, $filterparams);
        $where  = "$filtersql->where GROUP BY o.id, t1.resources, t2.activities, t3.questions, t3.questionsused";

        $this->set_sql(implode(', ', $fields), $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM (SELECT o.id FROM {outcome} o $filtersql->join WHERE $filtersql->where $filtersql->groupby) x", $filterparams);
    }

    function wrap_html_finish() {
        echo \html_writer::tag('div', '*'.get_string('questionbanknote', 'outcome'),
                array('id' => 'outcome-coverage-notes'));
    }

    protected function panel_data($row, $action) {
        return array('data-request-outcomeid' => $row->id);
    }

    public function col_docnum($row) {
        return format_string($row->docnum);
    }

    public function col_description($row) {
        return $this->format_text($row->description);
    }

    public function col_resources($row) {
        if ($this->is_downloading()) {
            return $row->resources;
        }
        if (is_null($row->resources)) {
            $row->resources = '-';
        }
        return $this->panel_link($row, 'course_coverage_resources', $row->resources);
    }

    public function col_activities($row) {
        if ($this->is_downloading()) {
            return $row->activities;
        }
        if (is_null($row->activities)) {
            $row->activities = '-';
        }
        return $this->panel_link($row, 'course_coverage_activities', $row->activities);
    }

    public function col_questions($row) {
        if ($this->is_downloading()) {
            return $row->questions;
        }
        if (is_null($row->questions)) {
            $questions = '-';
        } else {
            $questions = $this->panel_link($row, 'course_coverage_questions', $row->questions);
            if ($row->questionsused < $row->questions) {
                $questions .= '*';
            }
        }
        return $questions;
    }
}