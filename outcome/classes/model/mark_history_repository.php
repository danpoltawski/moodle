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
 * Mark History Model Repository Mapper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\model;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_history_repository extends abstract_repository {
    protected $model = '\core_outcome\model\mark_history_model';
    protected $table = 'outcome_marks_history';

    /**
     * @param mark_model $mark
     * @return mark_history_model
     */
    public function map_mark_to_model(mark_model $mark) {
        $model                = new mark_history_model();
        $model->outcomemarkid = $mark->id;
        $model->courseid      = $mark->courseid;
        $model->outcomeid     = $mark->outcomeid;
        $model->userid        = $mark->userid;
        $model->graderid      = $mark->graderid;
        $model->result        = $mark->result;

        return $model;
    }

    public function create_mark(mark_model $mark) {
        $this->handle_action($mark, mark_history_model::ACTION_CREATE);
    }

    public function update_mark(mark_model $mark) {
        $this->handle_action($mark, mark_history_model::ACTION_UPDATE);
    }

    public function delete_mark(mark_model $mark) {
        $this->handle_action($mark, mark_history_model::ACTION_DELETE);
    }

    public function handle_action(mark_model $mark, $action) {
        $model         = $this->map_mark_to_model($mark);
        $model->action = $action;

        $this->save($model);
    }

    public function save(mark_history_model $model) {
        $model->timecreated = time();
        $model->id = $this->db->insert_record($this->table, $model);
    }

    /**
     * Removes history records that are OLDER than the passed timestamp
     *
     * @param int $time
     */
    public function remove_old($time) {
        $this->db->delete_records_select($this->table, 'timecreated < ?', array($time));
    }
}