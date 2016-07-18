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
 * @package    mod_wiki
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_wiki\callback;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for print_recent_activity API.
 *
 * @package    mod_wiki
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class print_recent_activity {

    /**
     * Print the recent activity for this module.
     *
     * @param \core\callback\print_recent_activity $callback
     */
    public static function output(\core\callback\print_recent_activity $callback) {
        global $CFG, $DB, $OUTPUT;

        $course = $callback->get_course();
        $timestart = $callback->get_timestart();
        $viewfullnames = $callback->get_viewfullnames();

        $sql = "SELECT p.id, p.timemodified, p.subwikiid, sw.wikiid, w.wikimode, sw.userid, sw.groupid
                FROM {wiki_pages} p
                    JOIN {wiki_subwikis} sw ON sw.id = p.subwikiid
                    JOIN {wiki} w ON w.id = sw.wikiid
                WHERE p.timemodified > ? AND w.course = ?
                ORDER BY p.timemodified ASC";
        if (!$pages = $DB->get_records_sql($sql, array($timestart, $course->id))) {
            return;
        }
        require_once($CFG->dirroot . "/mod/wiki/locallib.php");

        $wikis = array();

        $modinfo = get_fast_modinfo($course);

        $subwikivisible = array();
        foreach ($pages as $page) {
            if (!isset($subwikivisible[$page->subwikiid])) {
                $subwiki = (object)array('id' => $page->subwikiid, 'wikiid' => $page->wikiid,
                    'groupid' => $page->groupid, 'userid' => $page->userid);
                $wiki = (object)array('id' => $page->wikiid, 'course' => $course->id, 'wikimode' => $page->wikimode);
                $subwikivisible[$page->subwikiid] = wiki_user_can_view($subwiki, $wiki);
            }
            if ($subwikivisible[$page->subwikiid]) {
                $wikis[] = $page;
            }
        }
        unset($subwikivisible);
        unset($pages);

        if (!$wikis) {
            return;
        }
        echo $OUTPUT->heading(get_string("updatedwikipages", 'wiki') . ':', 3);
        foreach ($wikis as $wiki) {
            $cm = $modinfo->instances['wiki'][$wiki->wikiid];
            $link = $CFG->wwwroot . '/mod/wiki/view.php?pageid=' . $wiki->id;
            print_recent_activity_note($wiki->timemodified, $wiki, $cm->name, $link, false, $viewfullnames);
        }

        $callback->set_hascontent(true);
    }
}
