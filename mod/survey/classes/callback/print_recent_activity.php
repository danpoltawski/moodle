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
 * @package    mod_survey
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_survey\callback;

use \user_picture;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for print_recent_activity API.
 *
 * @package    mod_survey
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
        global $CFG, $DB, $OUTPUT;

        $course = $callback->get_course();
        $viewfullnames = $callback->get_viewfullnames();
        $timestart = $callback->get_timestart();

        $modinfo = get_fast_modinfo($course);
        $ids = array();
        foreach ($modinfo->cms as $cm) {
            if ($cm->modname != 'survey') {
                continue;
            }
            if (!$cm->uservisible) {
                continue;
            }
            $ids[$cm->instance] = $cm->instance;
        }

        if (!$ids) {
            return;
        }

        $slist = implode(',', $ids); // There should not be hundreds of glossaries in one course, right?

        $allusernames = user_picture::fields('u');
        $rs = $DB->get_recordset_sql("SELECT sa.userid, sa.survey, MAX(sa.time) AS time,
                                             $allusernames
                                        FROM {survey_answers} sa
                                        JOIN {user} u ON u.id = sa.userid
                                       WHERE sa.survey IN ($slist) AND sa.time > ?
                                    GROUP BY sa.userid, sa.survey, $allusernames
                                    ORDER BY time ASC", array($timestart));
        if (!$rs->valid()) {
            $rs->close(); // Not going to iterate (but exit), close rs.
            return;
        }

        $surveys = array();

        foreach ($rs as $survey) {
            $cm = $modinfo->instances['survey'][$survey->survey];
            $survey->name = $cm->name;
            $survey->cmid = $cm->id;
            $surveys[] = $survey;
        }
        $rs->close();

        if (!$surveys) {
            return;
        }

        echo $OUTPUT->heading(get_string('newsurveyresponses', 'survey').':', 3);
        foreach ($surveys as $survey) {
            $url = $CFG->wwwroot.'/mod/survey/view.php?id='.$survey->cmid;
            print_recent_activity_note($survey->time, $survey, $survey->name, $url, false, $viewfullnames);
        }

        $callback->set_hascontent(true);
    }
}
