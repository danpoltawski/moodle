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
 * Internal Service: Outcome Mark Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\model\mark_model;
use core_outcome\model\mark_repository;
use core_outcome\model\outcome_model;
use core_outcome\model\outcome_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Helps with marking outcomes as earned or not.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_helper {
    /**
     * @var mark_repository
     */
    protected $marks;

    /**
     * @var outcome_repository
     */
    protected $outcomes;

    /**
     * @param mark_repository $marks
     * @param outcome_repository $outcomes
     */
    public function __construct(mark_repository $marks = null,
                                outcome_repository $outcomes = null) {

        if (is_null($marks)) {
            $marks = new mark_repository();
        }
        if (is_null($outcomes)) {
            $outcomes = new outcome_repository();
        }
        $this->marks    = $marks;
        $this->outcomes = $outcomes;
    }

    /**
     * Mark outcomes as earned.
     *
     * @param int $courseid
     * @param int $graderid
     * @param int $userid
     * @param array $outcomeids
     * @return mark_model[]
     */
    public function mark_outcomes_as_earned($courseid, $graderid, $userid, array $outcomeids) {
        $models   = array();
        $outcomes = $this->outcomes->find_by_ids($outcomeids);
        foreach ($outcomes as $outcome) {
            $models[] = $this->mark_outcome_as_earned($courseid, $graderid, $userid, $outcome);
        }
        return $models;
    }

    /**
     * Ensure that the outcome has been earned.
     *
     * @param int $courseid
     * @param int $graderid
     * @param int $userid
     * @param outcome_model $outcome
     * @throws \coding_exception
     * @return mark_model
     */
    public function mark_outcome_as_earned($courseid, $graderid, $userid, outcome_model $outcome) {
        if (!$outcome->assessable) {
            throw new \coding_exception("Outcome is not assessable so it cannot be earned (id = $outcome->id");
        }
        $model = $this->marks->find_one_by(array('courseid' => $courseid, 'userid' => $userid, 'outcomeid' => $outcome->id));
        if (!$model instanceof mark_model) {
            $model = new mark_model();
            $model->courseid = $courseid;
            $model->outcomeid = $outcome->id;
            $model->userid = $userid;
        }
        $model->graderid = $graderid;
        $model->result = mark_model::EARNED;

        $this->marks->save($model);

        return $model;
    }

    /**
     * Update result for a group of marks
     *
     * @param int $graderid
     * @param array $markids Outcome mark IDs
     * @param array $earnedmarkids  Outcome mark IDs.  This is a white list of earned marks.  If
     *     a mark ID passed in $markids is not present in this parameter, then it will be updated
     *     as not earned.
     * @return mark_model[]
     */
    public function update_mark_earned($graderid, array $markids, array $earnedmarkids) {
        $updated = array();
        $models  = $this->marks->find_by_ids($markids);
        foreach ($models as $model) {
            if (in_array($model->id, $earnedmarkids)) {
                if ($model->result != mark_model::EARNED) {
                    $model->graderid = $graderid;
                    $model->result   = mark_model::EARNED;
                    $this->marks->save($model);

                    $updated[] = $model;
                }
            } else if ($model->result != mark_model::NOT_EARNED) {
                $model->graderid = $graderid;
                $model->result   = mark_model::NOT_EARNED;
                $this->marks->save($model);

                $updated[] = $model;
            }
        }
        return $updated;
    }
}
