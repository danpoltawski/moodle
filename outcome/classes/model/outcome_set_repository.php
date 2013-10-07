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
 * Outcome Set Model Repository Mapper
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
class outcome_set_repository extends abstract_repository {
    protected $model = '\core_outcome\model\outcome_set_model';
    protected $table = 'outcome_sets';

    /**
     * @param int $id The outcome set ID
     * @param int $strictness
     * @return outcome_set_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return outcome_set_model|boolean Returns false if
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
     * @return outcome_set_model[]
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->_find_by($conditions, $sort, $limitfrom, $limitnum);
    }

    /**
     * Find outcome sets that are used in a particular course
     *
     * @param int $courseid
     * @param null|string $sort Add sorting
     * @return outcome_set_model[]
     */
    public function find_used_by_course($courseid, $sort = null) {
        $sortsql = '';
        if (!is_null($sort)) {
            $sortsql = 'ORDER BY '.$sort;
        }
        $rs = $this->db->get_recordset_sql("
            SELECT s.*
              FROM {outcome_sets} s
        INNER JOIN {outcome_used_sets} u ON s.id = u.outcomesetid
             WHERE u.courseid = ?
               AND s.deleted = ?
          $sortsql
        ", array($courseid, 0));

        return $this->map_to_models($rs);
    }

    /**
     * Find outcome sets that belong to an outcome area
     *
     * @param area_model $area
     * @param bool $mappable Only return sets that are mappable
     * @return outcome_set_model[]
     */
    public function find_by_area(area_model $area, $mappable = true) {
        $select = 'a.id = ?';
        $params = array($area->id);

        if ($mappable) {
            $select .= ' AND s.deleted = ? AND o.deleted = ? AND o.assessable = ?';
            $params = array_merge($params, array(0, 0, 1));
        }
        $rs = $this->db->get_recordset_sql("
            SELECT s.*
              FROM {outcome_sets} s
        INNER JOIN {outcome} o ON s.id = o.outcomesetid
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
             WHERE $select
        ", $params);

        return $this->map_to_models($rs);
    }

    /**
     * Determine if the outcome set idnumber is unique or not
     *
     * @param string $idnumber
     * @param null|int $outcomesetid If passed, then this outcome set is ignored
     * @return bool
     */
    public function is_idnumber_unique($idnumber, $outcomesetid = null) {
        $select = 'idnumber = ?';
        $params = array($idnumber);

        if (!empty($outcomesetid)) {
            $select .= ' AND id != ?';
            $params[] = $outcomesetid;
        }
        return !$this->db->record_exists_select('outcome_sets', $select, $params);
    }

    /**
     * Fetch outcome metadata values for a given outcome set.
     *
     * @param outcome_set_model $outcomeset
     * @param $name
     * @return array
     */
    public function fetch_metadata_values(outcome_set_model $outcomeset, $name) {
        $rs = $this->db->get_recordset_sql('
            SELECT m.value
              FROM {outcome_sets} s
        INNER JOIN {outcome} o ON s.id = o.outcomesetid
        INNER JOIN {outcome_metadata} m ON o.id = m.outcomeid
             WHERE s.id = ?
               AND m.name = ?
          GROUP BY m.value
          ORDER BY m.value
        ', array($outcomeset->id, $name));

        $values = array();
        foreach ($rs as $row) {
            $values[] = $row->value;
        }
        $rs->close();

        return $values;
    }

    /**
     * Fetch courses that are mapped to a particular outcome set
     *
     * @param outcome_set_model $outcomeset
     * @param string|null $fields
     * @return array
     */
    public function fetch_mapped_courses(outcome_set_model $outcomeset,
                                         $fields = 'c.id, c.shortname, c.idnumber, c.fullname') {

        return $this->db->get_records_sql("
            SELECT $fields
              FROM {course} c
        INNER JOIN {outcome_used_sets} us ON c.id = us.courseid
        INNER JOIN {outcome_sets} s ON s.id = us.outcomesetid
             WHERE s.id = ?
        ", array($outcomeset->id));
    }

    /**
     * Save the outcome set
     *
     * @param outcome_set_model $model
     * @return $this
     */
    public function save(outcome_set_model $model) {
        $model->timemodified = time();
        if (!empty($model->id)) {
            $this->db->update_record('outcome_sets', $model);
        } else {
            $model->timecreated = $model->timemodified;
            $model->id = $this->db->insert_record('outcome_sets', $model);
        }
        return $this;
    }

    /**
     * Delete an outcome set
     *
     * @param outcome_set_model $model
     * @return $this
     */
    public function remove(outcome_set_model $model) {
        if ($model->deleted == 0) {
            $model->deleted = 1;
            $this->save($model);
        }
        return $this;
    }

    /**
     * Restore a deleted outcome set
     *
     * @param outcome_set_model $model
     * @return $this
     */
    public function restore(outcome_set_model $model) {
        if ($model->deleted == 1) {
            $model->deleted = 0;
            $this->save($model);
        }
        return $this;
    }
}