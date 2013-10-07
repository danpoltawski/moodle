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
 * Map outcomes to an area
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once('HTML/QuickForm/element.php');
require_once($CFG->libdir.'/formslib.php');

/**
 * Element to map outcomes to a piece of content
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_map_outcome extends HTML_QuickForm_element {
    /**
     * HTML for help button, if empty then no help will icon will be displayed.
     *
     * @var string
     */
    public $_helpbutton = '';

    /**
     * @param null|string $elementName
     * @param string $value
     * @param null|string|array $attributes
     */
    function MoodleQuickForm_map_outcome($elementName = null, $value = '', $attributes = null) {
        $this->HTML_QuickForm_element($elementName, $value, $attributes);
        $this->_type = 'mapoutcome';
        $this->updateAttributes(array('type' => 'hidden'));
    }

    /**
     * @param string $name The name to set
     */
    function setName($name) {
        $this->updateAttributes(array('name' => $name));
    }

    /**
     * @return string
     */
    function getName() {
        return $this->getAttribute('name');
    }

    /**
     * @param string $value The value to set
     */
    function setValue($value) {
        $this->updateAttributes(array('value' => $value));
    }

    /**
     * @return string
     */
    function getValue() {
        return $this->getAttribute('value');
    }

    /**
     * Returns html for help button.
     *
     * @return string
     */
    function getHelpButton() {
        return $this->_helpbutton;
    }

    /**
     * Returns HTML for this element.
     *
     * @return string
     */
    function toHtml() {
        global $PAGE;

        $randomid = html_writer::random_id('outcome');

        $PAGE->requires->yui_module(
            'moodle-core_outcome-mapoutcome',
            'M.core_outcome.init_mapoutcome',
            array(array(
                'srcNode'   => '#'.$randomid,
                'isFrozen'  => $this->_flagFrozen,
                'contextId' => $PAGE->context->id,
            ))
        );
        $PAGE->requires->strings_for_js(array(
            'selectoutcomes',
            'noselectedoutcomes',
            'openx',
            'close',
            'closex',
            'outcomesforx',
            'deletex',
            'ok',
            'nooutcomesfound',
        ), 'outcome');
        $PAGE->requires->strings_for_js(array(
            'cancel',
            'error'
        ), 'moodle');

        return html_writer::tag('div', $this->_getTabs().'<input'.$this->_getAttrString($this->_attributes).' />', array('id' => $randomid));
    }

    function exportValue(&$submitValues, $assoc = false) {
        // The value is stored as JSON.
        // Decode and return array of selected outcome IDs.
        $value = $this->_findValue($submitValues);
        if (null === $value) {
            $value = $this->getValue();
        }
        if (!empty($value)) {
            $outcomeids = array();
            $rawvalues  = json_decode($value);
            if (!empty($rawvalues->outcomes)) {
                $outcomeids = array();
                foreach ($rawvalues->outcomes as $outcome) {
                    $outcomeids[] = $outcome->id;
                }
            }
            $value = $outcomeids;
        } else {
            $value = array();
        }
        return $this->_prepareValue($value, $assoc);
    }
}
