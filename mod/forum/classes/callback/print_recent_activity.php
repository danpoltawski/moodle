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
 * @package    mod_forum
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\callback;

use \context_module;
use \user_picture;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for print_recent_activity API.
 *
 * @package    mod_forum
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

        // Do not use log table if possible, it may be huge and is expensive to join with other tables.
        $course = $callback->get_course();
        $timestart = $callback->get_timestart();
        $viewfullnames = $callback->get_viewfullnames();

        $allnamefields = user_picture::fields('u', null, 'duserid');
        if (!$posts = $DB->get_records_sql("SELECT p.*, f.type AS forumtype, d.forum, d.groupid,
                                                  d.timestart, d.timeend, $allnamefields
                                             FROM {forum_posts} p
                                                  JOIN {forum_discussions} d ON d.id = p.discussion
                                                  JOIN {forum} f             ON f.id = d.forum
                                                  JOIN {user} u              ON u.id = p.userid
                                            WHERE p.created > ? AND f.course = ?
                                         ORDER BY p.id ASC", array($timestart, $course->id))) { // Order by initial posting date.
             return;
        }

        $modinfo = get_fast_modinfo($course);

        $groupmodes = array();
        $cms    = array();

        $strftimerecent = get_string('strftimerecent');

        $printposts = array();
        foreach ($posts as $post) {
            if (!isset($modinfo->instances['forum'][$post->forum])) {
                // Not visible.
                continue;
            }
            $cm = $modinfo->instances['forum'][$post->forum];
            if (!$cm->uservisible) {
                continue;
            }
            $context = context_module::instance($cm->id);

            if (!has_capability('mod/forum:viewdiscussion', $context)) {
                continue;
            }

            if (!empty($CFG->forum_enabletimedposts) and $USER->id != $post->duserid
              and (($post->timestart > 0 and $post->timestart > time()) or ($post->timeend > 0 and $post->timeend < time()))) {
                if (!has_capability('mod/forum:viewhiddentimedposts', $context)) {
                    continue;
                }
            }

            // Check that the user can see the discussion.
            if (forum_is_user_group_discussion($cm, $post->groupid)) {
                $printposts[] = $post;
            }

        }
        unset($posts);

        if (!$printposts) {
            return;
        }

        echo $OUTPUT->heading(get_string('newforumposts', 'forum').':', 3);
        echo "\n<ul class='unlist'>\n";

        foreach ($printposts as $post) {
            $subjectclass = empty($post->parent) ? ' bold' : '';

            echo '<li><div class="head">'.
                   '<div class="date">'.userdate($post->modified, $strftimerecent).'</div>'.
                   '<div class="name">'.fullname($post, $viewfullnames).'</div>'.
                 '</div>';
            echo '<div class="info'.$subjectclass.'">';
            $urlbase = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$post->discussion;
            if (empty($post->parent)) {
                echo '"<a href="'.$urlbase.'">';
            } else {
                echo '"<a href="'.$urlbase.'&amp;parent='.$post->parent.'#p'.$post->id.'">';
            }
            $post->subject = break_up_long_words(format_string($post->subject, true));
            echo $post->subject;
            echo "</a>\"</div></li>\n";
        }

        echo "</ul>\n";

        $callback->set_hascontent(true);
    }
}
