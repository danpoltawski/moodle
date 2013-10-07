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
 * Coverage abstract
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\coverage;

use core_outcome\model\filter_repository;
use core_outcome\model\outcome_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class coverage_abstract implements coverage_interface {
    /**
     * @var int
     */
    protected $courseid;

    /**
     * @param $courseid
     */
    public function set_courseid($courseid) {
        $this->courseid = $courseid;
    }

    /**
     * @return int
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Get SQL that gets all mappable outcomes
     *
     * @return array|bool
     */
    public function course_outcomes_sql() {
        $outcomerepo = new outcome_repository();
        $filterrepo  = new filter_repository();

        $filters = $filterrepo->find_by_course($this->courseid);

        if (empty($filters)) {
            return false;
        }
        $params  = array();
        $queries = array();
        foreach ($filters as $filter) {
            list($filtersql, $filterparams) = $outcomerepo->filter_to_sql($filter, true);

            $queries[] = "SELECT o.id FROM {outcome} o $filtersql->join WHERE $filtersql->where $filtersql->groupby";
            $params    = array_merge($params, $filterparams);
        }
        $sql = '('.implode(') UNION (', $queries).')';

        return array($sql, $params);
    }
}
