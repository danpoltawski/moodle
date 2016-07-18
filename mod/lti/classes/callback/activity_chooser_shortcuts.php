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
 * Callbacks for activity_chooser_shortcuts API.
 *
 * @package    mod_lti
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lti\callback;

use core_component;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for activity_chooser_shortcuts API.
 *
 * @package    mod_lti
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_chooser_shortcuts {

    /**
     * Print the recent activity for this module.
     *
     * @param \core_course\callback\activity_chooser_shortcuts $callback
     * @throws coding_exception
     */
    public static function get_shortcuts(\core_course\callback\activity_chooser_shortcuts $callback) {
        global $CFG, $COURSE;
        require_once($CFG->dirroot.'/mod/lti/locallib.php');

        $callback->add_default_shortcut();

        $types = lti_get_configured_types($COURSE->id, $callback->get_link()->param('sr'));
        foreach ($types as $type) {
            $archetype = $callback->get_archetype();
            if (!empty($type->archetype)) {
                $archetype = $type->archetype;
            }
            $callback->add_shortcut($archetype, $type->name, $type->title, $type->help, $type->icon, $type->link, $type->helplink);
        }

        // Add items defined in ltisource plugins.
        foreach (core_component::get_plugin_list('ltisource') as $pluginname => $dir) {
            if ($moretypes = component_callback("ltisource_$pluginname", 'get_types')) {
                // Callback 'get_types()' in 'ltisource' plugins is deprecated in 3.1 and will be removed in 3.5, TODO MDL-53697.
                debugging('Deprecated callback get_types() is found in ltisource_' . $pluginname .
                    ', use get_shortcuts() instead', DEBUG_DEVELOPER);
                $grouptitle = get_string('modulenameplural', 'mod_lti');
                foreach ($moretypes as $type) {
                    // Instead of adding subitems combine the name of the group with the name of the subtype.
                    $type->title = get_string('activitytypetitle', '',
                        (object)['activity' => $grouptitle, 'type' => $type->typestr]);
                    // Re-implement the logic of get_module_metadata() in Moodle 3.0 and below for converting
                    // subtypes into items in activity chooser.
                    $type->type = str_replace('&amp;', '&', $type->type);
                    $type->name = preg_replace('/.*type=/', '', $type->type);
                    $type->link = new moodle_url($defaultitem->link, array('type' => $type->name));
                    if (empty($type->help) && !empty($type->name) &&
                            get_string_manager()->string_exists('help' . $type->name, $pluginname)) {
                        $type->help = get_string('help' . $type->name, $pluginname);
                    }
                    $callback->add_shortcut($type->archetype, $type->name, $type->title, $type->help, $type->icon, $type->link);
                }
            }
            // LTISOURCE plugins can also implement callback \core_course\callback\activity_chooser_shortcuts to add items to
            // the activity chooser.
            // The return values are the same as of the 'mod' callbacks except that $defaultitem is only passed for reference and
            // should not be added to the return value.
            $params = (array) $callback->get_default_shortcut();
            $callback = \core_course\callback\activity_chooser_shortcuts::create($params);
            $moretypes = $callback->dispatch("ltisource_$pluginname")->get_shortcuts();

            foreach ($moretypes as $type) {
                $callback->add_shortcut($type->archetype, $type->name, $type->title,
                                        $type->help, $type->icon, $type->link, $type->helplink);
            }
        }
    }
}
