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
 * Outcome Area Service
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\model\area_model;
use core_outcome\model\area_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Outcome Area Service
 *
 * This class assists with common use cases
 * when dealing with outcome areas.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class area_service {
    /**
     * @var area_repository
     */
    protected $areas;

    /**
     * @param area_repository $areas
     */
    public function __construct(area_repository $areas = null) {
        if (is_null($areas)) {
            $areas = new area_repository();
        }
        $this->areas = $areas;
    }

    /**
     * @param string $component
     * @param string $area
     * @param int $itemid
     * @param int $strictness
     * @return bool|area_model
     */
    public function get_area($component, $area, $itemid, $strictness = IGNORE_MISSING) {
        return $this->areas->find_one($component, $area, $itemid, $strictness);
    }

    /**
     * Set an outcome area as being used by an activity
     *
     * @param area_model $model
     * @param int $cmid
     * @return boolean True if the outcome_used_areas record was created, false otherwise
     */
    public function set_area_used(area_model $model, $cmid) {
        $this->areas->set_area_used($model, $cmid, $created);
        return $created;
    }

    /**
     * Set an outcome area as being used by multiple activities
     *
     * @param area_model $model
     * @param array $cmids
     */
    public function set_area_used_by_many(area_model $model, array $cmids) {
        $this->areas->set_area_used_by_many($model, $cmids);
    }

    /**
     * Remove an outcome area as being used by an activity
     *
     * @param area_model $model
     * @param int $cmid
     * @return $this
     */
    public function unset_area_used(area_model $model, $cmid) {
        $this->areas->unset_area_used($model, $cmid);
    }

    /**
     * @param string $component
     * @param string $area
     * @param int $itemid
     * @param int $cmid
     * @param bool $recover If the used area ID is not found, generate it
     * @return bool|int
     */
    public function get_used_area_id($component, $area, $itemid, $cmid, $recover = true) {
        $model            = new area_model();
        $model->component = $component;
        $model->area      = $area;
        $model->itemid    = $itemid;

        $id = $this->areas->fetch_area_used_id($model, $cmid);

        if (!empty($id)) {
            return $id;
        } else if ($recover and !empty($model->id)) {
            // Disaster recovery - set area as used.
            return $this->areas->set_area_used($model, $cmid);
        }
        return false;
    }

    /**
     * Delete an outcome area
     *
     * This will delete the following associated records:
     *      * outcome_areas record
     *      * outcome_area_outcomes records
     *      * outcome_used_areas records
     *      * outcome_attempts records
     *
     * @param string $component
     * @param string $area
     * @param int $itemid
     */
    public function delete_area($component, $area, $itemid) {

        $model = $this->areas->find_one($component, $area, $itemid);

        if ($model instanceof area_model) {
            $this->areas->remove($model);
        }
    }
}