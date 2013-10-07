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

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * An event for when a question has been updated.
 *
 * @package    core
 * @copyright  Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_updated extends base {
    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['objecttable'] = 'question';
        $this->data['crud']        = 'u';
        $this->data['level']       = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventquestionupdated', 'question');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "Question $this->objectid updated by user $this->userid";
    }

    /**
     * Returns relevant URL, override in subclasses.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/question/edit.php', array('courseid' => $this->courseid));
    }
}
