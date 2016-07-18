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
 * Definition of all callbacks and receivers defined in atto_accessibilitychecker.
 *
 * For more information, take a look to the documentation available:
 *     - Callbacks API: {@link http://docs.moodle.org/dev/Callbacks_API}
 *
 * @package   atto_accessibilitychecker
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$receivers = [
    [
        'name' => '\\editor_atto\\callback\\strings_for_js',
        'callback' => '\\atto_accessibilitychecker\\callback\\strings_for_js::get_strings'
    ],
];
