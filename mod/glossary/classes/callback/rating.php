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
 * Callbacks for rating API.
 *
 * @package    mod_glossary
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_glossary\callback;

use \core_rating\callback\validate;
use \core_rating\callback\permissions;
use rating_exception;
use context_module;
use context;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for rating API.
 *
 * @package    mod_glossary
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating {
    /**
     * Validates a submitted rating
     * @param \core_rating\callback\validate
     */
    public static function validate(validate $callback) {
        global $DB, $USER;

        // Check the component is correct.
        if ($callback->get_component() != 'mod_glossary') {
            throw new rating_exception('invalidcomponent');
        }

        // Check the ratingarea is post (the only rating area in forum).
        if ($callback->get_ratingarea() != 'entry') {
            throw new rating_exception('invalidratingarea');
        }

        // Check the rateduserid is not the current user .. you can't rate your own posts.
        if ($callback->get_rateduserid() == $USER->id) {
            throw new rating_exception('nopermissiontorate');
        }

        $glossarysql = "SELECT g.id as glossaryid, g.scale, g.course, e.userid as userid,
                               e.approved, e.timecreated, g.assesstimestart, g.assesstimefinish
                        FROM {glossary_entries} e
                        JOIN {glossary} g ON e.glossaryid = g.id
                        WHERE e.id = :itemid";
        $glossaryparams = array('itemid' => $callback->get_itemid());
        $info = $DB->get_record_sql($glossarysql, $glossaryparams);
        if (!$info) {
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

        // Check the item we're rating was created in the assessable time window.
        if (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
            if ($info->timecreated < $info->assesstimestart || $info->timecreated > $info->assesstimefinish) {
                throw new rating_exception('notavailable');
            }
        }

        $cm = get_coursemodule_from_instance('glossary', $info->glossaryid, $info->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id, MUST_EXIST);

        // If the supplied context doesnt match the item's context.
        if ($context->id != $callback->get_context()->id) {
            throw new rating_exception('invalidcontext');
        }

        $callback->set_valid(true);
    }

    /**
     * Return rating related permissions
     *
     * @param \core_rating\callback\permissions
     */
    public static function permissions(permissions $callback) {
        if ($callback->get_component() != 'mod_glossary' || $callback->get_ratingarea() != 'entry') {
            // We don't know about this component/ratingarea so just return to get the
            // default restrictive permissions.
            return;
        }
        $context = context::instance_by_id($callback->get_contextid());
        $callback->set_canview(has_capability('mod/glossary:viewrating', $context));
        $callback->set_canviewany(has_capability('mod/glossary:viewanyrating', $context));
        $callback->set_canviewall(has_capability('mod/glossary:viewallratings', $context));
        $callback->set_canrate(has_capability('mod/glossary:rate', $context));
    }
}