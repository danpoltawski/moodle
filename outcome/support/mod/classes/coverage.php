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
 * Coverage information for activities
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace outcomesupport_mod;

use core_outcome\coverage\coverage_abstract;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coverage extends coverage_abstract {
    public function get_unmapped_content_header() {
        return get_string('unmappedactivitiesandresources', 'outcome');
    }

    /**
     * Get a list of activities are not mapped
     * to any mappable outcomes.
     *
     * @return array
     */
    protected function get_unmapped_activities() {
        global $DB;

        $concatsql = $DB->sql_concat(':modprefix', 'mods.name');
        $unmapped  = $DB->get_records_sql("
            SELECT cm.id, COUNT(u.id) areacount
              FROM {course_modules} cm
        INNER JOIN {modules} mods ON mods.id = cm.module AND mods.visible = :modvisible
         LEFT JOIN {outcome_areas} a ON a.itemid = cm.id AND a.area = :area AND component = $concatsql
         LEFT JOIN {outcome_used_areas} u ON u.cmid = cm.id
             WHERE cm.course = :courseid AND a.id IS NULL
          GROUP BY cm.id",
            array('courseid' => $this->courseid, 'modvisible' => 1, 'area' => 'mod', 'modprefix' => 'mod_'));

        return $this->add_invalid_mapped_activities($unmapped);
    }

    /**
     * This adds activities that are mapped to outcomes, but
     * those outcomes are not associated to the course.
     *
     * @param array $unmapped
     * @return array
     */
    protected function add_invalid_mapped_activities($unmapped) {
        global $DB;

        $outcomesql = $this->course_outcomes_sql();
        if (empty($outcomesql)) {
            return $unmapped;
        }
        list($sql, $params) = $outcomesql;
        $params['courseid'] = $this->courseid;

        $mapinfos = $DB->get_records_sql("
            SELECT cm.id, COUNT(outcomes.id) validcount
              FROM {course_modules} cm
        INNER JOIN {outcome_used_areas} u ON u.cmid = cm.id
        INNER JOIN {outcome_areas} a ON a.id = u.outcomeareaid
        INNER JOIN {outcome_area_outcomes} ao ON a.id = ao.outcomeareaid
         LEFT JOIN ($sql) outcomes ON outcomes.id = ao.outcomeid
             WHERE cm.course = :courseid
          GROUP BY cm.id
        ", $params);

        foreach ($mapinfos as $mapinfo) {
            if (empty($mapinfo->validcount)) {
                $unmapped[$mapinfo->id] = (object) array(
                    'id'        => $mapinfo->id,
                    'areacount' => 0, // Zero out because none are valid anyways.
                );
            }
        }
        return $unmapped;
    }

    public function get_unmapped_content() {
        global $OUTPUT;

        $table        = new \html_table();
        $table->head  = array(get_string('section'), get_string('title', 'outcome'), '');
        $table->data  = array();
        $table->attributes['class'] = 'generaltable outcome-unmapped-activities';

        $unmapped = $this->get_unmapped_activities();
        if (!empty($unmapped)) {
            $modinfo = get_fast_modinfo($this->courseid);

            foreach ($unmapped as $cm) {
                if (!array_key_exists($cm->id, $modinfo->get_cms())) {
                    debugging("Course Module ID doesn't exist: $cm->id", DEBUG_DEVELOPER);
                    continue; // Invalid, can be for several reasons.
                }
                $cminfo = $modinfo->get_cm($cm->id);
                $sectionname = course_get_format($this->courseid)->get_section_name($cminfo->sectionnum);
                $mapit = \html_writer::link(new \moodle_url('/course/mod.php', array('update' => $cm->id)),
                        get_string('map', 'outcome'));

                if (!empty($cm->areacount)) {
                    $mapit .= ' '.$OUTPUT->help_icon('notmappedwarning', 'outcome');
                }
                $table->data[] = array($sectionname, format_string($cminfo->name), $mapit);
            }
        }
        return $table;
    }
}
