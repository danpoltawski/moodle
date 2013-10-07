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
 * Internal Service: Outcome Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\model\outcome_model;
use core_outcome\model\outcome_repository;
use core_outcome\model\outcome_set_model;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Helps with validating and saving of outcomes.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_helper {
    /**
     * @var \moodle_database
     */
    protected $db;

    /**
     * @var outcome_repository
     */
    protected $outcomes;

    /**
     * @param outcome_repository $outcomes
     * @param \moodle_database $db
     */
    public function __construct(outcome_repository $outcomes = null, \moodle_database $db = null) {
        global $DB;

        if (is_null($outcomes)) {
            $outcomes = new outcome_repository();
        }
        if (is_null($db)) {
            $db = $DB;
        }
        $this->db = $db;
        $this->outcomes = $outcomes;
    }

    /**
     * @param outcome_set_model $outcomeset
     * @param outcome_model[] $currentoutcomes
     * @param object $data
     */
    public function save_outcome_form_data(outcome_set_model $outcomeset, $currentoutcomes, $data) {
        if (!$rawoutcomes = $this->extract_raw_outcomes($data)) {
            return;
        }
        $newidmap    = array();
        $transaction = $this->db->start_delegated_transaction();
        foreach ($rawoutcomes as $rawoutcome) {
            $id = clean_param($rawoutcome->id, PARAM_INT);
            if ($id > 0) {
                $outcome = $currentoutcomes[$id];
            } else {
                $outcome = new outcome_model();
            }
            // Remove ID, no longer required and if negative, breaks saves.
            unset($rawoutcome->id);
            $this->map_to_outcome($outcome, $rawoutcome);

            // Ensure that this is enforced.
            $outcome->outcomesetid = $outcomeset->id;

            $this->clean_and_validate($outcome);
            $this->resolve_parent_id($outcome, $newidmap);

            $this->outcomes->save($outcome);

            if ($id < 0) {
                $newidmap[$id] = $outcome->id;
            }
        }
        $transaction->allow_commit();
    }

    /**
     * @param object $data
     * @return bool|array
     */
    protected function extract_raw_outcomes($data) {
        if (!empty($data->modifiedoutcomedata)) {
            return json_decode($data->modifiedoutcomedata);
        }
        return false;
    }

    /**
     * Map data to an outcome model
     *
     * @param outcome_model $model
     * @param array|object $data
     */
    public function map_to_outcome(outcome_model $model, $data) {
        $rawdata = array();
        foreach ($data as $name => $value) {
            if (property_exists($model, $name)) {
                $model->$name = $value;
            } else if (strpos($name, 'raw') === 0) {
                $rawdata[substr($name, 3)] = $value;
            }
        }
        // Raw data parameters override main ones.  Just deal with it.
        if (!empty($rawdata)) {
            $this->map_to_outcome($model, $rawdata);
        }
    }

    /**
     * If the model's parent ID is less than zero, then its parent was
     * a new outcome.  Find the parent ID in the passed ID map.
     *
     * @param outcome_model $model
     * @param array $newidmap
     * @throws moodle_exception
     */
    protected function resolve_parent_id(outcome_model $model, array $newidmap) {
        if ($model->parentid < 0) {
            if (array_key_exists($model->parentid, $newidmap)) {
                $model->parentid = $newidmap[$model->parentid];
            } else {
                throw new moodle_exception('failedtosavereasonparent', 'outcome', '', format_string($model->description));
            }
        }
    }

    /**
     * Cleans an outcome model
     *
     * @param outcome_model $model
     */
    public function clean_outcome(outcome_model $model) {
        if (!empty($model->id)) {
            $model->id = clean_param($model->id, PARAM_INT);
        }
        if (!empty($model->timemodified)) {
            $model->timemodified = clean_param($model->timemodified, PARAM_INT);
        }
        if (!empty($model->timecreated)) {
            $model->timecreated = clean_param($model->timecreated, PARAM_INT);
        }
        $model->outcomesetid = clean_param($model->outcomesetid, PARAM_INT);
        $model->parentid     = clean_param($model->parentid, PARAM_INT);
        $model->idnumber     = trim(clean_param($model->idnumber, PARAM_TEXT));
        $model->docnum       = trim(clean_param($model->docnum, PARAM_TEXT));
        $model->assessable   = clean_param($model->assessable, PARAM_BOOL);
        $model->deleted      = clean_param($model->deleted, PARAM_BOOL);
        $model->description  = trim(clean_param($model->description, PARAM_TEXT));
        $model->subjects     = clean_param_array($model->subjects, PARAM_TEXT);
        $model->edulevels    = clean_param_array($model->edulevels, PARAM_TEXT);
        $model->sortorder    = clean_param($model->sortorder, PARAM_INT);

        $model->subjects  = array_map('trim', $model->subjects);
        $model->edulevels = array_map('trim', $model->edulevels);

        // Due to cleaning, nulls get converted to zeros or empty strings.  Restore nulls if necessary.
        if ($model->parentid == 0) {
            $model->parentid = null;
        }
        if ($model->docnum === '') {
            $model->docnum = null;
        }
    }

    /**
     * @param outcome_model $model
     * @param bool $requiresetid Require outcome set ID - sometimes you don't because the set doesn't exist yet
     * @param bool $throw Little funky, but if true, throws first error found
     * @return moodle_exception[]
     * @throws moodle_exception
     */
    public function validate_outcome(outcome_model $model, $requiresetid = true, $throw = true) {
        $errors = array();
        if ($model->description === '') {
            $errors[] = new moodle_exception('outcomedescriptionrequired', 'outcome');
        }
        if ($requiresetid and empty($model->outcomesetid)) {
            $errors[] = new moodle_exception('outcomesetidrequired', 'outcome');
        }
        if ($model->idnumber === '') {
            $errors[] = new moodle_exception('outcomeidnumberrequired', 'outcome');
        }
        if (!$this->outcomes->is_idnumber_unique($model->idnumber, $model->id)) {
            $conflict = $this->outcomes->find_one_by(array('idnumber' => $model->idnumber), MUST_EXIST);
            $errors[] = new moodle_exception('outcomeidnumbernotunique', 'outcome', '', array(
                'idnumber'    => format_string($model->idnumber),
                'description' => format_string($model->description),
                'conflict'    => format_string($conflict->description),
            ));
        }
        if ($throw and !empty($errors)) {
            throw $errors[0];
        }
        return $errors;
    }

    /**
     * Clean and validate an outcome model.
     *
     * @param outcome_model $model
     */
    public function clean_and_validate(outcome_model $model) {
        $this->clean_outcome($model);
        $this->validate_outcome($model);
    }

    /**
     * Clean, validate and save an outcome model
     *
     * @param outcome_model $model
     */
    public function save_outcome(outcome_model $model) {
        $this->clean_and_validate($model);
        $this->outcomes->save($model);
    }

    /**
     * Repairs any problems in with the sort order for all
     * outcomes within the passed outcome set.
     *
     * @param outcome_set_model $outcomeset
     */
    public function fix_sort_order(outcome_set_model $outcomeset) {
        $this->_fix_sort_order(
            $this->outcomes->find_by_outcome_set($outcomeset)
        );
    }

    /**
     * Does the actual repairing of the sort order
     *
     * @param outcome_model[] $outcomes
     * @param null|int $parentid
     * @param int $sortorder
     */
    protected function _fix_sort_order(array $outcomes, $parentid = null, &$sortorder = 0) {
        foreach ($outcomes as $outcome) {
            if ($outcome->parentid == $parentid) {
                if ($outcome->sortorder != $sortorder) {
                    $outcome->sortorder = $sortorder;
                    $this->outcomes->update_sort_order($outcome);
                }
                $sortorder++;

                $this->_fix_sort_order($outcomes, $outcome->id, $sortorder);
            }
        }
    }
}
