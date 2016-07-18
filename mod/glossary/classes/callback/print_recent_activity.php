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
 * @package    mod_glossary
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_glossary\callback;

use \context_module;
use \user_picture;
use \moodle_url;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for print_recent_activity API.
 *
 * @package    mod_glossary
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
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;

        $course = $callback->get_course();
        $timestart = $callback->get_timestart();
        $viewfullnames = $callback->get_viewfullnames();

        // TODO: use timestamp in approved field instead of changing timemodified when approving in 2.0.
        if (!defined('GLOSSARY_RECENT_ACTIVITY_LIMIT')) {
            define('GLOSSARY_RECENT_ACTIVITY_LIMIT', 50);
        }
        $modinfo = get_fast_modinfo($course);
        $ids = array();

        foreach ($modinfo->cms as $cm) {
            if ($cm->modname != 'glossary') {
                continue;
            }
            if (!$cm->uservisible) {
                continue;
            }
            $ids[$cm->instance] = $cm->id;
        }

        if (!$ids) {
            return false;
        }

        // Generate list of approval capabilities for all glossaries in the course.
        $approvals = array();
        foreach ($ids as $glinstanceid => $glcmid) {
            $context = context_module::instance($glcmid);
            if (has_capability('mod/glossary:view', $context)) {
                // Get records glossary entries that are approved if user has no capability to approve entries.
                if (has_capability('mod/glossary:approve', $context)) {
                    $approvals[] = ' ge.glossaryid = :glsid'.$glinstanceid.' ';
                } else {
                    $approvals[] = ' (ge.approved = 1 AND ge.glossaryid = :glsid'.$glinstanceid.') ';
                }
                $params['glsid'.$glinstanceid] = $glinstanceid;
            }
        }

        if (count($approvals) == 0) {
            return false;
        }
        $selectsql = 'SELECT ge.id, ge.concept, ge.approved, ge.timemodified, ge.glossaryid,
                                            '.user_picture::fields('u', null, 'userid');
        $countsql = 'SELECT COUNT(*)';

        $joins = array(' FROM {glossary_entries} ge ');
        $joins[] = 'JOIN {user} u ON u.id = ge.userid ';
        $fromsql = implode($joins, "\n");

        $params['timestart'] = $timestart;
        $clausesql = ' WHERE ge.timemodified > :timestart ';

        if (count($approvals) > 0) {
            $approvalsql = 'AND ('. implode($approvals, ' OR ') .') ';
        } else {
            $approvalsql = '';
        }
        $ordersql = 'ORDER BY ge.timemodified ASC';
        $query = $selectsql.$fromsql.$clausesql.$approvalsql.$ordersql;
        $entries = $DB->get_records_sql($query, $params, 0, (GLOSSARY_RECENT_ACTIVITY_LIMIT + 1));

        if (empty($entries)) {
            return false;
        }

        echo $OUTPUT->heading(get_string('newentries', 'glossary').':', 3);
        $strftimerecent = get_string('strftimerecent');
        $entrycount = 0;
        foreach ($entries as $entry) {
            if ($entrycount < GLOSSARY_RECENT_ACTIVITY_LIMIT) {
                if ($entry->approved) {
                    $dimmed = '';
                    $urlparams = array('g' => $entry->glossaryid, 'mode' => 'entry', 'hook' => $entry->id);
                } else {
                    $dimmed = ' dimmed_text';
                    $urlparams = array('id' => $ids[$entry->glossaryid], 'mode' => 'approval', 'hook' => format_text($entry->concept, true));
                }
                $link = new moodle_url($CFG->wwwroot.'/mod/glossary/view.php' , $urlparams);
                echo '<div class="head'.$dimmed.'">';
                echo '<div class="date">'.userdate($entry->timemodified, $strftimerecent).'</div>';
                echo '<div class="name">'.fullname($entry, $viewfullnames).'</div>';
                echo '</div>';
                echo '<div class="info"><a href="'.$link.'">'.format_string($entry->concept, true).'</a></div>';
                $entrycount += 1;
            } else {
                $numnewentries = $DB->count_records_sql($countsql.$joins[0].$clausesql.$approvalsql, $params);
                echo '<div class="head"><div class="activityhead">'.get_string('andmorenewentries', 'glossary', $numnewentries - GLOSSARY_RECENT_ACTIVITY_LIMIT).'</div></div>';
                break;
            }
        }

        $callback->set_hascontent(true);
        return;
    }
}
