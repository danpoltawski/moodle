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
 * Outcome Factory
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome;

use cm_info;
use coding_exception;
use core_outcome\area\area_info_unknown;
use core_outcome\model\area_model;

defined('MOODLE_INTERNAL') || die();

/**
 * Assists with building instances of specific classes.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class factory {
    /**
     * Given a component and class name suffix, create a full
     * class name and ensure that it exists.
     *
     * Classes are namespace based.
     *
     * @param string $component The component to find the class file in
     * @param string $suffix This is appended to the component name, together make the class name we want
     * @return string
     * @throws \coding_exception
     */
    protected function build_class_name($component, $suffix) {
        $classname = "\\$component\\$suffix";
        if (!class_exists($classname)) {
            throw new coding_exception("Expected to find $classname in the classes directory of $component");
        }
        return $classname;
    }

    /**
     * @param string $class Create a new instance of this class
     * @param null|string $parent Ensure that this is the parent class
     * @return mixed
     * @throws coding_exception
     */
    protected function build_generic_instance($class, $parent = null) {
        if (!is_null($parent)) {
            $reflection = new \ReflectionClass($class);
            if (!$reflection->isSubclassOf($parent)) {
                throw new coding_exception("The $class must be a subclass of $parent");
            }
        }
        return new $class();
    }

    /**
     * Build an area info instance.
     *
     * @param area_model $model
     * @param null|cm_info $cm The course module that the area is associated to (EG: the cmid in outcome_used_areas)
     * @throws coding_exception
     * @return \core_outcome\area\area_info_interface
     */
    public function build_area_info(area_model $model, cm_info $cm = null) {
        $normalized = \core_component::normalize_component($model->component);
        $classname  = $this->build_class_name('outcomesupport_'.$normalized[0], 'area_info', false);

        if (empty($classname)) {
            $areainfo = new area_info_unknown();
        } else {
            $areainfo = $this->build_generic_instance($classname, '\core_outcome\area\area_info_interface');
        }
        $areainfo->set_area($model);
        if ($cm instanceof cm_info) {
            $areainfo->set_cm($cm);
        }

        return $areainfo;
    }

    /**
     * Build an outcome backup instance.
     *
     * @param string $component A outcome support component name
     * @return mixed
     * @throws coding_exception
     */
    public function build_backup($component) {
        return $this->build_generic_instance(
            $this->build_class_name($component, 'backup')
        );
    }

    /**
     * Build an outcome import instance.
     *
     * @param string $component A outcome import component name
     * @return \core_outcome\import\import_interface
     * @throws coding_exception
     */
    public function build_importer($component) {
        return $this->build_generic_instance(
            $this->build_class_name($component, 'import'),
            '\core_outcome\import\import_interface'
        );
    }

    /**
     * Build an outcome export instance.
     *
     * @param string $component A outcome export component name
     * @return \core_outcome\export\export_interface
     * @throws coding_exception
     */
    public function build_exporter($component) {
        return $this->build_generic_instance(
            $this->build_class_name($component, 'export'),
            '\core_outcome\export\export_interface'
        );
    }

    /**
     * Build an outcome coverage instance.
     *
     * @param $component
     * @return \core_outcome\coverage\coverage_interface
     */
    public function build_coverage($component) {
        return $this->build_generic_instance(
            $this->build_class_name($component, 'coverage'),
            '\core_outcome\coverage\coverage_interface'
        );
    }

    /**
     * Build outcome coverage instances.
     *
     * @return \core_outcome\coverage\coverage_interface[]
     */
    public function build_coverages() {
        $supportcomponents = \core_component::get_plugin_list('outcomesupport');

        $coverages = array();
        foreach ($supportcomponents as $pluginname => $path) {
            try {
                $coverages[] = $this->build_coverage('outcomesupport_'.$pluginname);
            } catch (\Exception $e) {
                // Just ignore this for now.
            }
        }

        return $coverages;
    }
}
