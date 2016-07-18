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
 * @package    mod_workshop
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_workshop\callback;

use \context_module;
use \core_collator;
use \moodle_url;
use \stdclass;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for print_recent_activity API.
 *
 * @package    mod_workshop
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

        $course = $callback->get_course();
        $viewfullnames = $callback->get_viewfullnames();
        $timestart = $callback->get_timestart();

        $authoramefields = get_all_user_name_fields(true, 'author', null, 'author');
        $reviewerfields = get_all_user_name_fields(true, 'reviewer', null, 'reviewer');

        $sql = "SELECT s.id AS submissionid, s.title AS submissiontitle, s.timemodified AS submissionmodified,
                       author.id AS authorid, $authoramefields, a.id AS assessmentid, a.timemodified AS assessmentmodified,
                       reviewer.id AS reviewerid, $reviewerfields, cm.id AS cmid
                  FROM {workshop} w
            INNER JOIN {course_modules} cm ON cm.instance = w.id
            INNER JOIN {modules} md ON md.id = cm.module
            INNER JOIN {workshop_submissions} s ON s.workshopid = w.id
            INNER JOIN {user} author ON s.authorid = author.id
             LEFT JOIN {workshop_assessments} a ON a.submissionid = s.id
             LEFT JOIN {user} reviewer ON a.reviewerid = reviewer.id
                 WHERE cm.course = ?
                       AND md.name = 'workshop'
                       AND s.example = 0
                       AND (s.timemodified > ? OR a.timemodified > ?)
              ORDER BY s.timemodified";

        $rs = $DB->get_recordset_sql($sql, array($course->id, $timestart, $timestart));

        $modinfo = get_fast_modinfo($course); // Reference needed because we might load the groups.

        $submissions = array(); // Recent submissions indexed by submission id.
        $assessments = array(); // Recent assessments indexed by assessment id.
        $users       = array();

        foreach ($rs as $activity) {
            if (!array_key_exists($activity->cmid, $modinfo->cms)) {
                // This should not happen but just in case.
                continue;
            }

            $cm = $modinfo->cms[$activity->cmid];
            if (!$cm->uservisible) {
                continue;
            }

            // Remember all user names we can use later.
            if (empty($users[$activity->authorid])) {
                $u = new stdclass();
                $users[$activity->authorid] = username_load_fields_from_object($u, $activity, 'author');
            }
            if ($activity->reviewerid and empty($users[$activity->reviewerid])) {
                $u = new stdclass();
                $users[$activity->reviewerid] = username_load_fields_from_object($u, $activity, 'reviewer');
            }

            $context = context_module::instance($cm->id);
            $groupmode = groups_get_activity_groupmode($cm, $course);

            if ($activity->submissionmodified > $timestart and empty($submissions[$activity->submissionid])) {
                $s = new stdclass();
                $s->title = $activity->submissiontitle;
                $s->authorid = $activity->authorid;
                $s->timemodified = $activity->submissionmodified;
                $s->cmid = $activity->cmid;
                if ($activity->authorid == $USER->id || has_capability('mod/workshop:viewauthornames', $context)) {
                    $s->authornamevisible = true;
                } else {
                    $s->authornamevisible = false;
                }

                // The following do-while wrapper allows to break from deeply nested if-statements.
                do {
                    if ($s->authorid === $USER->id) {
                        // Own submissions always visible.
                        $submissions[$activity->submissionid] = $s;
                        break;
                    }

                    if (has_capability('mod/workshop:viewallsubmissions', $context)) {
                        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                            if (isguestuser()) {
                                // Shortcut - guest user does not belong into any group.
                                break;
                            }

                            // This might be slow - show only submissions by users who share group with me in this cm.
                            if (!$modinfo->get_groups($cm->groupingid)) {
                                break;
                            }
                            $authorsgroups = groups_get_all_groups($course->id, $s->authorid, $cm->groupingid);
                            if (is_array($authorsgroups)) {
                                $authorsgroups = array_keys($authorsgroups);
                                $intersect = array_intersect($authorsgroups, $modinfo->get_groups($cm->groupingid));
                                if (empty($intersect)) {
                                    break;
                                } else {
                                    // Can see all submissions and shares a group with the author.
                                    $submissions[$activity->submissionid] = $s;
                                    break;
                                }
                            }

                        } else {
                            // Can see all submissions from all groups.
                            $submissions[$activity->submissionid] = $s;
                        }
                    }
                } while (0);
            }

            if ($activity->assessmentmodified > $timestart and empty($assessments[$activity->assessmentid])) {
                $a = new stdclass();
                $a->submissionid = $activity->submissionid;
                $a->submissiontitle = $activity->submissiontitle;
                $a->reviewerid = $activity->reviewerid;
                $a->timemodified = $activity->assessmentmodified;
                $a->cmid = $activity->cmid;
                if ($activity->reviewerid == $USER->id || has_capability('mod/workshop:viewreviewernames', $context)) {
                    $a->reviewernamevisible = true;
                } else {
                    $a->reviewernamevisible = false;
                }

                // The following do-while wrapper allows to break from deeply nested if-statements.
                do {
                    if ($a->reviewerid === $USER->id) {
                        // Own assessments always visible.
                        $assessments[$activity->assessmentid] = $a;
                        break;
                    }

                    if (has_capability('mod/workshop:viewallassessments', $context)) {
                        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                            if (isguestuser()) {
                                // Shortcut - guest user does not belong into any group.
                                break;
                            }

                            // This might be slow - show only submissions by users who share group with me in this cm.
                            if (!$modinfo->get_groups($cm->groupingid)) {
                                break;
                            }
                            $reviewersgroups = groups_get_all_groups($course->id, $a->reviewerid, $cm->groupingid);
                            if (is_array($reviewersgroups)) {
                                $reviewersgroups = array_keys($reviewersgroups);
                                $intersect = array_intersect($reviewersgroups, $modinfo->get_groups($cm->groupingid));
                                if (empty($intersect)) {
                                    break;
                                } else {
                                    // Can see all assessments and shares a group with the reviewer.
                                    $assessments[$activity->assessmentid] = $a;
                                    break;
                                }
                            }

                        } else {
                            // Can see all assessments from all groups.
                            $assessments[$activity->assessmentid] = $a;
                        }
                    }
                } while (0);
            }
        }
        $rs->close();

        $shown = false;

        if (!empty($submissions)) {
            $shown = true;
            echo $OUTPUT->heading(get_string('recentsubmissions', 'workshop'), 3);
            foreach ($submissions as $id => $submission) {
                $link = new moodle_url('/mod/workshop/submission.php', array('id' => $id, 'cmid' => $submission->cmid));
                if ($submission->authornamevisible) {
                    $author = $users[$submission->authorid];
                } else {
                    $author = null;
                }
                print_recent_activity_note($submission->timemodified, $author, $submission->title,
                                           $link->out(), false, $viewfullnames);
            }
        }

        if (!empty($assessments)) {
            $shown = true;
            echo $OUTPUT->heading(get_string('recentassessments', 'workshop'), 3);
            core_collator::asort_objects_by_property($assessments, 'timemodified');
            foreach ($assessments as $id => $assessment) {
                $link = new moodle_url('/mod/workshop/assessment.php', array('asid' => $id));
                if ($assessment->reviewernamevisible) {
                    $reviewer = $users[$assessment->reviewerid];
                } else {
                    $reviewer = null;
                }
                print_recent_activity_note($assessment->timemodified, $reviewer, $assessment->submissiontitle,
                                           $link->out(), false, $viewfullnames);
            }
        }

        $callback->set_hascontent($shown);
    }
}
