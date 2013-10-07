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
 * Internal Service: Report Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

defined('MOODLE_INTERNAL') || die();

/**
 * Helps with running reports.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_helper {
    /**
     * @var \moodle_database
     */
    protected $db;

    /**
     * @param \moodle_database $db
     */
    public function __construct(\moodle_database $db = null) {
        global $DB;

        if (is_null($db)) {
            $db = $DB;
        }
        $this->db = $db;
    }

    /**
     * Handle the downloading of tables
     *
     * @param \table_sql $table
     * @return bool
     */
    public function download_report(\table_sql $table) {
        if ($table->is_downloading()) {
            // Purge output buffers, we don't want any of it.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            // Note: at time of comment, this exits the script.  Doesn't seem 100% guaranteed though.
            $table->out(50, false);
            return true;
        }
        return false;
    }

    /**
     * @param int $outcomeid
     * @param int $courseid
     * @return \cm_info[]
     */
    public function get_performance_associated_content($outcomeid, $courseid) {
        $params = array(
            'visible' => 1,
            'outcomeid' => $outcomeid,
            'courseid' => $courseid,
        );

        $rs = $this->db->get_recordset_sql("
            SELECT cm.id cmid
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} areas ON areas.id = ao.outcomeareaid
        INNER JOIN {outcome_used_areas} used ON areas.id = used.outcomeareaid
        INNER JOIN {course_modules} cm ON cm.id = used.cmid
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.visible = :visible
             WHERE o.id = :outcomeid AND cm.course = :courseid
          GROUP BY cm.id
        ", $params);

        $content = array();
        $modinfo  = get_fast_modinfo($courseid);
        foreach ($rs as $row) {
            $cm = $modinfo->get_cm($row->cmid);

            $content[$cm->id] = $cm;
        }
        return $content;
    }

    public function get_gradebook_users_sql($groupid = 0) {
        global $CFG, $DB, $PAGE;

        // Ensure we have course context.
        $context = \context_course::instance($PAGE->course->id);

        list($gsql, $rparams) = $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED, 'grbr');
        list($csql, $cparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');

        $eparams  = array('uedeleted' => 0, 'ueguestid' => $CFG->siteguest, 'uecourseid' => $PAGE->course->id);
        $groupsql = '';
        if (!empty($groupid)) {
            $groupsql = "JOIN {groups_members} gm ON (gm.userid = u.id AND gm.groupid = :uegroupid)";
            $eparams['uegroupid'] = $groupid;
        }
        $sql = "INNER JOIN {user} u ON 1 = 1
                INNER JOIN (
                    SELECT DISTINCT u.id
                      FROM {user} u
                      JOIN {user_enrolments} ue ON ue.userid = u.id
                      JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :uecourseid)
                      JOIN {role_assignments} ra ON ra.userid = u.id
                      $groupsql
                     WHERE u.deleted = :uedeleted
                       AND u.id <> :ueguestid
                       AND ra.roleid $gsql
                       AND ra.contextid $csql
                ) e ON e.id = u.id";

        return array($sql, array_merge($eparams, $rparams, $cparams));
    }
}
