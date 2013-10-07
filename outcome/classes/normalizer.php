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
 * Normalize Outcome Models to Arrays
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome;

use core_outcome\model\outcome_model;
use core_outcome\model\outcome_set_model;

defined('MOODLE_INTERNAL') || die();

/**
 * Coverts models to arrays of data that can be
 * sent to external sources.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class normalizer {
    /**
     * Convert an outcome model to an array that can be used by JS
     *
     * @param outcome_model $model
     * @param bool $raw Include raw data, saves space if you don't include.
     * @return array
     */
    public function normalize_outcome(outcome_model $model, $raw = false) {
        $description = format_text($model->description, FORMAT_MOODLE, array('para' => false));

        $subjects = array();
        foreach ($model->subjects as $subject) {
            $subjects[] = format_string($subject);
        }
        $edulevels = array();
        foreach ($model->edulevels as $edulevel) {
            $edulevels[] = format_string($edulevel);
        }
        $normalized = array(
            'id'           => $model->id,
            'outcomesetid' => $model->outcomesetid,
            'parentid'     => $model->parentid,
            'idnumber'     => format_string($model->idnumber),
            'docnum'       => format_string($model->docnum),
            'description'  => $description,
            'assessable'   => $model->assessable,
            'deleted'      => $model->deleted,
            'sortorder'    => $model->sortorder,
            'subjects'     => $subjects,
            'edulevels'    => $edulevels,
            'timemodified' => $model->timemodified,
            'timecreated'  => $model->timecreated,
        );
        if ($raw) {
            $normalized['rawidnumber']    = $model->idnumber;
            $normalized['rawdocnum']      = $model->docnum;
            $normalized['rawdescription'] = $model->description;
            $normalized['rawsubjects']    = $model->subjects;
            $normalized['rawedulevels']   = $model->edulevels;
        }
        return $normalized;
    }

    /**
     * @param outcome_model[] $models
     * @param bool $raw Include raw data, saves space if you don't include.
     * @return array
     */
    public function normalize_outcomes(array $models, $raw = false) {
        $normalized = array();
        foreach ($models as $model) {
            $normalized[] = $this->normalize_outcome($model, $raw);
        }
        return $normalized;
    }

    /**
     * Convert an outcome set model to an array that can be used by JS
     *
     * Note: not adding raw attributes here because outcome sets are not
     * edited by JS right now.  So, feel free to add later if needed.
     *
     * @param outcome_set_model $model
     * @return array
     */
    public function normalize_outcome_set(outcome_set_model $model) {
        $description = format_text($model->description, FORMAT_MOODLE, array('para' => false));

        return array(
            'id'           => $model->id,
            'idnumber'     => format_string($model->idnumber),
            'name'         => format_string($model->name),
            'description'  => $description,
            'provider'     => format_string($model->provider),
            'revision'     => format_string($model->revision),
            'region'       => format_string($model->region),
            'deleted'      => $model->deleted,
            'timemodified' => $model->timemodified,
            'timecreated'  => $model->timecreated,
        );
    }

    /**
     * @param outcome_set_model[] $models
     * @return array
     */
    public function normalize_outcome_sets(array $models) {
        $normalized = array();
        foreach ($models as $model) {
            $normalized[] = $this->normalize_outcome_set($model);
        }
        return $normalized;
    }
}
