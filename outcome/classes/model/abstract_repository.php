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
 * Abstract Model Repository Mapper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\model;

defined('MOODLE_INTERNAL') || die();

/**
 * A Model Repository Mapper is a class that handles the
 * transformation of the model to the DB layer AND
 * from the DB layer to the model.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_repository {
    /**
     * This is the class name of the default model that this
     * repository mapper interacts with.
     *
     * @var string
     */
    protected $model;

    /**
     * This is the default table name that this repository
     * mapper interacts with.
     *
     * @var string
     */
    protected $table;

    /**
     * @var \moodle_database
     */
    protected $db;

    /**
     * @param \moodle_database $db Defaults to global $DB
     * @throws \coding_exception
     */
    public function __construct(\moodle_database $db = null) {
        global $DB;

        if (empty($this->model) or empty($this->table)) {
            throw new \coding_exception('The model and table properties must not be empty');
        }
        if (is_null($db)) {
            $db = $DB;
        }
        $this->db = $db;
    }

    /**
     * @param array $conditions
     * @param int $strictness
     * @return object|boolean Returns false if
     *         $strictness = IGNORE_MISSING and record not round
     */
    protected function _find_one_by(array $conditions, $strictness = IGNORE_MISSING) {
        $record = $this->db->get_record($this->table, $conditions, '*', $strictness);

        if ($record === false) {
            return false;
        }
        return $this->map_to_model($record);
    }

    /**
     * @param array $conditions
     * @param string $sort
     * @param int $limitfrom
     * @param int $limitnum
     * @return object[]
     */
    protected function _find_by(array $conditions, $sort = '', $limitfrom = 0, $limitnum = 0) {
        $rs = $this->db->get_recordset($this->table, $conditions, $sort, '*', $limitfrom, $limitnum);
        return $this->map_to_models($rs);
    }

    /**
     * @param array $ids
     * @return object[]
     */
    protected function _find_by_ids(array $ids) {
        if (empty($ids)) {
            return array();
        }
        $rs = $this->db->get_recordset_list($this->table, 'id', $ids);
        return $this->map_to_models($rs);
    }

    /**
     * Helper method to map a DB record to a model instance
     *
     * @param array|object $record
     * @return object
     */
    protected function map_to_model($record) {
        $model = new $this->model;
        foreach ($record as $name => $value) {
            $model->$name = $value;
        }
        return $model;
    }

    /**
     * @param \moodle_recordset $rs
     * @return object[]
     */
    protected function map_to_models(\moodle_recordset $rs) {
        $models = array();
        foreach ($rs as $record) {
            $model              = $this->map_to_model($record);
            $models[$model->id] = $model;
        }
        $rs->close();

        return $models;
    }
}