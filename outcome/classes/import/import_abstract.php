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
 * Outcome Set Import Abstract
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\import;

use coding_exception;
use core_outcome\model\outcome_model;
use core_outcome\model\outcome_set_model;
use core_outcome\service\outcome_helper;
use core_outcome\service\outcome_set_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Implements commonly used methods for importing of
 * outcome sets.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class import_abstract implements import_interface {
    /**
     * @var outcome_set_model
     */
    protected $outcomeset;

    /**
     * Keep track of the current outcome sort order
     *
     * @var int
     */
    protected $sortorder = 0;

    /**
     * @var outcome_helper
     */
    protected $outcomehelper;

    /**
     * @var outcome_set_helper
     */
    protected $outcomesethelper;

    /**
     * A map of unique identifier to a value (Usually a new outcome record ID)
     *
     * @var array
     */
    protected $map = array();

    /**
     * @param outcome_helper $outcomehelper
     * @param outcome_set_helper $outcomesethelper
     */
    public function __construct(outcome_helper $outcomehelper = null,
                                outcome_set_helper $outcomesethelper = null) {

        if (is_null($outcomehelper)) {
            $outcomehelper = new outcome_helper();
        }
        if (is_null($outcomesethelper)) {
            $outcomesethelper = new outcome_set_helper();
        }
        $this->outcomehelper    = $outcomehelper;
        $this->outcomesethelper = $outcomesethelper;
    }

    public function get_result() {
        if ($this->outcomeset instanceof outcome_set_model) {
            return $this->outcomeset;
        }
        return null;
    }

    /**
     * @param outcome_set_model $outcomeset
     * @return import_abstract
     */
    public function set_outcomeset($outcomeset) {
        $this->outcomeset = $outcomeset;
        return $this;
    }

    /**
     * @throws coding_exception
     * @return outcome_set_model
     */
    public function get_outcomeset() {
        if (!$this->outcomeset instanceof outcome_set_model) {
            throw new coding_exception('The outcome set property has not been set yet');
        }
        return $this->outcomeset;
    }

    /**
     * Set a key/value pair to the map.
     *
     * The key must be unique.  Also, the value is usually
     * just an outcome ID or null to signify root.
     *
     * @param string $key
     * @param mixed $value
     * @throws coding_exception
     */
    public function set_map_value($key, $value) {
        if (array_key_exists($key, $this->map)) {
            throw new coding_exception("Outcome with $key as already been created, duplicate detected");
        }
        $this->map[$key] = $value;
    }

    /**
     * Lookup a value in the map by a unique key
     *
     * @param string $key
     * @return mixed
     * @throws coding_exception
     */
    public function get_map_value($key) {
        if (!array_key_exists($key, $this->map)) {
            throw new coding_exception("Outcome with $key has not been created yet");
        }
        return $this->map[$key];
    }

    /**
     * Save the outcome set.
     *
     * @param outcome_set_model $model
     */
    public function save_outcome_set(outcome_set_model $model) {
        $this->outcomesethelper->save_outcome_set($model);
        $this->set_outcomeset($model);
    }

    /**
     * Save an outcome.
     *
     * Outcome set ID is automatically applied for you.
     *
     * Sort order can automatically be applied for you if you are
     * saving the outcomes in order.
     *
     * @param outcome_model $model
     */
    public function save_outcome(outcome_model $model) {
        $model->outcomesetid = $this->get_outcomeset()->id;

        // The assumption is, if you set sort order already, then you are managing it yourself!
        if (empty($model->sortorder)) {
            $model->sortorder = $this->sortorder;
            $this->sortorder++;
        }
        $this->outcomehelper->save_outcome($model);
    }
}
