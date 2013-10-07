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
 * Outcome Award Model Repository Mapper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\model;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class award_repository extends abstract_repository {
    protected $model = '\core_outcome\model\award_model';
    protected $table = 'outcome_awards';

    /**
     * @param int $id The outcome set ID
     * @param int $strictness
     * @return award_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return award_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find_one_by(array $conditions, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by($conditions, $strictness);
    }

    /**
     * @param array $conditions
     * @param string $sort
     * @param int $limitfrom
     * @param int $limitnum
     * @return award_model[]
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->_find_by($conditions, $sort, $limitfrom, $limitnum);
    }

    /**
     * We only ever create these records, never update.
     *
     * @param award_model $model
     */
    public function insert(award_model $model) {
        if (!empty($model->id)) {
            return; // Assumed already in db.
        }
        if ($this->db->record_exists($this->table, array('userid' => $model->userid, 'outcomeid' => $model->outcomeid))) {
            return; // Already in db.
        }
        if (empty($model->timecreated)) {
            $model->timecreated = time();
        }
        $model->id = $this->db->insert_record($this->table, $model);
    }

    /**
     * @param array $conditions
     * @throws \coding_exception
     */
    public function remove_by(array $conditions) {
        if (empty($conditions)) {
            throw new \coding_exception('Conditions cannot be empty');
        }
        $this->db->delete_records($this->table, $conditions);
    }
}