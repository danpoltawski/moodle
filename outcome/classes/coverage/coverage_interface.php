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
 * Outcome Coverage Interface
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\coverage;

defined('MOODLE_INTERNAL') || die();

/**
 * Assists with finding content that has not been mapped to outcomes.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface coverage_interface {
    /**
     * Get the header for the unmapped content.
     *
     * @return string
     */
    public function get_unmapped_content_header();

    /**
     * Get the unmapped content html_table.
     *
     * @return \html_table Table of unmapped content
     */
    public function get_unmapped_content();

    /**
     * Get the course id.
     *
     * @return int
     */
    public function get_courseid();

    /**
     * Set the course id.
     *
     * @param $courseid
     */
    public function set_courseid($courseid);
}
