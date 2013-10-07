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
 * Internal Service: Outcome Set Export Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\export\export_interface;
use core_outcome\factory;
use core_outcome\model\outcome_repository;
use core_outcome\model\outcome_set_model;
use core_outcome\model\outcome_set_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Helps with exporting of outcome sets.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_helper {
    /**
     * @var factory
     */
    protected $factory;

    /**
     * @var outcome_repository
     */
    protected $outcomes;

    /**
     * @var outcome_set_repository
     */
    protected $outcomesets;

    /**
     * @var \moodle_database
     */
    protected $db;

    public function __construct(factory $factory = null,
                                outcome_repository $outcomes = null,
                                outcome_set_repository $outcomesets = null,
                                \moodle_database $db = null) {
        global $DB;

        if (is_null($factory)) {
            $factory = new factory();
        }
        if (is_null($outcomes)) {
            $outcomes = new outcome_repository();
        }
        if (is_null($outcomesets)) {
            $outcomesets = new outcome_set_repository();
        }
        if (is_null($db)) {
            $db = $DB;
        }
        $this->db          = $db;
        $this->factory     = $factory;
        $this->outcomes    = $outcomes;
        $this->outcomesets = $outcomesets;
    }

    /**
     * Create a temporary file that will store the export
     *
     * @return string
     * @throws \coding_exception
     */
    public function create_temp_file() {
        $dir = make_temp_directory('outcomeexport');
        if (!$tempfile = tempnam($dir, 'outcome_export_')) {
            throw new \coding_exception('Failed to make temporary file for export');
        }
        return $tempfile;
    }

    /**
     * Given an outcome set and exporter, create an appropriate file name
     *
     * @param outcome_set_model $model
     * @param export_interface $exporter
     * @return string
     */
    public function get_file_name(outcome_set_model $model, export_interface $exporter) {
        $filename = str_replace(' ', '_', html_to_text(format_string($model->name)));
        $filename = \core_text::strtolower(trim(clean_filename($filename), '_'));
        $filename = preg_replace('/_{2,}/', '_', $filename); // Replace duplicate underscores.
        $filename .= '.'.$exporter->get_extension();

        return $filename;
    }

    /**
     * Export an outcome set by an outcome ID.
     *
     * @param string $component An outcome export component
     * @param int $outcomesetid
     * @return array The file path and file name
     */
    public function export_outcome_set_by_id($component, $outcomesetid) {
        $outcomeset = $this->outcomesets->find($outcomesetid, MUST_EXIST);
        $outcomes   = $this->outcomes->find_by_outcome_set($outcomeset, true);

        return $this->export_outcome_set($component, $outcomeset, $outcomes);
    }

    /**
     * Export an outcome set.
     *
     * @param string $component An outcome export component
     * @param outcome_set_model $outcomeset
     * @param \core_outcome\model\outcome_model[] $outcomes
     * @return array The file path and file name
     */
    public function export_outcome_set($component, outcome_set_model $outcomeset, array $outcomes) {
        $exporter = $this->factory->build_exporter($component);
        $tempfile = $this->create_temp_file();

        $exporter->export($tempfile, $outcomeset, $outcomes);

        return array($tempfile, $this->get_file_name($outcomeset, $exporter));
    }

    /**
     * Send the export file to the browser to be downloaded.
     *
     * @param string $path The full path to the export file
     * @param string $filename The name to use for the file
     * @param bool $delete Delete the file after sending
     */
    public function send_export_file($path, $filename, $delete = true) {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');

        send_file($path, $filename, 0, false, false, true, '', true);

        if ($delete) {
            fulldelete($path);
        }
    }
}
