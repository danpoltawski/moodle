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
 * Definition of all callbacks and receivers defined in mod_assign
 *
 * @package   mod_assign
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$receivers = [
    [
        'name' => '\\core\\callback\\print_recent_activity',
        'callback' => '\\mod_assign\\callback\\print_recent_activity::output'
    ],
    [
        'name' => '\\core\\callback\\rescale_activity_grades',
        'callback' => '\\mod_assign\\callback\\rescale_activity_grades::rescale'
    ],
    [
        'name' => '\\core\\callback\\output_fragment',
        'callback' => '\\mod_assign\\callback\\output_fragment::get_html'
    ]
];
