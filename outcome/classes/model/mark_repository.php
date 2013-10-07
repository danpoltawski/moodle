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
 * Mark Model Repository Mapper
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
class mark_repository extends abstract_repository {
    protected $model = '\core_outcome\model\mark_model';
    protected $table = 'outcome_marks';

    /**
     * @var mark_history_repository
     */
    protected $history;

    public function __construct(\moodle_database $db = null, mark_history_repository $history = null) {
        parent::__construct($db);

        if (is_null($history)) {
            $history = new mark_history_repository();
        }
        $this->history = $history;
    }

    /**
     * @param int $id The mark ID
     * @param int $strictness
     * @return mark_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return mark_model|boolean Returns false if
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
     * @return mark_model[]
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->_find_by($conditions, $sort, $limitfrom, $limitnum);
    }

    /**
     * @param array $ids
     * @return mark_model[]
     */
    public function find_by_ids(array $ids) {
        return $this->_find_by_ids($ids);
    }

    /**
     * Determine if the user has ever earned this mark
     * before.  This includes checking in other courses
     * and in the history.
     *
     * @param mark_model $model
     * @return bool
     */
    public function has_ever_been_earned(mark_model $model) {
        if ($model->result == mark_model::EARNED) {
            return true;
        }
        $result = $this->db->record_exists_select(
            'outcome_marks', 'userid = ? AND outcomeid = ? AND courseid != ?',
            array($model->userid, $model->outcomeid, $model->courseid)
        );
        if ($result) {
            return true;
        }
        // This query finds the latest history record from other courses.
        // And then it filters those to see if any of them are marked as earned.
        // If one exists, that means the user has, in the past, earned the outcome.
        return $this->db->record_exists_sql('
            SELECT h.id
              FROM {outcome_marks_history} h
        INNER JOIN (
                SELECT outcomeid, userid, courseid, MAX(timecreated) timelatest
                  FROM {outcome_marks_history}
                 WHERE outcomeid = ?
                   AND userid = ?
                   AND courseid != ?
              GROUP BY outcomeid, userid, courseid
                  ) latest ON h.outcomeid = latest.outcomeid AND h.userid = latest.userid
                    AND h.userid = latest.userid AND h.timecreated = latest.timelatest
             WHERE h.outcomeid = ?
               AND h.userid = ?
               AND h.courseid != ?
               AND h.result = ?
        ', array(
            $model->outcomeid, $model->userid, $model->courseid,
            $model->outcomeid, $model->userid, $model->courseid,
            mark_model::EARNED,
        ));
    }

    /**
     * Save a mark
     *
     * @param mark_model $model
     */
    public function save(mark_model $model) {
        $model->timemodified = time();

        if (!empty($model->id)) {
            $this->db->update_record($this->table, $model);
            $this->history->update_mark($model);
        } else {
            $model->timecreated = $model->timemodified;
            $model->id          = $this->db->insert_record($this->table, $model);
            $this->history->create_mark($model);
        }
    }

    /**
     * Remove a mark
     *
     * @param mark_model $model
     */
    public function remove(mark_model $model) {
        $this->db->delete_records($this->table, array('id' => $model->id));
        $this->history->delete_mark($model);
        $model->id = null;
    }

    /**
     * Remove all marks belonging to the same course
     *
     * @param int $courseid
     */
    public function remove_by_courseid($courseid) {
        $rs = $this->db->get_recordset($this->table, array('courseid' => $courseid));
        foreach ($rs as $row) {
            /** @var mark_model $model */
            $model = $this->map_to_model($row);
            $this->history->delete_mark($model);
        }
        $rs->close();

        $this->db->delete_records($this->table, array('courseid' => $courseid));
    }
}