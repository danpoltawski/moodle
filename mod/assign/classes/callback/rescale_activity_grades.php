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
 * Callbacks for rescale_activity_grades API.
 *
 * @package    mod_assign
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_assign\callback;

use \context_module;
use \user_picture;
use \assign;

defined('MOODLE_INTERNAL') || die;

/**
 * Callbacks for rescale_activity_grades API.
 *
 * @package    mod_assign
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rescale_activity_grades {

    /**
     * Perform manual rescaling of the grades for this activity because the maxgrade has changed.
     *
     * @param \core\callback\rescale_activity_grades $callback
     * @throws coding_exception
     */
    public static function output(\core\callback\rescale_activity_grades $callback) {
        global $DB;

        $oldmin = $callback->get_oldgrademin();
        $oldmax = $callback->get_oldgrademax();
        $newmin = $callback->get_newgrademin();
        $newmax = $callback->get_newgrademax();
        $itemnumber = $callback->get_itemnumber();

        if ($oldmax <= $oldmin) {
            // Grades cannot be scaled.
            return;
        }
        // Sloppy types here is intentional.
        if ($itemnumber != 0) {
            return;
        }
        $scale = ($newmax - $newmin) / ($oldmax - $oldmin);
        if (($newmax - $newmin) <= 1) {
            // We would lose too much precision, lets bail.
            return;
        }

        $params = array(
            'p1' => $oldmin,
            'p2' => $scale,
            'p3' => $newmin,
            'a' => $callback->get_module()->instance
        );

        $sql = 'UPDATE {assign_grades} set grade = (((grade - :p1) * :p2) + :p3) where assignment = :a';
        $dbupdate = $DB->execute($sql, $params);
        if (!$dbupdate) {
            return;
        }

        // Now re-push all grades to the gradebook.
        $dbparams = array('id' => $callback->get_module()->instance);
        $assign = $DB->get_record('assign', $dbparams);
        $assign->cmidnumber = $callback->get_module()->idnumber;

        assign_update_grades($assign);

        $callback->set_gradesscaled(true);
    }
}
