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
 * View course outcome sets and related information
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\table;

use html_writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/tablelib.php');

/**
 * This table shows the outcome sets that have been mapped to the course.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_outcome_sets_table extends \table_sql {
    public function __construct() {
        global $COURSE, $PAGE;

        parent::__construct(__CLASS__);

        $reports = array();
        if (has_capability('moodle/grade:edit', $PAGE->context)) {
            $reports[] = 'report:marking_table';
            $reports[] = 'report:course_performance_table';
            $reports[] = 'report:course_coverage_table';
        }

        $columns = array_merge(array('name'), $reports);
        $headers = array_merge(array(get_string('fullname', 'outcome')), array_fill(0, count($reports), ''));

        $this->set_attribute('id', 'course-outcome-sets');
        $this->define_columns($columns);
        $this->define_headers($headers);

        array_map(array($this, 'no_sorting'), $reports);
        $this->collapsible(false);

        $from = '{outcome_sets} s INNER JOIN {outcome_used_sets} us ON s.id = us.outcomesetid';
        $this->set_sql('s.id, s.name', $from, 'us.courseid = ? AND s.deleted = ?', array($COURSE->id, 0));
    }

    public function col_name($row) {
        return format_string($row->name);
    }

    public function other_cols($column, $row) {
        return $this->_report_link($row, $column);
    }

    public function wrap_html_finish() {
        global $PAGE;

        if (!has_capability('moodle/outcome:mapoutcomes', $PAGE->context)) {
            return;
        }
        /** @var $url \moodle_url */
        $url = clone($this->baseurl);
        $url->params(array(
            'action' => 'report_course_unmapped_table',
        ));
        echo html_writer::tag('div', html_writer::link($url, get_string('report:course_unmapped_table', 'outcome')),
                array('id' => 'outcome-unmapped-content-link'));
    }

    function print_nothing_to_display() {
        global $CFG, $COURSE, $OUTPUT;

        echo $OUTPUT->box(get_string('nooutcomesetsmapped', 'outcome', $CFG->wwwroot.'/course/edit.php?id='.$COURSE->id));
    }

    protected function _report_link($row, $identifier, $action = null) {
        if (is_null($action)) {
            $action = str_replace(':', '_', $identifier);
        }
        /** @var $url \moodle_url */
        $url = clone($this->baseurl);
        $url->params(array(
            'action'            => $action,
            'forceoutcomesetid' => $row->id,
        ));
        return html_writer::link($url, get_string($identifier, 'outcome'),
            array('title' => get_string($identifier.'x', 'outcome', format_string($row->name))));
    }
}