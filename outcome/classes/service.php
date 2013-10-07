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
 * Outcome Services
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome;

defined('MOODLE_INTERNAL') || die();

/**
 * Outcome Services
 *
 * Use this class to interact with outcomes.  This is
 * the "Public API" for outcomes to the rest of Moodle.
 * This is done so backwards compatibility can be maintained
 * on refactoring if necessary.  It is also done so the
 * underlying code can be refactored more freely without
 * breaking the rest of Moodle.
 *
 * At first glance, some of the methods in this class
 * may look overly simple and redundant, but it was done like this
 * to support services that may require additional arguments
 * or depend on other services.  If this happens, then
 * the overall structure of this class does not need to be
 * modified and it will remain fast.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service {
    /**
     * Instances of services
     *
     * @var array
     */
    protected static $services = array();

    /**
     * Creates a singleton instance of a service
     *
     * @param string $classname The service class to build
     * @return mixed
     */
    public static function build($classname) {
        if (!isset(self::$services[$classname])) {
            self::$services[$classname] = new $classname();
        }
        return self::$services[$classname];
    }

    /**
     * @return \core_outcome\service\mapper_service
     */
    public static function mapper() {
        return self::build('\core_outcome\service\mapper_service');
    }

    /**
     * @return \core_outcome\service\area_service
     */
    public static function area() {
        return self::build('\core_outcome\service\area_service');
    }

    /**
     * @return \core_outcome\service\attempt_service
     */
    public static function attempt() {
        return self::build('\core_outcome\service\attempt_service');
    }

    /**
     * @return \core_outcome\service\mark_service
     */
    public static function mark() {
        return self::build('\core_outcome\service\mark_service');
    }

    /**
     * @return \core_outcome\service\backup_service
     */
    public static function backup() {
        return self::build('\core_outcome\service\backup_service');
    }
}