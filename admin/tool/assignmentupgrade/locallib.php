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
 * Assignment upgrade tool library functions
 *
 * @package    tool_assignmentupgrade
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get the URL of a script within this plugin.
 * @param string $script the script name, without .php. E.g. 'index'
 * @param array $params URL parameters (optional)
 * @return moodle_url
 */
function tool_assignmentupgrade_url($script, $params = array()) {
    return new moodle_url('/admin/tool/assignmentupgrade/' . $script . '.php', $params);
}


/**
 * Class to encapsulate one of the functionalities that this plugin offers.
 *
 * @package    tool_assignmentupgrade
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_assignmentupgrade_action {
    /** @var string the name of this action. */
    public $name;
    /** @var moodle_url the URL to launch this action. */
    public $url;
    /** @var string a description of this aciton. */
    public $description;

    /**
     * Constructor to set the fields.
     *
     * In order to create a new tool_assignmentupgrade_action instance you must use the tool_assignmentupgrade_action::make
     * method.
     *
     * @param string $name the name of this action.
     * @param moodle_url $url the URL to launch this action.
     * @param string $description a description of this aciton.
     */
    protected function __construct($name, moodle_url $url, $description) {
        $this->name = $name;
        $this->url = $url;
        $this->description = $description;
    }

    /**
     * Make an action with standard values.
     * @param string $shortname internal name of the action. Used to get strings and build a URL.
     * @param array $params any URL params required.
     * @return tool_assignmentupgrade_action
     */
    public static function make($shortname, $params = array()) {
        return new self(
                get_string($shortname, 'tool_assignmentupgrade'),
                tool_assignmentupgrade_url($shortname, $params),
                get_string($shortname . '_desc', 'tool_assignmentupgrade'));
    }
}


/**
 * A class to represent a list of assignments with various information about plugins that can be displayed as a table.
 *
 * @package    tool_assignmentupgrade
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_assignmentupgrade_assignment_list {
    public $title;
    public $intro;
    public $sql;
    public $assignmentlist = null;
    public $totalassignments = 0;
    public $totalupgradable = 0;
    public $totalsubmissions = 0;

    /**
     * Constructor
     *
     * @global moodle_database $DB
     */
    public function __construct() {
        global $DB;
        $this->title = get_string('notupgradedtitle', 'tool_assignmentupgrade');
        $this->intro = get_string('notupgradedintro', 'tool_assignmentupgrade');
        $this->build_sql();
        $this->assignmentlist = $DB->get_records_sql($this->sql);
    }

    /**
     * Check to see whether the assignment type is upgradeable.
     * @param string $type The assignment type to check
     * @return bool Returns true if the given type can be upgraded.
     */
    protected function is_upgradable($type) {
        global $CFG;
        $version = get_config('assignment_' . $type, 'version');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        return assignment::can_upgrade_assignment($type, $version);
    }

    /**
     * Sets the SQL used to gather the required information about assignments
     */
    protected function build_sql() {
        $this->sql = 'SELECT a.id, a.name, a.assignmenttype, c.shortname, c.id AS courseid, COUNT(s.id) as submissioncount
                        FROM {assignment} a
                        JOIN {course} c ON c.id = a.course
                   LEFT JOIN {assignment_submissions} s ON a.id = s.assignment
                    GROUP BY a.id, a.name, a.assignmenttype, c.shortname, c.id
                    ORDER BY c.shortname, a.name, a.id';
    }

    /**
     * Returns an array of strings to use as column headings
     * @return array
     */
    public function get_col_headings() {
        return array(
            get_string('assignmentid', 'tool_assignmentupgrade'),
            get_string('course'),
            get_string('name'),
            get_string('assignmenttype', 'tool_assignmentupgrade'),
            get_string('submissions', 'tool_assignmentupgrade'),
            get_string('upgradable', 'tool_assignmentupgrade'),
        );
    }

    /**
     * This function converts a row from the database into a row in the table ready for display.
     *
     * The columns returned should match the column heading returned by {@see tool_assignmentupgrade_assignment_list::get_col_headings()}
     * @param stdClass $assignmentinfo
     * @return array
     */
    public function get_row($assignmentinfo) {
        $this->totalassignments += 1;
        $upgradable = $this->is_upgradable($assignmentinfo->assignmenttype);
        if ($upgradable) {
            $this->totalupgradable += 1;
        }
        $this->totalsubmissions += $assignmentinfo->submissioncount;
        return array(
            $assignmentinfo->id,
            html_writer::link(new moodle_url('/course/view.php', array('id' => $assignmentinfo->courseid)), format_string($assignmentinfo->shortname)),
            html_writer::link(new moodle_url('/mod/assignment/view.php', array('a' => $assignmentinfo->id)), format_string($assignmentinfo->name)),
            $assignmentinfo->assignmenttype,
            $assignmentinfo->submissioncount,
            $upgradable ? 
            html_writer::link(new moodle_url('/admin/tool/assignmentupgrade/upgradesingleconfirm.php',
                    array('id' => $assignmentinfo->id)), get_string('supported', 'tool_assignmentupgrade'))
            : get_string('notsupported', 'tool_assignmentupgrade'));
    }

    /**
     * Returns a CSS class to apply to the row or null if there are none
     * @param stdClass|array $assignmentinfo
     * @return null
     */
    public function get_row_class($assignmentinfo) {
        return null;
    }

    /**
     * @return array
     */
    public function get_total_row() {
        return array(
            '',
            html_writer::tag('b', get_string('total')),
            '',
            html_writer::tag('b', $this->totalassignments),
            html_writer::tag('b', $this->totalsubmissions),
            html_writer::tag('b', $this->totalupgradable),
        );
    }

    /**
     * Returns true if there are no assignments left to upgrade.
     * @return bool
     */
    public function is_empty() {
        return empty($this->assignmentlist);
    }
}

/**
 * Convert a single assignment from the old format to the new one.
 * @param stdClass $assignmentinfo An object containing information about this class
 * @param string $log This gets appended to with the details of the conversion process
 * @return boolean This is the overall result (true/false)
 */
function tool_assignmentupgrade_upgrade_assignment($assignmentinfo, &$log) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    require_once($CFG->dirroot . '/mod/assign/upgradelib.php');
    $assignment_upgrader = new assignment_upgrade_manager();
    return $assignment_upgrader->upgrade_assignment($assignmentinfo->id, $log);
}

/**
 * Get the information about a assignment to be upgraded.
 * @param int $assignmentid the assignment id.
 * @return stdClass the information about that assignment, as for {@see tool_assignmentupgrade_get_upgradable_assignments()}.
 */
function tool_assignmentupgrade_get_assignment($assignmentid) {
    global $DB;
    return $DB->get_record_sql("
            SELECT a.id, a.name, c.shortname, c.id AS courseid
            FROM {assignment} a
            JOIN {course} c ON c.id = a.course
            WHERE a.id = ?", array($assignmentid));
}

