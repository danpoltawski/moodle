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
 * Outcome Mark History Model
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\model;

defined('MOODLE_INTERNAL') || die();

/**
 * This model represents a single part of
 * an audit trail of edits to
 * \core_outcome\model\mark_model
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_history_model {
    /**
     * The mark was inserted into the database.
     */
    const ACTION_CREATE = 1;

    /**
     * The mark was updated in the database.
     */
    const ACTION_UPDATE = 2;

    /**
     * The mark was deleted from the database.
     */
    const ACTION_DELETE = 3;

    public $id;
    public $action;
    public $outcomemarkid;
    public $courseid;
    public $outcomeid;
    public $userid;
    public $graderid;
    public $result;
    public $timecreated;
}