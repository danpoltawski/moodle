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
 * Outcome Attempt Model Repository Mapper
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
class attempt_repository extends abstract_repository {
    protected $model = '\core_outcome\model\attempt_model';
    protected $table = 'outcome_attempts';

    /**
     * @param int $id The attempt ID
     * @param int $strictness
     * @return attempt_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return attempt_model|boolean Returns false if
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
     * @return attempt_model[]
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->_find_by($conditions, $sort, $limitfrom, $limitnum);
    }

    /**
     * Find attempts against a particular item ID
     *
     * @param int $usedareaid
     * @param int $itemid
     * @param array $userids Optional, restrict to a list of users
     * @return attempt_model[]
     */
    public function find_by_itemid($usedareaid, $itemid, array $userids = array()) {
        if (!empty($userids)) {
            list($sql, $params) = $this->db->get_in_or_equal($userids);
            $sql = 'AND userid '.$sql;
        } else {
            $params = array();
            $sql = '';
        }
        $rs = $this->db->get_recordset_sql("
            SELECT *
              FROM {outcome_attempts}
             WHERE outcomeusedareaid = ?
               AND itemid = ?
               $sql
        ", array_merge(array($usedareaid, $itemid), $params));

        return $this->map_to_models($rs);
    }

    /**
     * @param array $conditions
     * @return bool
     */
    public function exists_by(array $conditions) {
        return $this->db->record_exists('outcome_attempts', $conditions);
    }

    /**
     * Save the attempt
     *
     * @param attempt_model $model
     * @param bool $duplicatecheck
     * @return $this
     */
    public function save(attempt_model $model, $duplicatecheck = true) {
        if (empty($model->timecreated)) {
            $model->timecreated = time();
        }
        // We don't automatically bump this because sometimes this is synced to other records.
        if (empty($model->timemodified)) {
            $model->timemodified = $model->timecreated;
        }
        if (empty($model->id) and $duplicatecheck) {
            // Some duplication prevention.
            $id = $this->db->get_field('outcome_attempts', 'id', array(
                'outcomeusedareaid' => $model->outcomeusedareaid,
                'userid' => $model->userid,
                'itemid' => $model->itemid
            ));
            if (!empty($id)) {
                $model->id = $id;
            }
        }
        if (!empty($model->id)) {
            $this->db->update_record('outcome_attempts', $model);
        } else {
            $model->id = $this->db->insert_record('outcome_attempts', $model);
        }
        return $this;
    }

    /**
     * Delete an attempt
     *
     * @param attempt_model $model
     * @return $this
     */
    public function remove(attempt_model $model) {
        $this->db->delete_records('outcome_attempts', array('id' => $model->id));
        $model->id = null;
        return $this;
    }

    /**
     * Delete an attempt based on some conditions
     *
     * @param array $conditions
     * @return $this
     * @throws \coding_exception
     */
    public function remove_by(array $conditions) {
        if (empty($conditions)) {
            throw new \coding_exception('Cannot delete all attempts via this method');
        }
        $this->db->delete_records('outcome_attempts', $conditions);
        return $this;
    }

    /**
     * Delete attempts based on course module ID
     *
     * If areas are passed, then those area attempts
     * within the course module are not deleted.
     *
     * @param $cmid
     * @param area_model[] $excludedareas
     * @return $this
     */
    public function remove_by_cmid($cmid, array $excludedareas = array()) {
        if (empty($excludedareas)) {
            $this->db->delete_records_select('outcome_attempts', 'outcomeusedareaid IN (
                SELECT id
                  FROM {outcome_used_areas}
                 WHERE cmid = ?
            )', array($cmid));
        } else {
            $ids = array();
            foreach ($excludedareas as $excludedarea) {
                $ids[] = $excludedarea->id;
            }
            list($sql, $params) = $this->db->get_in_or_equal($ids, SQL_PARAMS_QM, 'param', false);

            $this->db->delete_records_select('outcome_attempts', "outcomeusedareaid IN (
                SELECT ua.id
                  FROM {outcome_used_areas} ua
            INNER JOIN {outcome_areas} a ON a.id = ua.outcomeareaid
                 WHERE ua.cmid = ?
                   AND a.id $sql
            )", array_merge(array($cmid), $params));
        }
        return $this;
    }
}
