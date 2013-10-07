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
 * Outcome Set Export Interface
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\export;

use core_outcome\model\outcome_set_model;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for exporting outcome sets.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface export_interface {
    /**
     * Get the extension of the file format that is created
     * by this exporter.  EG: if you generate a XML file,
     * then this should return a 'xml' string.
     *
     * @return string
     */
    public function get_extension();

    /**
     * @param string $file The file to write the export to
     * @param outcome_set_model $outcomeset
     * @param \core_outcome\model\outcome_model[] $outcomes
     * @return string The file created from the export
     */
    public function export($file, outcome_set_model $outcomeset, array $outcomes);
}
