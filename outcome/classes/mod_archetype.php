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
 * Get modules by archetype
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome;

defined('MOODLE_INTERNAL') || die();

/**
 * Organize activities by archetype.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_archetype {
    /**
     * @var array
     */
    protected $modarchetypes = array();

    /**
     * @param int $archetype One of MOD_ARCHETYPE_OTHER or MOD_ARCHETYPE_RESOURCE
     * @return array
     */
    public function get_mods_by_archetype($archetype) {
        if (empty($this->modarchetypes)) {
            $this->modarchetypes = $this->mods_by_archetype();
        }
        return $this->modarchetypes[$archetype];
    }

    /**
     * @return array
     */
    protected function mods_by_archetype() {
        global $COURSE;

        $modmetadata     = get_module_metadata($COURSE, get_module_types_names());
        $modsbyarchetype = array(MOD_ARCHETYPE_OTHER => array(), MOD_ARCHETYPE_RESOURCE => array());

        foreach ($modmetadata as $metadata) {
            if (isset($metadata->archetype) and isset($modsbyarchetype[$metadata->archetype])) {
                $modsbyarchetype[$metadata->archetype][] = $metadata->name;
            }
        }
        return $modsbyarchetype;
    }
}