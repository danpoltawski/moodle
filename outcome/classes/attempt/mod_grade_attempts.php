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
 * Outcome attempts based on activity grades
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\attempt;

use core_outcome\model\area_model;
use core_outcome\model\area_repository;
use core_outcome\model\attempt_model;
use core_outcome\model\attempt_repository;
use grade_grade;
use grade_item;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/gradelib.php');

/**
 * Assists with generating outcome attempt records based
 * on an activity's grade.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_grade_attempts {
    /**
     * @var area_repository
     */
    protected $areas;

    /**
     * @var attempt_repository
     */
    protected $attempts;

    /**
     * Last grade item ID that was processed
     *
     * This is part of a basic cache for processing several grades
     * that all belong to the same grade item.
     *
     * @var grade_item
     */
    protected $lastgradeitem;

    /**
     * Last used area ID that belongs to the last
     * grade item that was processed
     *
     * This is part of a basic cache for processing several grades
     * that all belong to the same grade item.
     *
     * @var int
     */
    protected $lastusedareaid = 0;

    /**
     * @param area_repository $areas
     * @param attempt_repository $attempts
     */
    public function __construct(area_repository $areas = null,
                                attempt_repository $attempts = null) {

        if (is_null($areas)) {
            $areas = new area_repository();
        }
        if (is_null($attempts)) {
            $attempts = new attempt_repository();
        }
        $this->areas = $areas;
        $this->attempts = $attempts;
    }

    /**
     * Sync a module's grades to outcome attempts.
     *
     * @param object $cm
     */
    public function sync_with_grade_item($cm) {
        $gradeitem = grade_item::fetch(array(
            'courseid'     => $cm->course,
            'itemtype'     => 'mod',
            'itemmodule'   => $cm->modname,
            'iteminstance' => $cm->instance,
            'itemnumber'   => 0,
        ));
        if (!$gradeitem) {
            return; // Nothing to do.
        }
        if (!$usedareaid = $this->fetch_used_area_id($cm)) {
            return; // Nothing to do.
        }
        // This primes our "cache" and bypasses a lot of grade item checks.
        $this->lastgradeitem   = $gradeitem;
        $this->lastusedareaid  = $usedareaid;

        /** @var grade_grade[] $grades */
        if ($grades = grade_grade::fetch_all(array('itemid' => $gradeitem->id))) {
            foreach ($grades as $grade) {
                $grade->grade_item = $gradeitem;
                $this->sync_with_grade($grade);
            }
        }
    }

    /**
     * Sync a single activity grade with an outcome attempt.
     *
     * @param grade_grade $grade
     * @param bool $deleted
     */
    public function sync_with_grade(grade_grade $grade, $deleted = false) {
        $usedareaid = $this->process_grade_item($grade);

        if (empty($usedareaid)) {
            return; // The activity is not associated with any outcomes, bail.
        }
        // Delete if the grade was deleted or there is no grade value.
        if ($deleted or is_null($grade->finalgrade)) {
            $this->attempts->remove_by(array(
                'outcomeusedareaid' => $usedareaid,
                'userid'            => $grade->userid,
            ));
        } else {
            $percentgrade = grade_format_gradevalue_percentage($grade->finalgrade, $grade->grade_item, 5, false);
            $percentgrade = (float) rtrim($percentgrade, ' %'); // Get this back to a float.

            $attempt                    = new attempt_model();
            $attempt->outcomeusedareaid = $usedareaid;
            $attempt->userid            = $grade->userid;
            $attempt->percentgrade      = $percentgrade;
            $attempt->mingrade          = $grade->grade_item->grademin;
            $attempt->maxgrade          = $grade->grade_item->grademax;
            $attempt->rawgrade          = $grade->finalgrade;
            $attempt->timemodified      = $grade->timemodified;
            $attempt->timecreated       = $grade->timecreated;

            $this->attempts->save($attempt);
        }
    }

    /**
     * Processes the grade's grade item and finds the outcome
     * used area ID.
     *
     * This returns zero when the grade item is not the correct
     * type of grade, if the course module no longer exists
     * or if the activity is not associated to any outcomes.
     *
     * @param grade_grade $grade
     * @return int The used area ID
     */
    protected function process_grade_item(grade_grade $grade) {
        if ($this->lastgradeitem instanceof grade_item and $grade->itemid == $this->lastgradeitem->id) {
            $grade->grade_item = $this->lastgradeitem;
            return $this->lastusedareaid;
        }
        $grade->load_grade_item();

        // Processing new grade item, reset.
        $this->lastgradeitem   = $grade->grade_item;
        $this->lastusedareaid  = 0;

        if (!$this->verify_grade_item($grade->grade_item)) {
            return $this->lastusedareaid;
        }
        $cm = get_coursemodule_from_instance($grade->grade_item->itemmodule,
            $grade->grade_item->iteminstance, $grade->grade_item->courseid);

        if (!$cm) {
            // The module was probably deleted before the grade item.
            return $this->lastusedareaid;
        }
        if ($usedareaid = $this->fetch_used_area_id($cm)) {
            $this->lastusedareaid = $usedareaid;
        }
        return $this->lastusedareaid;
    }

    /**
     * Verify that the grade item belongs to a module,
     * that it is the primary grade item and it has the
     * correct grade type.
     *
     * @param grade_item $gradeitem
     * @return bool
     */
    protected function verify_grade_item(grade_item $gradeitem) {
        if ($gradeitem->itemtype != 'mod') {
            return false; // Only care about modules.
        }
        if ($gradeitem->itemnumber != 0) {
            return false; // Only care about the primary grade item.
        }
        if ($gradeitem->gradetype != GRADE_TYPE_VALUE) {
            return false; // Only care about value grades.
        }
        return true;
    }

    /**
     * @param object $cm
     * @return bool|int
     */
    protected function fetch_used_area_id($cm) {
        $model            = new area_model();
        $model->component = 'mod_'.$cm->modname;
        $model->area      = 'mod';
        $model->itemid    = $cm->id;

        return $this->areas->fetch_area_used_id($model, $cm->id);
    }
}