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
 * Outcome Set Filter Model Repository Mapper
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
class filter_repository extends abstract_repository {
    protected $model = '\core_outcome\model\filter_model';
    protected $table = 'outcome_used_sets';

    /**
     * @param $record
     * @return filter_model
     */
    protected function map_to_model($record) {
        if (is_null($record->filter)) {
            unset($record->filter);
        } else if (!is_array($record->filter)) {
            $record->filter = unserialize($record->filter);
        }
        return parent::map_to_model($record);
    }

    /**
     * @param int $id The outcome set ID
     * @param int $strictness
     * @return filter_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return filter_model|boolean Returns false if
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
     * @return filter_model[]
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->_find_by($conditions, $sort, $limitfrom, $limitnum);
    }

    /**
     * Finds all ACTIVE filters for a given course
     *
     * @param int $courseid
     * @param int $limitfrom
     * @param int $limitnum
     * @return filter_model[]
     */
    public function find_by_course($courseid, $limitfrom = 0, $limitnum = 0) {
        $rs = $this->db->get_recordset_sql('
            SELECT f.*
              FROM {outcome_sets} s
        INNER JOIN {outcome_used_sets} f ON s.id = f.outcomesetid
             WHERE f.courseid = ?
               AND s.deleted = ?
        ', array($courseid, 0), $limitfrom, $limitnum);

        return $this->map_to_models($rs);
    }

    /**
     * Save a set of filters and delete any filters
     * that are not in the set.
     *
     * Note: ALL filters must belong to the same course.  Deletion
     * is based on courseid.
     *
     * @param int $courseid
     * @param filter_model[] $models
     * @throws \coding_exception
     * @return $this
     */
    public function sync($courseid, array $models) {
        $savedids = array();

        // Validation - make sure all of the models belong to the same course.
        foreach ($models as $model) {
            if ($courseid != $model->courseid) {
                throw new \coding_exception('Can only use the sync method when all of the models belong to the same course');
            }
        }
        foreach ($models as $model) {
            $this->save($model);
            $savedids[] = $model->id;
        }
        $sql    = 'courseid = ?';
        $params = array($courseid);
        if (!empty($savedids)) {
            list($insql, $inparams) = $this->db->get_in_or_equal($savedids, SQL_PARAMS_QM, 'param', false);
            $sql .= " AND id $insql";
            $params = array_merge($params, $inparams);
        }
        $this->db->delete_records_select('outcome_used_sets', $sql, $params);

        return $this;
    }

    /**
     * Save the outcome set filter
     *
     * @param filter_model $model
     * @return $this
     */
    public function save(filter_model $model) {
        // Attempt to find an ID for the model.
        if (empty($model->id)) {
            $conditions = array('courseid' => $model->courseid, 'outcomesetid' => $model->outcomesetid);
            $id = $this->db->get_field('outcome_used_sets', 'id', $conditions);

            if (!empty($id)) {
                $model->id = $id;
            }
        }
        $record = (object) get_object_vars($model);
        if (empty($record->filter)) {
            $record->filter = null;
        } else {
            $record->filter = serialize($record->filter);
        }
        if (!empty($model->id)) {
            $this->db->update_record('outcome_used_sets', $record);
        } else {
            $model->id = $this->db->insert_record('outcome_used_sets', $record);
        }
        return $this;
    }

    /**
     * Delete an outcome set filter
     *
     * @param filter_model $model
     * @return $this
     */
    public function remove(filter_model $model) {
        if (!empty($model->id)) {
            $conditions = array('id' => $model->id);
        } else {
            $conditions = array(
                'courseid' => $model->courseid,
                'outcomesetid' => $model->outcomesetid
            );
        }
        $this->db->delete_records('outcome_used_sets', $conditions);
        $model->id = null;

        return $this;
    }

    /**
     * This will delete ALL outcome sets mapped to a course
     *
     * @param int $courseid
     * @return $this
     */
    public function remove_by_course($courseid) {
        $this->db->delete_records('outcome_used_sets', array('courseid' => $courseid));
        return $this;
    }
}