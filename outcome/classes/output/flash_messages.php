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
 * Flash Message Output
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Flash Message Output
 *
 * This class stores messages in the session so
 * they can be displayed on the next page load.
 *
 * Classic use case: do processing, set a message,
 * redirect to a new page and display message.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flash_messages implements \renderable {
    /**
     * Message that is bad :(
     */
    const BAD = 'bad';

    /**
     * Message that is good :)
     */
    const GOOD = 'good';

    /**
     * The default component to use in get_string()
     *
     * @var string
     */
    protected $component;

    /**
     * The session storage key to use
     *
     * @var string
     */
    protected $storagekey;

    /**
     * The session
     *
     * @var object
     */
    protected $session;

    /**
     * @param string $component Default component to use in get_string()
     * @param string $storagekey The session storage key to use
     * @param object $session The session object to use, defaults to global $SESSION
     */
    public function __construct($component = '', $storagekey = '_flashmessages', $session = null) {
        global $SESSION;

        if (is_null($session)) {
            $session = $SESSION;
        }
        $this->set_component($component)->set_storagekey($storagekey)->set_session($session);
    }

    /**
     * Set the default component to use in get_string()
     *
     * @param string $component
     * @return flash_messages
     */
    public function set_component($component) {
        $this->component = $component;
        return $this;
    }

    /**
     * @param string $storagekey The session storage key to use
     * @return flash_messages
     */
    public function set_storagekey($storagekey) {
        $this->storagekey = $storagekey;
        return $this;
    }

    /**
     * @param object $session
     * @return flash_messages
     */
    public function set_session($session) {
        $this->session = $session;
        return $this;
    }

    /**
     * Add a good message :)
     *
     * @param string $identifier The string identifier to use in get_string()
     * @param mixed $a To be passed in the call to get_string()
     * @param string $component The component to use in get_string()
     * @return flash_messages
     */
    public function good($identifier, $a = null, $component = null) {
        return $this->add($identifier, self::GOOD, $a, $component);
    }

    /**
     * Add a bad message :(
     *
     * @param string $identifier The string identifier to use in get_string()
     * @param mixed $a To be passed in the call to get_string()
     * @param string $component The component to use in get_string()
     * @return flash_messages
     */
    public function bad($identifier, $a = null, $component = null) {
        return $this->add($identifier, self::BAD, $a, $component);
    }

    /**
     * Adds a message to be printed.  Messages are printed
     * by calling {@link print()}.
     *
     * @param string $identifier The string identifier to use in get_string()
     * @param string $type The type of message, EG: good or bad
     * @param mixed $a To be passed in the call to get_string()
     * @param string $component The component to use in get_string()
     * @return flash_messages
     */
    public function add($identifier, $type = self::BAD, $a = null, $component = null) {
        if (is_null($component)) {
            $component = $this->component;
        }
        return $this->add_string(get_string($identifier, $component, $a), $type);
    }

    /**
     * Add a string to be printed
     *
     * @param string $string The string to be printed
     * @param string $type The type of message, EG: good or bad
     * @return flash_messages
     */
    public function add_string($string, $type = self::BAD) {
        if (empty($this->session->{$this->storagekey}) or !is_array($this->session->{$this->storagekey})) {
            $this->session->{$this->storagekey} = array();
        }
        $this->session->{$this->storagekey}[$type][] = $string;

        return $this;
    }

    /**
     * Any messages of a given type exist?
     *
     * @param string $type The type of message, EG: good or bad
     * @return bool
     */
    public function has_messages($type) {
        if (!empty($this->session->{$this->storagekey})) {
            return array_key_exists($type, $this->session->{$this->storagekey});
        }
        return false;
    }

    /**
     * Get messages of a particular type
     *
     * Once the messages matching the passed type
     * are returned, they are removed.
     *
     * @param string $type The type of message, EG: good or bad
     * @return array
     */
    public function get_messages($type) {
        $messages = array();
        if (!empty($this->session->{$this->storagekey})) {
            if (array_key_exists($type, $this->session->{$this->storagekey})) {
                $messages = $this->session->{$this->storagekey}[$type];
                unset($this->session->{$this->storagekey}[$type]);

                if (empty($this->session->{$this->storagekey})) {
                    unset($this->session->{$this->storagekey});
                }
            }
        }
        return $messages;
    }

    /**
     * Get all messages, keyed by type
     *
     * Once the messages are returned, they are removed.
     *
     * @return array
     */
    public function get_all_messages() {
        $messages = array();
        if (!empty($this->session->{$this->storagekey})) {
            $messages = $this->session->{$this->storagekey};
            unset($this->session->{$this->storagekey});
        }
        return $messages;
    }

    /**
     * Remove all messages
     */
    public function clear_all_messages() {
        $this->get_all_messages();
    }
}