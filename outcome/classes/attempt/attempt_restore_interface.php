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
 * Helps with restoring outcome attempts
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\attempt;

defined('MOODLE_INTERNAL') || die();

/**
 * This interface helps to restore outcome attempt
 * item IDs.  Item IDs generally points to another
 * table and naturally this value changes after
 * a restore.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface attempt_restore_interface {
    /**
     * Attempt to remap the attempt item ID during restore
     *
     * @param \restore_structure_step $step
     * @param string $area
     * @param int $oldid
     * @return bool|int
     */
    public function remap_attempt_itemid(\restore_structure_step $step, $area, $oldid);
}