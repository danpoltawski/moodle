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
 * Outcome Award Service
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\model\award_model;
use core_outcome\model\award_repository;
use core_outcome\model\mark_model;
use core_outcome\model\mark_repository;
use core_outcome\model\outcome_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Award an outcome to a user.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class award_service {
    /**
     * @var mark_repository
     */
    protected $marks;

    /**
     * @var award_repository
     */
    protected $awards;

    /**
     * @var outcome_repository
     */
    protected $outcomes;

    /**
     * @param award_repository $awards
     * @param mark_repository $marks
     * @param outcome_repository $outcomes
     */
    public function __construct(award_repository $awards = null,
                                mark_repository $marks = null,
                                outcome_repository $outcomes = null) {

        if (is_null($marks)) {
            $marks = new mark_repository();
        }
        if (is_null($awards)) {
            $awards = new award_repository();
        }
        if (is_null($outcomes)) {
            $outcomes = new outcome_repository();
        }
        $this->marks    = $marks;
        $this->awards   = $awards;
        $this->outcomes = $outcomes;
    }

    /**
     * Convert a mark model into an award model
     *
     * @param mark_model $mark
     * @return award_model
     */
    protected function mark_to_award(mark_model $mark) {
        $award = new award_model();
        $award->userid = $mark->userid;
        $award->outcomeid = $mark->outcomeid;

        return $award;
    }

    /**
     * Given a marking, update outcome awards or warn if the
     * outcome is no longer earned and not earned elsewhere.
     *
     * @param mark_model $model
     * @throws \moodle_exception
     */
    public function update_award_by_mark(mark_model $model) {
        if ($model->result == mark_model::EARNED) {
            // Ensure award has been given.
            $this->awards->insert($this->mark_to_award($model));

        } else if ($model->result == mark_model::NOT_EARNED) {
            // If not earned in history table, etc - throw error message.
            if (!$this->marks->has_ever_been_earned($model)) {
                $outcome = $this->outcomes->find($model->outcomeid, MUST_EXIST);
                throw new \moodle_exception('outcomeawardwarning', 'outcome', '', format_string($outcome->description));
            }
        }
    }

    /**
     * Bulk update, see update_award_by_mark
     *
     * @param mark_model[] $models
     * @return array
     */
    public function update_awards_by_marks(array $models) {
        $errors = array();
        foreach ($models as $model) {
            try {
                $this->update_award_by_mark($model);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        return $errors;
    }
}
