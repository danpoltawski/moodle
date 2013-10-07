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
 * Defines backup_outcome_area_plugin class
 *
 * @package    core_backup
 * @subpackage moodle2
 * @category   backup
 * @copyright  Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Assists with backing up outcome areas and the outcomes associated
 * to each area.
 *
 * @package    core_backup
 * @subpackage moodle2
 * @category   backup
 * @copyright  Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class backup_outcome_area_plugin extends backup_plugin {
    /**
     * All four of these params are passed to set_source_sql in order to fetch
     * the correct outcome_areas record.  This means each param can be something
     * like backup::VAR_PARENTID, backup_helper::is_sqlparam(...), XML Path, etc.
     *
     * @param backup_nested_element $element The element to add the outcome area structure to
     * @param mixed $plugintype The plugin name for the outcome area (used to create component)
     * @param mixed $pluginname The plugin type for the outcome area (used to create component)
     * @param mixed $outcomearea The outcome area name
     * @param mixed $outcomeitemid The outcome area item ID
     */
    public function define_outcome_area_structure(backup_nested_element $element, $plugintype, $pluginname, $outcomearea, $outcomeitemid) {
        global $CFG, $DB;

        if (empty($CFG->core_outcome_enable)) {
            return;
        }
        // Note: there is only ever one area, but ensure XML looks right and should never fail/have problems.
        $areas    = new backup_nested_element('outcome_areas');
        $area     = new backup_nested_element('outcome_area', array('id'), array('component', 'area', 'itemid'));
        $outcomes = new backup_nested_element('outcome_area_outcomes');
        $outcome  = new backup_nested_element('outcome_area_outcome', array('id'), array('idnumber', 'outcomeareaid'));

        $element->add_child($areas);
        $areas->add_child($area);
        $area->add_child($outcomes);
        $outcomes->add_child($outcome);

        $concatsql = $DB->sql_concat(':plugintype', ':separator', ':pluginname');

        $area->set_source_sql("
            SELECT *
              FROM {outcome_areas}
             WHERE component = $concatsql
               AND area = :area
               AND itemid = :itemid
        ", array(
            'plugintype' => $plugintype,
            'separator'  => backup_helper::is_sqlparam('_'),
            'pluginname' => $pluginname,
            'area'       => $outcomearea,
            'itemid'     => $outcomeitemid,
        ));

        $outcome->set_source_sql('
            SELECT o.id, o.idnumber, ao.outcomeareaid
              FROM {outcome} o
        INNER JOIN {outcome_area_outcomes} ao ON o.id = ao.outcomeid
        INNER JOIN {outcome_areas} a ON a.id = ao.outcomeareaid
             WHERE a.id = ?
        ', array(backup::VAR_PARENTID));
    }
}
