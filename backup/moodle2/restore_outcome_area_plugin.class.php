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
 * Defines restore_outcome_area_plugin class
 *
 * @package    core_backup
 * @subpackage moodle2
 * @category   backup
 * @copyright  Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Assists with restoring outcome areas and the outcomes associated
 * to each area.  Outcome must exist in Moodle already, they are
 * not restored themselves.
 *
 * @package    core_backup
 * @subpackage moodle2
 * @category   backup
 * @copyright  Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class restore_outcome_area_plugin extends restore_plugin {
    /**
     * This is passed to the get_mappingid(...) function and
     * used to lookup the new ID for the outcome area item ID.
     *
     * @var string
     */
    protected $areaitemid;

    /**
     * @var null|object
     */
    protected $lastoutcomearea = false;

    /**
     * @param string $areaitemid This is passed to the get_mappingid(...) function and
     *               used to lookup the new ID for the outcome area item ID.
     * @param string $prefix XML path prefix for restore paths
     * @return array
     */
    public function define_outcome_area_structure($areaitemid, $prefix = '') {
        global $CFG;

        if (empty($CFG->core_outcome_enable)) {
            return array();
        }
        $this->areaitemid = $areaitemid;

        return array(
            new restore_path_element('outcome_area', $this->get_pathfor($prefix.'/outcome_areas/outcome_area')),
            new restore_path_element('outcome_area_outcome',
                $this->get_pathfor($prefix.'/outcome_areas/outcome_area/outcome_area_outcomes/outcome_area_outcome'))
        );
    }

    /**
     * Extract the outcome area from backup data.
     *
     * @param array|object $data
     */
    public function process_outcome_area($data) {
        $data         = (object) $data;
        $data->itemid = $this->get_mappingid($this->areaitemid, $data->itemid);

        if ($data->itemid !== false) {
            $this->lastoutcomearea = $data;
        }
    }

    /**
     * Map an outcome to the outcome area.
     *
     * @param array|object $data
     */
    public function process_outcome_area_outcome($data) {
        global $DB;

        $data  = (object) $data;
        $oldid = $data->id;
        $id    = $DB->get_field('outcome', 'id', array('idnumber' => $data->idnumber));

        if (!empty($id)) {
            $this->enure_outcome_area_created();

            $areaid = $this->get_mappingid('outcome_area', $data->outcomeareaid);
            if ($areaid === false) {
                return;
            }
            $newid = $DB->insert_record('outcome_area_outcomes', (object) array(
                'outcomeid'     => $id,
                'outcomeareaid' => $areaid,
            ));
            $this->set_mapping('outcome_area_outcome', $oldid, $newid);
        }
    }

    /**
     * We lazy save this because we don't want the record created unless
     * we actually find an outcome mapped to the area.
     */
    protected function enure_outcome_area_created() {
        global $DB;

        if (!empty($this->lastoutcomearea)) {
            $oldid = $this->lastoutcomearea->id;
            $newid = $DB->insert_record('outcome_areas', $this->lastoutcomearea);
            $this->set_mapping('outcome_area', $oldid, $newid);

            $this->lastoutcomearea = null;
        }
    }
}
