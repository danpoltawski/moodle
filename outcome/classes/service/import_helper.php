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
 * Internal Service: Outcome Set Import Helper
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\service;

use core_outcome\factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Helps with importing of outcome sets.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_helper {
    /**
     * @var \moodle_database
     */
    protected $db;

    /**
     * @var factory
     */
    protected $factory;

    public function __construct(factory $factory = null, \moodle_database $db = null) {
        global $DB;

        if (is_null($factory)) {
            $factory = new factory();
        }
        if (is_null($db)) {
            $db = $DB;
        }
        $this->db      = $db;
        $this->factory = $factory;
    }

    /**
     * Same as moodleform::save_temp_file() except that is
     * preserves the file extension.
     *
     * @param \moodleform $mform
     * @param $elementname
     * @return bool|string
     */
    public function save_temp_file(\moodleform $mform, $elementname) {
        if (!$filename = $mform->get_new_filename($elementname)) {
            return false;
        }
        $tempfile = $mform->save_temp_file($elementname);
        $newfile  = $tempfile.'.'.pathinfo($filename, PATHINFO_EXTENSION);

        if (!rename($tempfile, $newfile)) {
            @unlink($tempfile);
            return false;
        }
        return $newfile;
    }

    /**
     * @param string $component
     * @param string $file
     * @return null|\core_outcome\model\outcome_set_model
     */
    public function import_outcome_set($component, $file) {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');

        $result      = null;
        $transaction = $this->db->start_delegated_transaction();
        try {
            $import = $this->factory->build_importer($component);
            $import->process_file($file);
            $result = $import->get_result();

            $transaction->allow_commit();
            fulldelete($file);

        } catch (\Exception $e) {
            fulldelete($file);
            $transaction->rollback($e);
        }
        return $result;
    }
}
