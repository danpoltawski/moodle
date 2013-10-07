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
 * Internal Service: Coverage Report Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\factory;
use core_outcome\mod_archetype;
use core_outcome\model\area_model;

defined('MOODLE_INTERNAL') || die();

/**
 * Helps with getting converage information for reports.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coverage_helper {
    /**
     * @var \moodle_database
     */
    protected $db;

    /**
     * @var factory
     */
    protected $factory;

    /**
     * @var mod_archetype
     */
    protected $modarchetypes;

    /**
     * @param factory $factory
     * @param mod_archetype $modarchetype
     * @param \moodle_database $db
     */
    public function __construct(factory $factory = null,
                                mod_archetype $modarchetype = null,
                                \moodle_database $db = null) {
        global $DB;

        if (is_null($factory)) {
            $factory = new factory();
        }
        if (is_null($modarchetype)) {
            $modarchetype = new mod_archetype();
        }
        if (is_null($db)) {
            $db = $DB;
        }
        $this->db            = $db;
        $this->factory       = $factory;
        $this->modarchetypes = $modarchetype;
    }

    /**
     * @param $outcomeid
     * @param $courseid
     * @return \core_outcome\area\area_info_interface[]
     */
    public function get_coverage_activities($outcomeid, $courseid) {
        return $this->get_coverage_mods($outcomeid, $courseid, MOD_ARCHETYPE_OTHER);
    }

    /**
     * @param $outcomeid
     * @param $courseid
     * @return \core_outcome\area\area_info_interface[]
     */
    public function get_coverage_resources($outcomeid, $courseid) {
        return $this->get_coverage_mods($outcomeid, $courseid, MOD_ARCHETYPE_RESOURCE);
    }

    /**
     * @param $outcomeid
     * @param $courseid
     * @return \SplObjectStorage Keyed by \core_outcome\area\area_info_interface instances
     *                           and if the question is used by an activity as data.
     */
    public function get_coverage_questions($outcomeid, $courseid) {
        $system           = \context_system::instance();
        $coursecontext    = \context_course::instance($courseid);
        $componentlikesql = $this->db->sql_like('areas.component', ':componentqtype');
        $contextlikesql   = $this->db->sql_like('ctx.path', ':ctxpath');

        $rs = $this->db->get_recordset_sql("
            SELECT areas.id areaid, areas.component areacomponent, areas.area areaarea, areas.itemid areaitemid, MAX(cm.id) cmid
              FROM {outcome_area_outcomes} ao
        INNER JOIN {outcome} o ON ao.outcomeid = o.id
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid AND areas.area = :qtype
                                         AND $componentlikesql
        INNER JOIN {question} q ON q.id = areas.itemid
        INNER JOIN {question_categories} qc ON qc.id = q.category
        INNER JOIN {context} ctx ON (ctx.id = qc.contextid AND (ctx.id = :contextid OR $contextlikesql OR ctx.id = :systemid1))
         LEFT JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
         LEFT JOIN {course_modules} cm ON cm.id = used.cmid AND cm.course = :courseid
             WHERE ao.outcomeid = :outcomeid
               AND (ctx.id != :systemid2 OR (ctx.id = :systemid3 AND cm.id IS NOT NULL))
          GROUP BY areas.id
        ", array('componentqtype' => 'qtype_%', 'qtype' => 'qtype', 'outcomeid' => $outcomeid,
                 'courseid'       => $courseid, 'contextid' => $coursecontext->id,
                 'ctxpath'        => $coursecontext->path.'/%', 'systemid1' => $system->id,
                 'systemid2'      => $system->id, 'systemid3' => $system->id));

        $questions = new \SplObjectStorage();
        foreach ($rs as $row) {
            $area            = new area_model();
            $area->id        = $row->areaid;
            $area->component = $row->areacomponent;
            $area->area      = $row->areaarea;
            $area->itemid    = $row->areaitemid;

            $questions->attach($this->factory->build_area_info($area), !is_null($row->cmid));
        }
        return $questions;
    }

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @param int $modtype
     * @return \core_outcome\area\area_info_interface[]
     */
    protected function get_coverage_mods($outcomeid, $courseid, $modtype) {
        $activitymods = $this->modarchetypes->get_mods_by_archetype($modtype);
        list($activitiesinsql, $activitiesinparmas) = $this->db->get_in_or_equal($activitymods, SQL_PARAMS_NAMED);

        $params = array_merge(array(
            'outcomeid' => $outcomeid,
            'courseid'  => $courseid,
        ), $activitiesinparmas);

        $rs = $this->db->get_recordset_sql("
            SELECT areas.id areaid, areas.component areacomponent, areas.area areaarea, areas.itemid areaitemid, cm.id cmid
              FROM {outcome_area_outcomes} ao
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.name $activitiesinsql
             WHERE ao.outcomeid = :outcomeid AND cm.course = :courseid
        ", $params);

        $modinfo   = get_fast_modinfo($courseid);
        $areainfos = array();
        foreach ($rs as $row) {
            $cm = $modinfo->get_cm($row->cmid);

            $area            = new area_model();
            $area->id        = $row->areaid;
            $area->component = $row->areacomponent;
            $area->area      = $row->areaarea;
            $area->itemid    = $row->areaitemid;

            $areainfos[] = $this->factory->build_area_info($area, $cm);
        }

        return $areainfos;
    }
}
