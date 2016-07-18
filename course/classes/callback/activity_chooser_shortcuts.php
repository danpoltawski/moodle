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
 * Callback to supply a list of aliases for this module in the activity chooser.
 *
 * @package    core_course
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_course\callback;

use \core\callback\callback_with_legacy_support;
use \stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * Callback to supply a list of aliases for this module in the activity chooser.
 *
 * @package    core_course
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_chooser_shortcuts extends callback_with_legacy_support {

    /** @var string $archetype */
    private $archetype;
    /** @var string $name */
    private $name;
    /** @var string $title */
    private $title;
    /** @var string $help */
    private $help;
    /** @var string $icon */
    private $icon;
    /** @var moodle_url $link */
    private $link;
    /** @var array $shortcuts */
    private $shortcuts;

    /**
     * Constructor - take parameters from a named array of arguments.
     *
     * Components implementing this callback can call add_shortcuts one or more times to append to the list of shortcuts for
     * this activity.
     *
     * @param array $params - List of arguments including archetype, name, title, help, icon, link
     */
    private function __construct($params = []) {
        $this->archetype = $params['archetype'];
        $this->name = $params['name'];
        $this->title = $params['title'];
        $this->help = $params['help'];
        $this->icon = $params['icon'];
        $this->link = $params['link'];
        $this->shortcuts = null;
    }

    /**
     * Public factory method. This is just because chaining on "new" seems ugly.
     *
     * @param array $params - Named array of arguments including defaultmodule
     *                        The default module is a stdClass containing fields for:
     *                        archetype, name, title, help, icon, link
     * @return activity_chooser_shortcuts
     */
    public static function create($params = []) {
        return new static($params);
    }

    /**
     * Map the fields in this class to the an array holding an stdClass with the initial defaults.
     * @return mixed $args
     */
    public function get_default_shortcut() {
        $default = new stdClass();
        $default->archetype = $this->archetype;
        $default->name = $this->name;
        $default->title = $this->title;
        $default->help = $this->help;
        $default->icon = $this->icon;
        $default->link = $this->link;

        // The arguments are expected in a numerically indexed array.
        return $default;
    }

    /**
     * Map the fields in this class to the format expected for backwards compatibility with component_callback.
     * @return mixed $args
     */
    public function get_legacy_arguments() {
        return [ $this->get_default_shortcut() ];
    }

    /**
     * This is the backwards compatible component_callback
     * @return string $functionname
     */
    public function get_legacy_function() {
        return 'get_shortcuts';
    }

    /**
     * Map the legacy result to the shortcuts field.
     * @return mixed $result
     */
    public function get_legacy_result() {
        return $this->shortcuts;
    }

    /**
     * Map the legacy result to the shortcuts field.
     * @param mixed $shortcuts
     */
    public function set_legacy_result($shortcuts) {
        $this->shortcuts = $shortcuts;
    }

    /**
     * Get the archetype
     * @return string
     */
    public function get_archetype() {
        return $this->archetype;
    }

    /**
     * Get the name
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get the title
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Get the icon
     * @return string
     */
    public function get_icon() {
        return $this->icon;
    }

    /**
     * Get the help
     * @return string
     */
    public function get_help() {
        return $this->help;
    }

    /**
     * Get the link
     * @return moodle_url
     */
    public function get_link() {
        return $this->link;
    }

    /**
     * Get the shortcuts
     * @return array
     */
    public function get_shortcuts() {
        return $this->shortcuts;
    }

    /**
     * Add the default shortcut to the list.
     */
    public function add_default_shortcut() {
        if (empty($this->shortcuts)) {
            $this->shortcuts = [];
        }
        $this->shortcuts = array_merge($this->shortcuts, [ $this->get_default_shortcut() ]);
    }

    /**
     * Add shortcut to the list.
     *
     * @param string $archetype
     * @param string $name
     * @param string $title
     * @param string $help
     * @param string $icon
     * @param moodle_url $link
     * @param moodle_url $helplink
     */
    public function add_shortcut($archetype, $name, $title, $help, $icon, $link, $helplink = null) {
        if (empty($this->shortcuts)) {
            $this->shortcuts = [];
        }
        $shortcut = new stdClass();
        $shortcut->archetype = $archetype;
        $shortcut->name = $name;
        $shortcut->title = $title;
        $shortcut->help = $help;
        $shortcut->icon = $icon;
        $shortcut->link = $link;
        $shortcut->helplink = $helplink;
        $this->shortcuts[] = $shortcut;
    }
}
