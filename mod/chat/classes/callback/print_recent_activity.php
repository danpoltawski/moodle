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
 * @package    mod_chat
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_chat\callback;

use context_module;
use user_picture;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for print_recent_activity API.
 *
 * @package    mod_chat
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

        // This is approximate only, but it is really fast.
        $timeout = $CFG->chat_old_ping * 10;
        $course = $callback->get_course();
        $viewfullnames = $callback->get_viewfullnames();
        $timestart = $callback->get_timestart();

        if (!$mcms = $DB->get_records_sql("SELECT cm.id, MAX(chm.timestamp) AS lasttime
                                             FROM {course_modules} cm
                                             JOIN {modules} md        ON md.id = cm.module
                                             JOIN {chat} ch           ON ch.id = cm.instance
                                             JOIN {chat_messages} chm ON chm.chatid = ch.id
                                            WHERE chm.timestamp > ? AND ch.course = ? AND md.name = 'chat'
                                         GROUP BY cm.id
                                         ORDER BY lasttime ASC", array($timestart, $course->id))) {
             return;
        }

        $past     = array();
        $current  = array();
        $modinfo = get_fast_modinfo($course); // Reference needed because we might load the groups.

        foreach ($mcms as $cmid => $mcm) {
            if (!array_key_exists($cmid, $modinfo->cms)) {
                continue;
            }
            $cm = $modinfo->cms[$cmid];
            if (!$modinfo->cms[$cm->id]->uservisible) {
                continue;
            }

            if (groups_get_activity_groupmode($cm) != SEPARATEGROUPS
             or has_capability('moodle/site:accessallgroups', context_module::instance($cm->id))) {
                if ($timeout > time() - $mcm->lasttime) {
                    $current[] = $cm;
                } else {
                    $past[] = $cm;
                }

                continue;
            }

            // Verify groups in separate mode.
            if (!$mygroupids = $modinfo->get_groups($cm->groupingid)) {
                continue;
            }

            // Ok, last post was not for my group - we have to query db to get last message from one of my groups.
            // The only minor problem is that the order will not be correct.
            $mygroupids = implode(',', $mygroupids);

            if (!$mcm = $DB->get_record_sql("SELECT cm.id, MAX(chm.timestamp) AS lasttime
                                               FROM {course_modules} cm
                                               JOIN {chat} ch           ON ch.id = cm.instance
                                               JOIN {chat_messages_current} chm ON chm.chatid = ch.id
                                              WHERE chm.timestamp > ? AND cm.id = ? AND
                                                    (chm.groupid IN ($mygroupids) OR chm.groupid = 0)
                                           GROUP BY cm.id", array($timestart, $cm->id))) {
                 continue;
            }

            $mcms[$cmid]->lasttime = $mcm->lasttime;
            if ($timeout > time() - $mcm->lasttime) {
                $current[] = $cm;
            } else {
                $past[] = $cm;
            }
        }

        if (!$past and !$current) {
            return;
        }

        $strftimerecent = get_string('strftimerecent');

        if ($past) {
            echo $OUTPUT->heading(get_string("pastchats", 'chat').':', 3);

            foreach ($past as $cm) {
                $link = $CFG->wwwroot.'/mod/chat/view.php?id='.$cm->id;
                $date = userdate($mcms[$cm->id]->lasttime, $strftimerecent);
                echo '<div class="head"><div class="date">'.$date.'</div></div>';
                echo '<div class="info"><a href="'.$link.'">'.format_string($cm->name, true).'</a></div>';
            }
        }

        if ($current) {
            echo $OUTPUT->heading(get_string("currentchats", 'chat').':', 3);

            $oldest = floor((time() - $CFG->chat_old_ping) / 10) * 10;  // Better db caching.

            $timeold    = time() - $CFG->chat_old_ping;
            $timeold    = floor($timeold / 10) * 10;  // Better db caching.
            $timeoldext = time() - ($CFG->chat_old_ping * 10); // JSless gui_basic needs much longer timeouts.
            $timeoldext = floor($timeoldext / 10) * 10;  // Better db caching.

            $params = array('timeold' => $timeold, 'timeoldext' => $timeoldext, 'cmid' => $cm->id);

            $timeout = "AND ((chu.version<>'basic' AND chu.lastping>:timeold)
                                OR (chu.version='basic' AND chu.lastping>:timeoldext))";

            foreach ($current as $cm) {
                // Count users first.
                $mygroupids = $modinfo->groups[$cm->groupingid];
                if (!empty($mygroupids)) {
                    list($subquery, $subparams) = $DB->get_in_or_equal($mygroupids, SQL_PARAMS_NAMED, 'gid');
                    $params += $subparams;
                    $groupselect = "AND (chu.groupid $subquery OR chu.groupid = 0)";
                } else {
                    $groupselect = "";
                }

                $userfields = user_picture::fields('u');
                $users = $DB->get_records_sql("SELECT $userfields
                                                      FROM {course_modules} cm
                                                      JOIN {chat} ch        ON ch.id = cm.instance
                                                      JOIN {chat_users} chu ON chu.chatid = ch.id
                                                      JOIN {user} u         ON u.id = chu.userid
                                                     WHERE cm.id = :cmid $timeout $groupselect
                                                  GROUP BY $userfields", $params);

                $link = $CFG->wwwroot.'/mod/chat/view.php?id='.$cm->id;
                $date = userdate($mcms[$cm->id]->lasttime, $strftimerecent);

                echo '<div class="head"><div class="date">'.$date.'</div></div>';
                echo '<div class="info"><a href="'.$link.'">'.format_string($cm->name, true).'</a></div>';
                echo '<div class="userlist">';
                if ($users) {
                    echo '<ul>';
                    foreach ($users as $user) {
                        echo '<li>'.fullname($user, $viewfullnames).'</li>';
                    }
                    echo '</ul>';
                }
                echo '</div>';
            }
        }

        $callback->set_hascontent(true);
    }
}
