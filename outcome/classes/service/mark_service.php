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
 * Outcome Marking Service
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\model\mark_history_repository;
use core_outcome\model\mark_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Marks Service
 *
 * Provides some cleanup routines for marking tables.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_service {
    /**
     * @var mark_repository
     */
    protected $marks;

    /**
     * @var mark_history_repository
     */
    protected $history;

    /**
     * @param mark_repository $marks
     * @param mark_history_repository $history
     */
    public function __construct(mark_repository $marks = null,
                                mark_history_repository $history = null) {
        if (is_null($history)) {
            $history = new mark_history_repository();
        }
        if (is_null($marks)) {
            $marks = new mark_repository(null, $history);
        }
        $this->marks   = $marks;
        $this->history = $history;
    }

    /**
     * @param $courseid
     */
    public function remove_course_marks($courseid) {
        $this->marks->remove_by_courseid($courseid);
    }

    /**
     * Remove old mark history records
     */
    public function clean_history() {
        global $CFG;

        if (!empty($CFG->gradehistorylifetime)) { // Value in days.
            $time = time() - ($CFG->gradehistorylifetime * 3600 * 24);
            $this->history->remove_old($time);
        }
    }
}
