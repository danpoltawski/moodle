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
 * Callbacks for print_recent_activity API.
 *
 * @package    mod_assign
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_assign\callback;

use \context_module;
use \user_picture;
use \assign;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for print_recent_activity API.
 *
 * @package    mod_assign
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class print_recent_activity {

    /**
     * Print the recent activity for this module.
     *
     * @param \core\callback\print_recent_activity $callback
     * @throws coding_exception
     */
    public static function output(\core\callback\print_recent_activity $callback) {
        global $CFG, $USER, $DB, $OUTPUT;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        // Get params.
        $course = $callback->get_course();
        $viewfullnames = $callback->get_viewfullnames();
        $timestart = $callback->get_timestart();

        // Do not use log table if possible, it may be huge.

        $dbparams = array($timestart, $course->id, 'assign', ASSIGN_SUBMISSION_STATUS_SUBMITTED);
        $namefields = user_picture::fields('u', null, 'userid');
        if (!$submissions = $DB->get_records_sql("SELECT asb.id, asb.timemodified, cm.id AS cmid,
                                                         $namefields
                                                    FROM {assign_submission} asb
                                                         JOIN {assign} a      ON a.id = asb.assignment
                                                         JOIN {course_modules} cm ON cm.instance = a.id
                                                         JOIN {modules} md        ON md.id = cm.module
                                                         JOIN {user} u            ON u.id = asb.userid
                                                   WHERE asb.timemodified > ? AND
                                                         asb.latest = 1 AND
                                                         a.course = ? AND
                                                         md.name = ? AND
                                                         asb.status = ?
                                                ORDER BY asb.timemodified ASC", $dbparams)) {
             return;
        }

        $modinfo = get_fast_modinfo($course);
        $show    = array();
        $grader  = array();

        $showrecentsubmissions = get_config('assign', 'showrecentsubmissions');

        foreach ($submissions as $submission) {
            if (!array_key_exists($submission->cmid, $modinfo->get_cms())) {
                continue;
            }
            $cm = $modinfo->get_cm($submission->cmid);
            if (!$cm->uservisible) {
                continue;
            }
            if ($submission->userid == $USER->id) {
                $show[] = $submission;
                continue;
            }

            $context = context_module::instance($submission->cmid);
            // The act of submitting of assignment may be considered private -
            // only graders will see it if specified.
            if (empty($showrecentsubmissions)) {
                if (!array_key_exists($cm->id, $grader)) {
                    $grader[$cm->id] = has_capability('moodle/grade:viewall', $context);
                }
                if (!$grader[$cm->id]) {
                    continue;
                }
            }

            $groupmode = groups_get_activity_groupmode($cm, $course);

            if ($groupmode == SEPARATEGROUPS &&
                    !has_capability('moodle/site:accessallgroups',  $context)) {
                if (isguestuser()) {
                    // Shortcut - guest user does not belong into any group.
                    continue;
                }

                // This will be slow - show only users that share group with me in this cm.
                if (!$modinfo->get_groups($cm->groupingid)) {
                    continue;
                }
                $usersgroups = groups_get_all_groups($course->id, $submission->userid, $cm->groupingid);
                if (is_array($usersgroups)) {
                    $usersgroups = array_keys($usersgroups);
                    $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                    if (empty($intersect)) {
                        continue;
                    }
                }
            }
            $show[] = $submission;
        }

        if (empty($show)) {
            return;
        }

        echo $OUTPUT->heading(get_string('newsubmissions', 'assign').':', 3);

        foreach ($show as $submission) {
            $cm = $modinfo->get_cm($submission->cmid);
            $context = context_module::instance($submission->cmid);
            $assign = new assign($context, $cm, $cm->course);
            $link = $CFG->wwwroot.'/mod/assign/view.php?id='.$cm->id;
            // Obscure first and last name if blind marking enabled.
            if ($assign->is_blind_marking()) {
                $submission->firstname = get_string('participant', 'mod_assign');
                $submission->lastname = $assign->get_uniqueid_for_user($submission->userid);
            }
            print_recent_activity_note($submission->timemodified,
                                       $submission,
                                       $cm->name,
                                       $link,
                                       false,
                                       $viewfullnames);
        }

        $callback->set_hascontent(true);
    }
}
