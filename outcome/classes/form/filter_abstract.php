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
 * Abstract form for report filtering
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\form;

use core_outcome\model\outcome_set_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides common filtering options.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class filter_abstract extends cached_form_abstract {
    /**
     * Don't cache the submit button
     *
     * @return array
     */
    public function cache_blacklist() {
        return array_merge(
            parent::cache_blacklist(),
            array('submitbutton')
        );
    }

    /**
     * Standard filter header
     */
    public function define_filter_header() {
        $this->_form->addElement('header', 'filters', get_string('filters', 'outcome'));
    }

    /**
     * Standard filter button
     */
    public function define_filter_buttons() {
        $this->_form->addElement('submit', 'submitbutton', get_string('filter', 'outcome'));
    }

    /**
     * Add a select drop-down of course outcome sets.
     */
    public function define_outcome_sets() {
        global $COURSE;

        $repo        = new outcome_set_repository();
        $outcomesets = $repo->find_used_by_course($COURSE->id, 's.name');

        $options = array();
        foreach ($outcomesets as $outcomeset) {
            $options[$outcomeset->id] = format_string($outcomeset->name);
        }
        reset($options);

        $mform = $this->_form;
        $mform->addElement('select', 'outcomesetid', get_string('outcomeset', 'outcome'), $options);
        $mform->setDefault('outcomesetid', key($options));
        $mform->setType('outcomesetid', PARAM_INT);
    }

    /**
     * Add a drop-down populated with course users
     */
    public function define_course_users() {
        $options = array();
        foreach ($this->get_gradebook_users() as $user) {
            $options[$user->id] = fullname($user);
        }
        \core_collator::asort($options);
        reset($options);

        $mform = $this->_form;
        $mform->addElement('select', 'userid', get_string('user', 'outcome'), $options);
        $mform->setDefault('userid', key($options));
        $mform->setType('userid', PARAM_INT);
    }

    /**
     * Add a drop-down populated with course groups.
     */
    public function define_course_groups() {
        global $PAGE;

        $options = array(0 => get_string('allgroups', 'outcome'));
        $groups  = groups_get_all_groups($PAGE->course->id);

        foreach ($groups as $group) {
            $options[$group->id] = format_string($group->name);
        }

        $mform = $this->_form;
        $mform->addElement('select', 'groupid', get_string('group', 'outcome'), $options);
        $mform->setDefault('groupid', 0);
        $mform->setType('groupid', PARAM_INT);
    }

    /**
     * Get gradebook users - throws exception if none are found.
     *
     * @return array
     * @throws \moodle_exception
     */
    protected function get_gradebook_users() {
        global $CFG, $DB, $PAGE;

        // Ensure we have course context.
        $context = \context_course::instance($PAGE->course->id);

        list($esql, $eparams) = get_enrolled_sql($context);
        list($gsql, $rparams) = $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED, 'grbr');
        list($csql, $cparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');

        $users = $DB->get_records_sql("
            SELECT u.id, u.firstname, u.lastname
              FROM {user} u
        INNER JOIN ($esql) e ON e.id = u.id
        INNER JOIN (
                     SELECT DISTINCT ra.userid
                       FROM {role_assignments} ra
                      WHERE ra.roleid $gsql
                        AND ra.contextid $csql
                   ) ra ON ra.userid = u.id
             WHERE u.deleted = 0
          ORDER BY u.firstname, u.lastname
        ", array_merge($eparams, $rparams, $cparams));

        if (empty($users)) {
            throw new \moodle_exception('nogradebookusers', 'outcome', $CFG->wwwroot.'/course/view.php?id='.$PAGE->course->id);
        }
        return $users;
    }
}