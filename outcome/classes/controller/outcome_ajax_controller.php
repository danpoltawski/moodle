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
 * Outcome AJAX Controller
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\controller;

use core_outcome\model\outcome_model;
use core_outcome\service\outcome_helper;
use core_outcome\normalizer;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles actions regarding outcomes.
 *
 * These are expected to be AJAX requests.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_ajax_controller extends controller_abstract {
    /**
     * @var normalizer
     */
    public $normalizer;

    /**
     * @var outcome_helper
     */
    public $outcomehelper;

    public function init($action) {
        parent::init($action);
        $this->normalizer    = new normalizer();
        $this->outcomehelper = new outcome_helper();
    }

    /**
     * Do any security checks needed for the passed action
     *
     * @abstract
     * @param string $action
     * @throws \coding_exception
     */
    public function require_capability($action) {
        global $PAGE;

        switch ($action) {
            case 'validate_outcome':
                require_capability('moodle/outcome:edit', $PAGE->context);
                break;
            default:
                throw new \coding_exception("Missing capability check for $action action");
        }
    }

    /**
     * Validates and cleans an outcome model.
     *
     * Returns the cleaned and validated model or an object
     * with an array of errors.
     *
     * @return string
     */
    public function validate_outcome_action() {
        require_sesskey();

        $data = required_param('data', PARAM_RAW_TRIMMED);
        $data = json_decode($data);

        $model = new outcome_model();
        $this->outcomehelper->map_to_outcome($model, $data);
        $this->outcomehelper->clean_outcome($model);
        $errors = $this->outcomehelper->validate_outcome($model, false, false);

        if (!empty($errors)) {
            $codes = array();
            foreach ($errors as $error) {
                $codes[] = $error->errorcode;
            }
            return json_encode(array('errors' => $codes));
        }
        return json_encode($this->normalizer->normalize_outcome($model, true));
    }
}