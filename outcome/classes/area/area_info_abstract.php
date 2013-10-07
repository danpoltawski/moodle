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
 * Area information abstract
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\area;

use core_outcome\model\area_model;

defined('MOODLE_INTERNAL') || die();

/**
 * The abstract just implements a few getters and
 * setters from the interface.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class area_info_abstract implements area_info_interface {
    /**
     * @var area_model
     */
    protected $area;

    /**
     * @var \cm_info
     */
    protected $cm;

    public function set_area(area_model $model) {
        $this->area = $model;
    }

    public function get_area() {
        return $this->area;
    }

    public function set_cm(\cm_info $cm) {
        $this->cm = $cm;
    }

    public function get_cm() {
        return $this->cm;
    }
}
