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
 * Global search block.
 *
 * @package    block_globalsearch
 * @copyright  Prateek Sachan {@link http://prateeksachan.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_globalsearch extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_globalsearch');
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content() {
        global $CFG, $OUTPUT;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content =  new stdClass;
        $this->content->footer = '';

        if (\core_search\manager::is_global_search_enabled() === false) {
            $this->content->text = get_string('globalsearchdisabled', 'search');
            return $this->content;
        }

        // We don't want the whole system to stop working because the search engine is not available.
        try {
            $search = \core_search\manager::instance();
        } catch (\core_search\engine_exception $e) {
            // The exception is returning an i18n string so it is fine to show it on screen.
            $this->content->text = $e->getMessage();
            return $this->content;
        }

        // Getting the global search enabled components.
        $components = $search::get_search_components_list(true);

        $url = new moodle_url('/search/index.php');
        $this->content->footer .= html_writer::link($url, get_string('advancedsearch', 'search'));

        $this->content->text  = html_writer::start_tag('div', array('class' => 'searchform'));
        $this->content->text .= html_writer::start_tag('form', array('action' => $url->out()));
        $this->content->text .= html_writer::start_tag('fieldset', array('action' => 'invisiblefieldset'));
        $this->content->text .= html_writer::tag('label', get_string('search', 'search'), array('for' => 'searchform_search', 'class' => 'accesshide'));
        $this->content->text .= html_writer::empty_tag('input', array('id' => 'searchform_search', 'name' => 'queryfield', 'type' => 'text', 'size' => '15'));
        $this->content->text .= $OUTPUT->help_icon('searchinfo', $search->get_engine()->get_plugin_name());
        $this->content->text .= html_writer::tag('label', get_string('searchin', 'block_globalsearch'),
            array('for' => 'id_globalsearch_component'));

        $options = array();
        foreach ($components as $componentname => $componentsearch) {
            $options[$componentname] = $componentsearch->get_component_visible_name();
        }
        $this->content->text .= html_writer::select($options, 'component', '',
            array('' => get_string('allcomponents', 'search')), array('id' => 'id_globalsearch_component'));
        $this->content->text .= html_writer::tag('button', get_string('search', 'search'),
            array('id' => 'searchform_button', 'type' => 'submit', 'title' => 'globalsearch'));
        $this->content->text .= html_writer::end_tag('fieldset');
        $this->content->text .= html_writer::end_tag('form');
        $this->content->text .= html_writer::end_tag('div');

        return $this->content;
    }
}
