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
 * Search report renderer.
 *
 * @package    report_search
 * @copyright  2015 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for search report.
 *
 * @package    report_search
 * @copyright  2015 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_search_renderer extends plugin_renderer_base {

    /**
     * Renders the global search admin interface.
     *
     * @param \report_search\output\form\admin $form
     * @param \core_search\base[] $searchcomponents
     * @param \stdClass[] $componentsconfig
     * @return string HTML
     */
    public function render_report($form, $searchcomponents, $componentsconfig) {

        $table = new \html_table();
        $table->head = array(get_string('component', 'search'), get_string('newestdocindexed', 'report_search'), get_string('lastrun', 'report_search'));

        foreach ($searchcomponents as $componentname => $componentsearch) {
            $cname = new \html_table_cell($componentsearch->get_component_visible_name());
            $clastrun = new \html_table_cell($componentsconfig[$componentname]->lastindexrun);
            if ($componentsconfig[$componentname]->indexingstart) {
                $ctimetaken = new \html_table_cell($componentsconfig[$componentname]->indexingend - $componentsconfig[$componentname]->indexingstart . ' , ' .
                                                  $componentsconfig[$componentname]->docsprocessed . ' , ' .
                                                  $componentsconfig[$componentname]->recordsprocessed . ' , ' .
                                                  $componentsconfig[$componentname]->docsignored);
            } else {
                $ctimetaken = '';
            }
            $row = new \html_table_row(array($cname, $clastrun, $ctimetaken));
            $table->data[] = $row;
        }

        $content = \html_writer::table($table);
        $content .= $this->output->container_start();
        $content .= $this->output->box_start();

        $content .= $form->render();

        $content .= $this->output->box_end();
        $content .= $this->output->container_end();

        return $content;
    }

}
