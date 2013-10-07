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
 * Outcome Area Repository Mapper
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
class area_repository extends abstract_repository {
    protected $model = '\core_outcome\model\area_model';
    protected $table = 'outcome_areas';

    /**
     * @param int $id The outcome set ID
     * @param int $strictness
     * @return area_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param string $component
     * @param string $area
     * @param int $itemid
     * @param int $strictness
     * @return area_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find_one($component, $area, $itemid, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by(array(
            'component' => $component,
            'area'      => $area,
            'itemid'    => $itemid,
        ), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return area_model|boolean Returns false if
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
     * @return area_model[]
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->_find_by($conditions, $sort, $limitfrom, $limitnum);
    }

    /**
     * Save an outcome area
     *
     * @param area_model $model
     * @return $this
     */
    public function save(area_model $model) {
        if (!empty($model->id)) {
            $this->db->update_record('outcome_areas', $model);
        } else {
            $model->id = $this->db->insert_record('outcome_areas', $model);
        }
        return $this;
    }

    /**
     * Save outcomes that are associated to an outcome area
     *
     * This compares the passed outcomes to this function to the existing
     * mapped outcomes.  Any that are missing from the passed outcomes
     * are removed.  The $mappable parameter is important as it wont
     * take into account deleted/non-assessable/non-mapped-sets outcomes that may
     * have been mapped to this content.
     *
     * @param area_model $model
     * @param outcome_model[] $outcomes The outcomes to save
     * @return $this
     */
    public function save_area_outcomes(area_model $model, array $outcomes) {

        $current = $this->db->get_records('outcome_area_outcomes',
            array('outcomeareaid' => $model->id), '', 'outcomeid, outcomeareaid');

        foreach ($outcomes as $outcome) {
            if (!array_key_exists($outcome->id, $current)) {
                $this->db->insert_record('outcome_area_outcomes', (object) array(
                    'outcomeid'     => $outcome->id,
                    'outcomeareaid' => $model->id,
                ));
            }
        }
        return $this;
    }

    /**
     * @param area_model $model
     * @param outcome_model[] $outcomes The outcomes to be removed
     */
    public function remove_area_outcomes(area_model $model, array $outcomes) {
        $ids = array();
        foreach ($outcomes as $outcome) {
            $ids[] = $outcome->id;
        }
        if (!empty($ids)) {
            list($sql, $params) = $this->db->get_in_or_equal($ids);
            $params[] = $model->id;

            $this->db->delete_records_select('outcome_area_outcomes', "outcomeid $sql AND outcomeareaid = ?", $params);
        }
    }

    /**
     * Set an outcome area as being used by an activity
     *
     * @param area_model $model
     * @param int $cmid
     * @param bool $created A flag to determine if the record was created or not
     * @return int
     */
    public function set_area_used(area_model $model, $cmid, &$created = false) {
        $conditions = array('cmid' => $cmid, 'outcomeareaid' => $model->id);
        if ($id = $this->db->get_field('outcome_used_areas', 'id', $conditions)) {
            $created = false;
            return $id;
        }
        $created = true;
        return $this->db->insert_record('outcome_used_areas', (object) $conditions);
    }

    /**
     * Set an outcome area as being used by multiple activities
     *
     * @param area_model $model
     * @param array $cmids
     * @return $this
     */
    public function set_area_used_by_many(area_model $model, array $cmids) {
        if (empty($cmids)) {
            return $this;
        }
        list($sql, $params) = $this->db->get_in_or_equal($cmids);
        $params[]  = $model->id;
        $sql       = "cmid $sql AND outcomeareaid = ?";
        $current   = $this->db->get_records_select_menu('outcome_used_areas', $sql, $params, 'id, cmid');
        $savecmids = array_diff($cmids, $current);

        if (empty($savecmids)) {
            return $this;
        }
        // Attempt to speed things up with transaction.
        $transaction = $this->db->start_delegated_transaction();

        try {
            foreach ($savecmids as $savecmid) {
                $this->db->insert_record('outcome_used_areas', (object) array(
                    'cmid'          => $savecmid,
                    'outcomeareaid' => $model->id,
                ));
            }
            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
        return $this;
    }

    /**
     * Remove an outcome area as being used by an activity
     *
     * @param area_model $model
     * @param int $cmid
     * @return int The ID of the outcome_used_areas record
     */
    public function unset_area_used(area_model $model, $cmid) {
        $this->db->delete_records('outcome_used_areas', array('cmid' => $cmid, 'outcomeareaid' => $model->id));
        return $this;
    }

    /**
     * Fetch an outcome_used_areas.id based on area and course module.
     *
     * The area model does not have to have its ID set.  If the model
     * does not have an ID and one is found, it will be set to the model.
     *
     * @param area_model $model
     * @param int $cmid
     * @return int|boolean
     */
    public function fetch_area_used_id(area_model $model, $cmid) {
        if (empty($model->id)) {
            $where = 'a.component = ? AND a.area = ? AND a.itemid = ?';
            $params = array($model->component, $model->area, $model->itemid);
        } else {
            $where = 'a.id = ?';
            $params = array($model->id);
        }
        $result = $this->db->get_record_sql("
            SELECT a.id areaid, used.id usedareaid
              FROM {outcome_areas} a
   LEFT OUTER JOIN {outcome_used_areas} used ON a.id = used.outcomeareaid AND used.cmid = ?
             WHERE $where
        ", array_merge(array($cmid), $params));

        if ($result === false) {
            return false; // Area doesn't even exist.
        }
        if (empty($model->id)) {
            $model->id = $result->areaid;
        }
        if (empty($result->usedareaid)) {
            return false;
        }
        return $result->usedareaid;
    }

    /**
     * Delete an outcome area and associated data
     *
     * @param area_model $model
     * @return $this
     */
    public function remove(area_model $model) {
        $rs = $this->db->get_recordset('outcome_used_areas', array('outcomeareaid' => $model->id));
        foreach ($rs as $row) {
            $this->db->delete_records('outcome_attempts', array('outcomeusedareaid' => $row->id));
        }
        $this->db->delete_records('outcome_area_outcomes', array('outcomeareaid' => $model->id));
        $this->db->delete_records('outcome_used_areas', array('outcomeareaid' => $model->id));
        $this->db->delete_records('outcome_areas', array('id' => $model->id));

        return $this;
    }
}