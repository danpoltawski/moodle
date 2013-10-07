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
 * Outcome Model Repository Mapper
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
class outcome_repository extends abstract_repository {
    protected $model = '\core_outcome\model\outcome_model';
    protected $table = 'outcome';

    /**
     * Metadata fields in the outcome model
     *
     * These are retrieved and saved to outcome_metadata table.
     *
     * @var array
     */
    protected $metadatafields = array('edulevels', 'subjects');

    /**
     * @param \moodle_recordset $rs
     * @param outcome_model[] $outcomes
     * @return outcome_model[]
     */
    protected function map_metadata(\moodle_recordset $rs, array $outcomes) {
        foreach ($rs as $metadata) {
            $outcomes[$metadata->outcomeid]->{$metadata->name}[] = $metadata->value;
        }
        $rs->close();

        return $outcomes;
    }

    /**
     * Populate a set of outcomes with their metadata
     *
     * @param outcome_model[] $outcomes
     * @return outcome_model[]
     */
    public function find_metadata(array $outcomes) {
        if (empty($outcomes)) {
            return $outcomes;
        }
        $rs = $this->db->get_recordset_list('outcome_metadata', 'outcomeid', array_keys($outcomes), 'id');
        return $this->map_metadata($rs, $outcomes);
    }

    /**
     * Converts a filter into a SQL object that filters outcomes.
     *
     * @param filter_model $filter
     * @param bool $assessable
     * @return array SQL object and params
     */
    public function filter_to_sql(filter_model $filter, $assessable = false) {

        static $pcount = 0; // Prefix count
        $pcount++;

        $p        = 'f'.$pcount.'_';
        $ors      = $params = $joins = array();
        $template = 'LEFT JOIN {outcome_metadata} %1$s ON (o.id = %1$s.outcomeid AND %1$s.name = :'.$p.'filterjoin%1$s)';
        $sql      = (object) array('join' => '', 'where' => '', 'groupby' => '');
        $count    = 0;

        foreach ($filter->filter as $info) {
            $ands = array();
            foreach ($this->metadatafields as $field) {
                if (array_key_exists($field, $info) and !is_null($info[$field])) {
                    $ands[] = "$field.value = :{$p}filter$field$count";
                    $params["{$p}filter$field$count"] = $info[$field];

                    if (!array_key_exists($field, $joins)) {
                        $joins[$field] = sprintf($template, $field);
                        $params[$p.'filterjoin'.$field] = $field;
                    }
                    $count++;
                }
            }
            // If empty, then bail because we are selecting all in the outcome set.
            if (empty($ands)) {
                // Reset all of these to produce a simple query.
                $ors = $params = $joins = array();
                break;
            }
            $ors[] = implode(' AND ', $ands);
        }
        $sql->where = "o.outcomesetid = :{$p}filteroutcomesetid";
        $params[$p.'filteroutcomesetid'] = $filter->outcomesetid;

        if ($assessable) {
            $sql->where .= " AND o.deleted = :{$p}filterdeleted AND o.assessable = :{$p}filterassessable";
            $params[$p.'filterdeleted']    = 0;
            $params[$p.'filterassessable'] = 1;
        }
        if (!empty($ors)) {
            $sql->where .= ' AND ('.implode(' OR ', $ors).')';
        }
        if (!empty($joins)) {
            $sql->join    = implode(' ', $joins);
            $sql->groupby = 'GROUP BY o.id';
        }
        return array($sql, $params);
    }

    /**
     * @param int $id The outcome ID
     * @param int $strictness
     * @return outcome_model|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    public function find($id, $strictness = IGNORE_MISSING) {
        return $this->_find_one_by(array('id' => $id), $strictness);
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return outcome_model|boolean Returns false if
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
     * @return outcome_model[]
     */
    public function find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        return $this->_find_by($conditions, $sort, $limitfrom, $limitnum);
    }

    /**
     * @param array $ids
     * @return outcome_model[]
     */
    public function find_by_ids(array $ids) {
        return $this->_find_by_ids($ids);
    }

    /**
     * Find all outcomes in a given outcome set.
     *
     * @param outcome_set_model $outcomeset
     * @param bool $metadata Include outcome metadata or not
     * @return outcome_model[]
     */
    public function find_by_outcome_set(outcome_set_model $outcomeset, $metadata = false) {
        $rs     = $this->db->get_recordset('outcome', array('outcomesetid' => $outcomeset->id), 'sortorder');
        $models = $this->map_to_models($rs);

        if ($metadata) {
            $metars = $this->db->get_recordset_sql('
                SELECT m.*
                  FROM {outcome} o
            INNER JOIN {outcome_metadata} m ON o.id = m.outcomeid
                 WHERE o.outcomesetid = ?
              ORDER BY m.id
            ', array($outcomeset->id));

            $this->map_metadata($metars, $models);
        }
        return $models;
    }

    /**
     * Find all outcomes that match the passed filter
     *
     * @param filter_model $filter
     * @param bool $mappable Only return the ones that are mappable
     * @return outcome_model[]
     */
    public function find_by_filter(filter_model $filter, $mappable = true) {
        list($sql, $params) = $this->filter_to_sql($filter);

        $extrawheresql = '';
        if ($mappable) {
            $extrawheresql = 'AND o.deleted = :deleted AND o.assessable = :assessable';
            $params['deleted'] = 0;
            $params['assessable'] = 1;
        }
        $rs = $this->db->get_recordset_sql("
            SELECT o.*
              FROM {outcome} o
                   $sql->join
             WHERE $sql->where $extrawheresql
                   $sql->groupby
        ", $params);

        return $this->map_to_models($rs);
    }

    /**
     * Find outcomes that are associated to a particular outcome area
     *
     * @param area_model $area
     * @param bool $mappable Only return the ones that are mappable
     * @return outcome_model[]
     */
    public function find_by_area(area_model $area, $mappable = true) {
        $select = 'a.id = ?';
        $params = array($area->id);

        if ($mappable) {
            $select .= ' AND s.deleted = ? AND o.deleted = ? AND o.assessable = ?';
            $params = array_merge($params, array(0, 0, 1));
        }
        $rs = $this->db->get_recordset_sql("
            SELECT o.*
              FROM {outcome} o
        INNER JOIN {outcome_sets} s ON s.id = o.outcomesetid
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
             WHERE $select
        ", $params);

        return $this->map_to_models($rs);
    }

    /**
     * Gets assessable outcomes that are mapped to an area
     * and are also filtered.
     *
     * @param area_model $area
     * @param filter_model $filter
     * @return object[]
     */
    public function find_by_area_and_filter(area_model $area, filter_model $filter) {
        list($sql, $params) = $this->filter_to_sql($filter);

        $rs = $this->db->get_recordset_sql("
            SELECT o.*
              FROM {outcome} o
                   $sql->join
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
             WHERE $sql->where AND a.id = :areaid AND o.deleted = :deleted AND o.assessable = :assessable
          $sql->groupby
        ", array_merge($params, array('areaid' => $area->id, 'deleted' => 0, 'assessable' => 1)));

        return $this->map_to_models($rs);
    }

    /**
     * Finds outcomes for related items
     *
     * @param string $component
     * @param string $area
     * @param array $itemids
     * @return array
     */
    public function find_by_area_itemids($component, $area, array $itemids) {
        list($sql, $params) = $this->db->get_in_or_equal($itemids);

        $params[] = $component;
        $params[] = $area;

        $rs = $this->db->get_recordset_sql("
            SELECT o.*, a.itemid
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
             WHERE a.itemid $sql
               AND a.component = ?
               AND a.area = ?
        ", $params);

        $results = array();
        foreach ($rs as $row) {
            $itemid = $row->itemid;
            unset($row->itemid);
            if (!array_key_exists($itemid, $results)) {
                $results[$itemid] = array();
            }
            $results[$itemid][$row->id] = $this->map_to_model($row);
        }
        return $results;
    }

    /**
     * Determine if the outcome idnumber is unique or not
     *
     * @param string $idnumber
     * @param null|int $outcomeid If passed, then this outcome is ignored
     * @return bool
     */
    public function is_idnumber_unique($idnumber, $outcomeid = null) {
        $select = 'idnumber = ?';
        $params = array($idnumber);

        if (!empty($outcomeid)) {
            $select .= ' AND id != ?';
            $params[] = $outcomeid;
        }
        return !$this->db->record_exists_select('outcome', $select, $params);
    }

    /**
     * Save an outcome
     *
     * Warning: all metadata (EG: edulevels and subjects) must
     * be present or they will be deleted.
     *
     * @param outcome_model $model
     * @return $this
     */
    public function save(outcome_model $model) {
        $model->timemodified = time();

        if (!empty($model->id)) {
            $this->db->update_record('outcome', $model);
            $this->db->delete_records('outcome_metadata', array('outcomeid' => $model->id));
        } else {
            $model->timecreated = $model->timemodified;
            $model->id = $this->db->insert_record('outcome', $model);
        }
        foreach ($this->metadatafields as $name) {
            foreach ($model->$name as $value) {
                $this->db->insert_record('outcome_metadata', (object) array(
                    'outcomeid' => $model->id,
                    'name'      => $name,
                    'value'     => $value,
                ));
            }
        }
        return $this;
    }

    /**
     * Update an outcomes sort order field
     *
     * @param outcome_model $model
     */
    public function update_sort_order(outcome_model $model) {
        $this->db->set_field('outcome', 'sortorder', $model->sortorder, array('id' => $model->id));
    }
}