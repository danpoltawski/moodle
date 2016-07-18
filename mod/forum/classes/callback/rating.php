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
 * @package    mod_forum
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\callback;

use core_rating\callback\can_see_item_ratings;
use core_rating\callback\permissions;
use core_rating\callback\validate;
use rating_exception;
use context;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for ratings API.
 *
 * @package    mod_forum
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
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/rating/lib.php');
        // We build params here so that the code resembles the code before the move to the new callbacks API. (good for diffs).
        $params = [
            'component' => $callback->get_component(),
            'ratingarea' => $callback->get_ratingarea(),
            'itemid' => $callback->get_itemid(),
            'contextid' => $callback->get_contextid(),
            'scaleid' => $callback->get_scaleid(),
        ];

        // Check the component is mod_forum.
        if (!isset($params['component']) || $params['component'] != 'mod_forum') {
            throw new rating_exception('invalidcomponent');
        }

        // Check the ratingarea is post (the only rating area in forum).
        if (!isset($params['ratingarea']) || $params['ratingarea'] != 'post') {
            throw new rating_exception('invalidratingarea');
        }

        if (!isset($params['itemid'])) {
            throw new rating_exception('invaliditemid');
        }

        $post = $DB->get_record('forum_posts', array('id' => $params['itemid']), '*', MUST_EXIST);
        $discussion = $DB->get_record('forum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $forum->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id , false, MUST_EXIST);

        // Perform some final capability checks.
        $callback->set_visible(forum_user_can_see_post($forum, $discussion, $post, $USER, $cm));
    }

    /**
     * Return rating related permissions
     * @param \core_rating\callback\permissions $callback
     */
    public static function permissions(permissions $callback) {
        $context = context::instance_by_id($callback->get_contextid(), MUST_EXIST);
        if ($callback->get_component() != 'mod_forum' || $callback->get_ratingarea() != 'post') {
            // We don't know about this component/ratingarea so just return to get the
            // default restrictive permissions.
            return;
        }

        $callback->set_canview(has_capability('mod/forum:viewrating', $context));
        $callback->set_canviewany(has_capability('mod/forum:viewanyrating', $context));
        $callback->set_canviewall(has_capability('mod/forum:viewallratings', $context));
        $callback->set_canrate(has_capability('mod/forum:rate', $context));
    }

    /**
     * Validates a submitted rating
     * @param \core_rating\callback\validate $callback
     */
    public static function validate(validate $callback) {
        global $DB, $USER;

        // Check the component is mod_forum.
        if ($callback->get_component() != 'mod_forum') {
            throw new rating_exception('invalidcomponent');
        }

        // Check the ratingarea is post (the only rating area in forum).
        if ($callback->get_ratingarea() != 'post') {
            throw new rating_exception('invalidratingarea');
        }

        // Check the rateduserid is not the current user .. you can't rate your own posts.
        if ($callback->get_rateduserid() == $USER->id) {
            throw new rating_exception('nopermissiontorate');
        }

        // Fetch all the related records ... we need to do this anyway to call forum_user_can_see_post.
        $post = $DB->get_record('forum_posts', array('id' => $callback->get_itemid(), 'userid' => $callback->get_rateduserid()), '*', MUST_EXIST);
        $discussion = $DB->get_record('forum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $forum->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id , false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Make sure the context provided is the context of the forum.
        if ($context->id != $callback->get_context()->id) {
            throw new rating_exception('invalidcontext');
        }

        if ($forum->scale != $callback->get_scaleid()) {
            // The scale being submitted doesnt match the one in the database.
            throw new rating_exception('invalidscaleid');
        }

        // Check the item we're rating was created in the assessable time window.
        if (!empty($forum->assesstimestart) && !empty($forum->assesstimefinish)) {
            if ($post->created < $forum->assesstimestart || $post->created > $forum->assesstimefinish) {
                throw new rating_exception('notavailable');
            }
        }

        // Check that the submitted rating is valid for the scale.

        // Lower limit.
        if ($callback->get_rating() < 0  && $callback->get_rating() != RATING_UNSET_RATING) {
            throw new rating_exception('invalidnum');
        }

        // Upper limit.
        if ($forum->scale < 0) {
            // Its a custom scale.
            $scalerecord = $DB->get_record('scale', array('id' => -$forum->scale));
            if ($scalerecord) {
                $scalearray = explode(',', $scalerecord->scale);
                if ($callback->get_rating() > count($scalearray)) {
                    throw new rating_exception('invalidnum');
                }
            } else {
                throw new rating_exception('invalidscaleid');
            }
        } else if ($callback->get_rating() > $forum->scale) {
            // If its numeric and submitted rating is above maximum.
            throw new rating_exception('invalidnum');
        }

        // Make sure groups allow this user to see the item they're rating
        if ($discussion->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used.
            if (!groups_group_exists($discussion->groupid)) { // Can't find group.
                throw new rating_exception('cannotfindgroup'); // Something is wrong.
            }

            if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
                // Do not allow rating of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS.
                throw new rating_exception('notmemberofgroup');
            }
        }

        // Perform some final capability checks.
        if (!forum_user_can_see_post($forum, $discussion, $post, $USER, $cm)) {
            throw new rating_exception('nopermissiontorate');
        }

        $callback->set_valid(true);
    }
}
