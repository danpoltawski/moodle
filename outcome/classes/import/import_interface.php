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
 * Outcome Set Import Interface
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\import;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for importing an outcome set
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface import_interface {
    /**
     * Import the outcome set found in the passed file.
     *
     * @param string $file Absolute path to the file that should be imported
     * @return void
     */
    public function process_file($file);

    /**
     * Return null if nothing happened.
     * Return instance of \core_outcome\model\outcome_set_model if
     * an outcome set was created.
     *
     * Otherwise, throw exceptions as needed for errors.
     *
     * @return null|\core_outcome\model\outcome_set_model
     */
    public function get_result();
}
