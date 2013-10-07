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
 * Internal Service: Activity Report Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\factory;
use core_outcome\model\area_model;
use core_outcome\model\attempt_model;
use grade_item;
use SplObjectStorage;

defined('MOODLE_INTERNAL') || die();

/**
 * This helps with getting activity information for reporting.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_helper {
    /**
     * @var \moodle_database
     */
    protected $db;

    /**
     * @var factory
     */
    protected $factory;

    /**
     * @var report_helper
     */
    protected $reporthelper;

    /**
     * @param factory $factory
     * @param report_helper $reporthelper
     * @param \moodle_database $db
     */
    public function __construct(factory $factory = null,
                                report_helper $reporthelper = null,
                                \moodle_database $db = null) {
        global $DB;

        if (is_null($factory)) {
            $factory = new factory();
        }
        if (is_null($reporthelper)) {
            $reporthelper = new report_helper();
        }
        if (is_null($db)) {
            $db = $DB;
        }
        $this->db = $db;
        $this->factory = $factory;
        $this->reporthelper = $reporthelper;
    }

    /**
     * @param int $outcomeid
     * @param int $userid
     * @param int $courseid
     * @return SplObjectStorage Full of cm_info instances and course_modules_completion record as the data
     */
    public function get_activity_completion_by_user($outcomeid, $userid, $courseid) {
        $rs = $this->db->get_recordset_sql('
            SELECT cm.id cmid, comp.*
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid AND cm.completion > ? AND areas.area = ?
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.visible = ?
   LEFT OUTER JOIN {course_modules_completion} comp ON cm.id = comp.coursemoduleid AND comp.userid = ?
             WHERE o.id = ?
               AND cm.course = ?
        ', array(0, 'mod', 1, $userid, $outcomeid, $courseid));

        $modinfo    = get_fast_modinfo($courseid);
        $activities = new SplObjectStorage();
        foreach ($rs as $row) {
            $cminfo   = $modinfo->get_cm($row->cmid);
            $progress = null;

            if (!empty($row->id)) {
                unset($row->cmid);
                $progress = $row;
            }
            $activities->attach($cminfo, $progress);
        }
        $rs->close();

        return $activities;
    }

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @param int $groupid
     * @return SplObjectStorage Full of cm_info instances and course_modules_completion record as the data
     */
    public function get_activity_completion_by_course($outcomeid, $courseid, $groupid = 0) {
        $params = array(
            'completionstate' => 1,
            'completion'      => 0,
            'area'            => 'mod',
            'visible'         => 1,
            'outcomeid'       => $outcomeid,
            'courseid'        => $courseid
        );

        list($esql, $eparams) = $this->reporthelper->get_gradebook_users_sql($groupid);

        $rs = $this->db->get_recordset_sql("
            SELECT cm.id cmid,
                   ((SUM(CASE WHEN comp.completionstate >= :completionstate THEN 1 ELSE 0 END) / COUNT(cm.id)) * 100) completion
              FROM {outcome} o
                   $esql
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid AND cm.completion > :completion AND areas.area = :area
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.visible = :visible
   LEFT OUTER JOIN {course_modules_completion} comp ON cm.id = comp.coursemoduleid AND comp.userid = u.id
             WHERE o.id = :outcomeid
               AND cm.course = :courseid
          GROUP BY cm.id
        ", array_merge($params, $eparams));

        $modinfo    = get_fast_modinfo($courseid);
        $activities = new SplObjectStorage();
        foreach ($rs as $row) {
            $cminfo   = $modinfo->get_cm($row->cmid);

            unset($row->cmid);
            $progress = $row;

            $activities->attach($cminfo, $progress);
        }
        $rs->close();

        return $activities;
    }

    /**
     * @param int $outcomeid
     * @param int $userid
     * @param int $courseid
     * @return SplObjectStorage Full of cm_info instances and the user's grade_grades record as the data
     */
    public function get_activity_scales_by_user($outcomeid, $userid, $courseid) {
        global $CFG;

        require_once($CFG->libdir.'/gradelib.php');

        $rs = $this->db->get_recordset_sql('
            SELECT cm.id cmid, gi.*
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.visible = ?
        INNER JOIN {grade_items} gi ON gi.itemtype = ?
                                   AND gi.iteminstance = cm.instance
                                   AND gi.itemmodule = mods.name
                                   AND gi.itemnumber = ?
                                   AND gi.gradetype = ?
             WHERE o.id = ?
               AND cm.course = ?
        ', array(1, 'mod', 0, GRADE_TYPE_SCALE, $outcomeid, $courseid));

        $modinfo    = get_fast_modinfo($courseid);
        $activities = new SplObjectStorage();
        foreach ($rs as $row) {
            $cminfo = $modinfo->get_cm($row->cmid);

            unset($row->cmid);
            $gradeitem = new grade_item($row);
            $grade     = $gradeitem->get_grade($userid, false);
            $grade->grade_item = $gradeitem;

            $activities->attach($cminfo, $grade);
        }
        $rs->close();

        return $activities;
    }

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @param int $groupid
     * @return SplObjectStorage Keyed by cm_info instances and the avg formatted scale as the data.
     */
    public function get_activity_scales_by_course($outcomeid, $courseid, $groupid) {
        global $CFG;

        require_once($CFG->libdir.'/gradelib.php');
        list($gbusersql, $gbuserparams) = $this->reporthelper->get_gradebook_users_sql($groupid);
        $params = array(
            'itemtype' => 'mod',
            'itemnumber' => 0,
            'visible' => 1,
            'gradetype' => GRADE_TYPE_SCALE,
            'outcomeid' => $outcomeid,
            'courseid' => $courseid
        );
        $params = array_merge($params, $gbuserparams);

        $rs = $this->db->get_recordset_sql("
            SELECT cm.id cmid, AVG(gg.finalgrade) avgscale, gi.*
              FROM {outcome} o
              $gbusersql
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.visible = :visible
        INNER JOIN {grade_items} gi ON gi.itemtype = :itemtype
                                   AND gi.iteminstance = cm.instance
                                   AND gi.itemmodule = mods.name
                                   AND gi.itemnumber = :itemnumber
                                   AND gi.gradetype = :gradetype
         LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
             WHERE o.id = :outcomeid AND cm.course = :courseid
          GROUP BY cm.id, gi.id
        ", $params);

        $modinfo    = get_fast_modinfo($courseid);
        $activities = new SplObjectStorage();
        foreach ($rs as $row) {
            $cminfo = $modinfo->get_cm($row->cmid);
            $avgscale = $row->avgscale;
            unset($row->cmid, $row->avgscale);
            $gradeitem = new grade_item($row);

            if (!is_null($avgscale)) {
                $avgscale = round($avgscale);
            }
            $scalevalue = grade_format_gradevalue($avgscale, $gradeitem);

            $activities->attach($cminfo, $scalevalue);
        }
        $rs->close();

        return $activities;
    }

    /**
     * @param int $outcomeid
     * @param int $userid
     * @param int $courseid
     * @return SplObjectStorage Full of \core_outcome\area\area_info_interface instances and the attempt_model as the data
     */
    public function get_activity_attempts_by_user($outcomeid, $userid, $courseid) {
        $rs = $this->db->get_recordset_sql('
            SELECT cm.id cmid, areas.id areaid, areas.component areacomponent, areas.area areaarea, areas.itemid areaitemid, a.*
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {outcome_attempts} a ON used.id = a.outcomeusedareaid AND a.userid = ?
        INNER JOIN (
                      SELECT outcomeusedareaid, userid, MAX(timemodified) timemodified
                        FROM {outcome_attempts}
                    GROUP BY outcomeusedareaid, userid
                   ) latest ON a.outcomeusedareaid = latest.outcomeusedareaid
                           AND a.userid = latest.userid
                           AND a.timemodified = latest.timemodified
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.visible = ?
             WHERE o.id = ?
               AND cm.course = ?
        ', array($userid, 1, $outcomeid, $courseid));

        $modinfo  = get_fast_modinfo($courseid);
        $attempts = new SplObjectStorage();
        foreach ($rs as $row) {
            $cm = $modinfo->get_cm($row->cmid);

            $area            = new area_model();
            $area->id        = $row->areaid;
            $area->component = $row->areacomponent;
            $area->area      = $row->areaarea;
            $area->itemid    = $row->areaitemid;

            $areainfo = $this->factory->build_area_info($area, $cm);

            $attempt = new attempt_model();
            foreach ($row as $name => $value) {
                if (property_exists($attempt, $name)) {
                    $attempt->$name = $value;
                }
            }
            $attempts->attach($areainfo, $attempt);
        }
        return $attempts;
    }

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @param int $groupid
     * @return SplObjectStorage Keyed by \core_outcome\area\area_info_interface instances
     *                          and the aggregated attempt info as the data
     */
    public function get_activity_attempts_by_course($outcomeid, $courseid, $groupid = 0) {
        $params = array(
            'visible' => 1,
            'outcomeid' => $outcomeid,
            'courseid' => $courseid,
        );

        list($esql, $eparams) = $this->reporthelper->get_gradebook_users_sql($groupid);

        $rs = $this->db->get_recordset_sql("
            SELECT cm.id cmid, areas.id areaid, areas.component areacomponent, areas.area areaarea, areas.itemid areaitemid,
                   (SUM(a.rawgrade - a.mingrade) / SUM(a.maxgrade - a.mingrade) * 100) avegrade,
                   SUM(a.rawgrade - a.mingrade) points, SUM(a.maxgrade - a.mingrade) possiblepoints
              FROM {outcome} o
              $esql
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {outcome_attempts} a ON used.id = a.outcomeusedareaid AND a.userid = u.id
        INNER JOIN (
                      SELECT outcomeusedareaid, userid, MAX(timemodified) timemodified
                        FROM {outcome_attempts}
                    GROUP BY outcomeusedareaid, userid
                   ) latest ON a.outcomeusedareaid = latest.outcomeusedareaid
                           AND a.userid = latest.userid
                           AND a.timemodified = latest.timemodified
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.visible = :visible
             WHERE o.id = :outcomeid
               AND cm.course = :courseid
          GROUP BY areas.id, cm.id
        ", array_merge($params, $eparams));

        $modinfo  = get_fast_modinfo($courseid);
        $attempts = new SplObjectStorage();
        foreach ($rs as $row) {
            $cm = $modinfo->get_cm($row->cmid);

            $area            = new area_model();
            $area->id        = $row->areaid;
            $area->component = $row->areacomponent;
            $area->area      = $row->areaarea;
            $area->itemid    = $row->areaitemid;

            $areainfo = $this->factory->build_area_info($area, $cm);

            $aggattemptinfo = array(
                'avegrade' => $row->avegrade,
                'points' => $row->points,
                'possiblepoints' => $row->possiblepoints,
            );
            $attempts->attach($areainfo, $aggattemptinfo);
        }
        return $attempts;
    }
}
