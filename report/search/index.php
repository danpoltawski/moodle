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
 * Global search report
 *
 * @package   report_search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('reportsearch');

$pagetitle = get_string('pluginname', 'report_search');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

if (\core_search\manager::is_global_search_enabled() === false) {
    $renderer = $PAGE->get_renderer('core_search');
    echo $renderer->render_search_disabled();
    echo $OUTPUT->footer();
    exit;
}

$renderer = $PAGE->get_renderer('report_search');
$search = \core_search\manager::instance();

// All enabled components.
$searchcomponents = $search->get_search_components_list(true);

$mform = new \report_search\form(null, array('searchcomponents' => $searchcomponents));
if ($data = $mform->get_data()) {

    if (!empty($data->delete)) {
        if (!empty($data->all)) {
            $search->delete_index();
        } else {
            $anydelete = false;
            // We check that the component exist and is enabled.
            foreach ($searchcomponents as $componentname => $searchcomponent) {
                if (!empty($data->{$componentname})) {
                    $anydelete = true;
                    $search->delete_index($componentname);
                }
            }
        }

        if (!empty($data->all) || $anydelete) {
            echo $OUTPUT->notification(get_string('deleted', 'report_search'), 'notifysuccess');
        }
    }

    if (!empty($data->reindex)) {
        $search->index();
        $search->optimize_index();
        echo $OUTPUT->notification(get_string('reindexed', 'report_search'), 'notifysuccess');
    }
}

// After processing the form as config might change depending on the action.
$componentsconfig = $search->get_components_config($searchcomponents);

echo $renderer->render_report($mform, $searchcomponents, $componentsconfig);
echo $OUTPUT->footer();
