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
 * Administrative table for outcome sets
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\table;

use moodle_url;
use html_writer;

defined('MOODLE_INTERNAL') || die();

/**
 * All of the outcome sets on the site and various
 * administrative actions that can be taken on them.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_outcome_sets_table extends report_abstract {
    public function __construct() {
        parent::__construct(__CLASS__);

        $this->set_attribute('id', 'manage-outcome-sets');
        $this->define_columns(array('name', 'count', 'action'));
        $this->define_headers(array(
            get_string('fullname', 'outcome'),
            get_string('mappedcourses', 'outcome'),
            get_string('editdeleteexport', 'outcome'),
        ));
        $this->column_class('count', 'col_mapped_courses');
        $this->no_sorting('action');
        $this->no_sorting('reports');
        $this->collapsible(false);

        $from = '{outcome_sets} s
            LEFT OUTER JOIN (
                SELECT outcomesetid, count(*) count
                FROM {outcome_used_sets}
                GROUP BY outcomesetid
            ) used ON s.id = used.outcomesetid';

        $this->set_sql('s.id, s.name, used.count', $from, 'deleted = ?', array(0));
    }

    protected function panel_data($row, $action) {
        if ($action == 'get_mapped_courses') {
            return array(
                'title' => get_string('mappedcoursesforx', 'outcome', format_string($row->name)),
                'data-request-outcomesetid' => $row->id,
            );
        }
        return array();
    }

    public function col_name($row) {
        return $this->_edit_link($row->name, $row->id);
    }

    public function col_count($row) {
        if (empty($row->count)) {
            return '0';
        }
        return $this->panel_link($row, 'get_mapped_courses', $row->count);
    }

    public function col_action($row) {
        $actions = array();
        $actions[] = $this->_edit_link($row->name, $row->id, get_string('edit', 'outcome'));
        $actions[] = $this->_delete_link($row->name, $row->id);
        $actions[] = $this->_export_link($row->name, $row->id);

        return implode('&nbsp;', $actions);
    }

    protected function _delete_link($name, $id, $title = null) {
        $url = new moodle_url('/outcome/admin.php', array(
            'action'       => 'outcomeset_delete',
            'sesskey'      => sesskey(),
            'outcomesetid' => $id,
        ));

        $name = format_string($name);
        if (is_null($title)) {
            $title = get_string('delete', 'outcome');
        }
        return html_writer::link($url, $title,
            array('title' => get_string('deletex', 'outcome', $name)));
    }

    protected function _edit_link($name, $id, $title = null) {
        $url = new moodle_url('/outcome/admin.php', array(
            'action'       => 'outcomeset_edit',
            'outcomesetid' => $id,
        ));

        $name = format_string($name);
        if (is_null($title)) {
            $title = $name;
        }
        return html_writer::link($url, $title,
            array('title' => get_string('editx', 'outcome', $name)));
    }

    protected function _export_link($name, $id, $title = null) {
        $url = new moodle_url('/outcome/download.php', array(
            'action'       => 'outcomeset_export',
            'outcomesetid' => $id,
        ));

        $name = format_string($name);
        if (is_null($title)) {
            $title = get_string('export', 'outcome');
        }
        return html_writer::link($url, $title,
            array('title' => get_string('exportx', 'outcome', $name)));
    }
}