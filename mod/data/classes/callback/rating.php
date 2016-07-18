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
 * Callbacks for ratings API.
 *
 * @package    mod_data
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_data\callback;

use \core_rating\callback\can_see_item_ratings;
use \rating_exception;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for ratings API.
 *
 * @package    mod_data
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating {

    /**
     * Can the current user see ratings for a given itemid?
     * @param can_see_item_ratings $callback
     * @throws coding_exception
     * @throws rating_exception
     */
    public static function can_see_item_ratings(can_see_item_ratings $callback) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/rating/lib.php');
        // We build params here so that the code resembles the code before the move to the new callbacks API. (good for diffs).
        $params = [
            'component' => $callback->get_component(),
            'ratingarea' => $callback->get_ratingarea(),
            'itemid' => $callback->get_itemid(),
            'contextid' => $callback->get_contextid(),
            'scaleid' => $callback->get_scaleid(),
        ];

        // Check the component is mod_data.
        if (!isset($params['component']) || $params['component'] != 'mod_data') {
            throw new rating_exception('invalidcomponent');
        }

        // Check the ratingarea is entry (the only rating area in data).
        if (!isset($params['ratingarea']) || $params['ratingarea'] != 'entry') {
            throw new rating_exception('invalidratingarea');
        }

        if (!isset($params['itemid'])) {
            throw new rating_exception('invaliditemid');
        }

        $datasql = "SELECT d.id as dataid, d.course, r.groupid
                      FROM {data_records} r
                      JOIN {data} d ON r.dataid = d.id
                     WHERE r.id = :itemid";
        $dataparams = array('itemid' => $params['itemid']);
        if (!$info = $DB->get_record_sql($datasql, $dataparams)) {
            // Item doesn't exist.
            throw new rating_exception('invaliditemid');
        }

        // User can see ratings of all participants.
        if ($info->groupid == 0) {
            $callback->set_visible(true);
            return;
        }

        $course = $DB->get_record('course', array('id' => $info->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('data', $info->dataid, $course->id, false, MUST_EXIST);

        // Make sure groups allow this user to see the item they're rating.
        $callback->set_visible(groups_group_visible($info->groupid, $course, $cm));
        return;
    }

    /**
     * Return rating related permissions
     * @param \core_rating\callback\permissions $callback
     */
    public static function permissions(permissions $callback) {
        $context = context::instance_by_id($callback->get_contextid(), MUST_EXIST);
        if ($callback->get_component() != 'mod_data' || $callback->get_ratingarea() != 'entry') {
            return;
        }

        $callback->set_canview(has_capability('mod/data:viewrating', $context));
        $callback->set_canviewany(has_capability('mod/data:viewanyrating', $context));
        $callback->set_canviewall(has_capability('mod/data:viewallrating', $context));
        $callback->set_canrate(has_capability('mod/data:rate', $context));
    }

    /**
     * Validates a submitted rating
     * @param \core_rating\callback\validate $callback
     */
    public static function validate(validate $callback) {
        global $DB, $USER;

        // Check the component is mod_data.
        if ($callback->get_component() != 'mod_data') {
            throw new rating_exception('invalidcomponent');
        }

        // Check the ratingarea is entry (the only rating area in data module).
        if ($callback->get_ratingarea() != 'entry') {
            throw new rating_exception('invalidratingarea');
        }

        // Check the rateduserid is not the current user .. you can't rate your own entries.
        if ($callback->get_rateduserid() == $USER->id) {
            throw new rating_exception('nopermissiontorate');
        }

        $datasql = "SELECT d.id as dataid, d.scale, d.course,
                           r.userid as userid, d.approval, r.approved,
                           r.timecreated, d.assesstimestart, d.assesstimefinish, r.groupid
                    FROM {data_records} r
                    JOIN {data} d ON r.dataid = d.id
                    WHERE r.id = :itemid";
        $dataparams = array('itemid' => $callback->get_itemid());
        if (!$info = $DB->get_record_sql($datasql, $dataparams)) {
            // Item doesn't exist.
            throw new rating_exception('invaliditemid');
        }

        if ($info->scale != $callback->get_scaleid()) {
            // The scale being submitted doesnt match the one in the database.
            throw new rating_exception('invalidscaleid');
        }

        // Check that the submitted rating is valid for the scale.

        // Lower limit.
        if ($callback->get_rating() < 0  && $callback->get_rating() != RATING_UNSET_RATING) {
            throw new rating_exception('invalidnum');
        }

        // Upper limit.
        if ($info->scale < 0) {
            // Its a custom scale.
            $scalerecord = $DB->get_record('scale', array('id' => -$info->scale));
            if ($scalerecord) {
                $scalearray = explode(',', $scalerecord->scale);
                if ($callback->get_rating() > count($scalearray)) {
                    throw new rating_exception('invalidnum');
                }
            } else {
                throw new rating_exception('invalidscaleid');
            }
        } else if ($callback->get_rating() > $info->scale) {
            // If its numeric and submitted rating is above maximum.
            throw new rating_exception('invalidnum');
        }

        if ($info->approval && !$info->approved) {
            // Database requires approval but this item isnt approved.
            throw new rating_exception('nopermissiontorate');
        }

        // Check the item we're rating was created in the assessable time window.
        if (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
            if ($info->timecreated < $info->assesstimestart || $info->timecreated > $info->assesstimefinish) {
                throw new rating_exception('notavailable');
            }
        }

        $course = $DB->get_record('course', array('id' => $info->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('data', $info->dataid, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // If the supplied context doesnt match the item's context.
        if ($context->id != $callback->get_context()->id) {
            throw new rating_exception('invalidcontext');
        }

        // Make sure groups allow this user to see the item they're rating.
        $groupid = $info->groupid;
        if ($groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
            if (!groups_group_exists($groupid)) { // Can't find group.
                throw new rating_exception('cannotfindgroup');// Something is wrong.
            }

            if (!groups_is_member($groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
                // Do not allow rating of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS.
                throw new rating_exception('notmemberofgroup');
            }
        }

        $callback->set_valid(true);
    }
}
