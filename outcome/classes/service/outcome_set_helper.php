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
 * Internal Service: Outcome Set Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\model\outcome_set_model;
use core_outcome\model\outcome_set_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Helps with validating and saving outcome sets.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_set_helper {
    /**
     * @var outcome_set_repository
     */
    protected $outcomesets;

    /**
     * These are option text fields in the outcome set model
     *
     * @var array
     */
    protected $optional = array('description', 'provider', 'revision', 'region');

    /**
     * @param outcome_set_repository $outcomesets
     */
    public function __construct(outcome_set_repository $outcomesets = null) {

        if (is_null($outcomesets)) {
            $outcomesets = new outcome_set_repository();
        }
        $this->outcomesets = $outcomesets;
    }

    /**
     * Save outcome set form data.
     *
     * @param outcome_set_model $model
     * @param object $data
     */
    public function save_outcome_set_form_data(outcome_set_model $model, $data) {
        $model->name        = $data->name;
        $model->idnumber    = $data->idnumber;
        $model->description = $data->description;
        $model->provider    = $data->provider;
        $model->region      = $data->region;

        $this->save_outcome_set($model);
    }

    /**
     * Clean and validate an outcome set model.
     *
     * @param outcome_set_model $model
     * @throws \moodle_exception
     * @throws \coding_exception
     */
    public function clean_and_validate(outcome_set_model $model) {
        if (!empty($model->id)) {
            $model->id = clean_param($model->id, PARAM_INT);
        }
        if (!empty($model->timemodified)) {
            $model->timemodified = clean_param($model->timemodified, PARAM_INT);
        }
        if (!empty($model->timecreated)) {
            $model->timecreated = clean_param($model->timecreated, PARAM_INT);
        }
        $model->idnumber = trim(clean_param($model->idnumber, PARAM_TEXT));
        $model->name     = trim(clean_param($model->name, PARAM_TEXT));
        $model->deleted  = clean_param($model->deleted, PARAM_BOOL);

        foreach ($this->optional as $property) {
            $model->$property = trim(clean_param($model->$property, PARAM_TEXT));

            if ($model->$property === '') {
                $model->$property = null;
            }
        }
        if ($model->name === '') {
            throw new \coding_exception('Outcome set name is required');
        }
        if ($model->idnumber === '') {
            throw new \coding_exception('Outcome idnumber property is required');
        }
        if (!$this->outcomesets->is_idnumber_unique($model->idnumber, $model->id)) {
            $conflict = $this->outcomesets->find_one_by(array('idnumber' => $model->idnumber), MUST_EXIST);
            throw new \moodle_exception('outcomesetidnumbererror', 'outcome', '', array(
                'idnumber' => format_string($model->idnumber),
                'name'     => format_string($model->name),
                'conflict' => format_string($conflict->name),
            ));
        }
    }

    /**
     * Clean, validate and save an outcome set model
     *
     * @param outcome_set_model $model
     */
    public function save_outcome_set(outcome_set_model $model) {
        $this->clean_and_validate($model);
        $this->outcomesets->save($model);
    }
}
