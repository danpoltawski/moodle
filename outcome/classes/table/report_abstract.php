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
 * Table abstract
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\table;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/tablelib.php');

/**
 * Common reporting functionality.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_abstract extends \table_sql {
    /**
     * A flag to toggle adding JS for dynamic panels
     *
     * @var bool
     */
    protected $loadpanelscript = false;

    /**
     * Standard init of table export
     *
     * @param string $filename Download file name
     */
    protected function init_download($filename) {

        $filename = clean_filename(str_replace(' ', '_', $filename));

        $this->is_downloading(optional_param('download', '', PARAM_ALPHA), $filename);
        $this->show_download_buttons_at(array(TABLE_P_BOTTOM));
    }

    /**
     * Format a number value.  If value is null, returns '-' instead.
     *
     * @param $value
     * @param int $precision
     * @return float|null|string
     */
    protected function format_percentage($value, $precision = 0) {
        if ($this->is_downloading()) {
            if (is_null($value)) {
                return null;
            }
            return round($value, $precision);
        }
        if (is_null($value)) {
            return '-';
        }
        return round($value, $precision).'%';
    }

    /**
     * @param object $row Table row
     * @param string $action The controller action
     * @param string $text The link text
     * @return string
     */
    protected function panel_link($row, $action, $text) {
        if ($text == '-' or is_null($text) or $this->is_downloading()) {
            return $text;
        }
        $this->loadpanelscript = true;

        return \html_writer::link('#', $text, array_merge(array(
            'class' => 'dynamic-panel',
            'data-request-action' => $action,
        ), $this->panel_data($row, $action)));
    }

    /**
     * Override to customize data sent to panel
     *
     * @param $row
     * @param $action
     * @return array
     */
    protected function panel_data($row, $action) {
        return array();
    }

    /**
     * If panels are used, then add JS to power them
     */
    public function finish_html() {
        global $PAGE;

        parent::finish_html();

        if ($this->started_output and $this->loadpanelscript and !$this->is_downloading()) {
            $PAGE->requires->yui_module(
                'moodle-core_outcome-dynamicpanel',
                'M.core_outcome.init_dynamicpanel',
                array(array(
                    'contextId'        => $PAGE->context->id,
                    'delegateSelector' => '#'.$this->attributes['id'],
                    'actionSelector'   => 'a.dynamic-panel',
                ))
            );
            $PAGE->requires->strings_for_js(array('close'), 'outcome');
        }
    }
}