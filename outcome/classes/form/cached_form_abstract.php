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
 * Abstract Form Cache
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_outcome\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/formslib.php');

/**
 * Abstract Form Cache
 *
 * This form will automatically cache its data so its
 * values will persist across requests.
 *
 * Usage:
 *      // Setup, order matters...
 *      $mform = new cached_form_abstract();
 *      $mform->set_data(...);  // Optional, only use to override data
 *      // Then...
 *      $mform->handle_submit(...); // Will persist submitted form data then redirect
 *      // OR...
 *      $mform->cache_data(...);    // Will persist the passed data
 *      // Later, get data
 *      $mform->get_cached_value(...); // Get a single value
 *      $mform->get_cached_data();     // Get all values
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class cached_form_abstract extends \moodleform {
    /**
     * @var \cache_store
     */
    protected $_cache;

    /**
     * Override so we can get form default values
     * before it gets mucked up with potentially
     * invalid submit data.
     *
     * In addition, set the form data to our cached data.
     *
     * @param string $method
     */
    function _process_submission($method) {
        $this->init_cache();
        $this->set_data(array_merge($this->_form->_defaultValues, $this->_form->exportValues(), $this->get_cached_data()));

        parent::_process_submission($method);
    }

    /**
     * Override so we can keep our copy of default values
     * up-to-date.
     *
     * @param array|\stdClass $default_values
     */
    function set_data($default_values) {
        if (is_object($default_values)) {
            $default_values = (array) $default_values;
        }
        $this->cache_data(array_merge($this->get_cached_data(), $default_values));
        parent::set_data($default_values);
    }

    /**
     * The cache key to use for form data
     *
     * @return string
     */
    public function get_cache_key() {
        global $PAGE;
        return get_class($this).'::'.$PAGE->context->id;
    }

    /**
     * Initialize the cache
     */
    public function init_cache() {
        $this->_cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'outcome', 'cachedform',
            array(), array('persistent' => true));
    }

    /**
     * Default handling of form submit
     *
     * @param null|string|\moodle_url $redirecturl
     */
    public function handle_submit($redirecturl = null) {
        global $PAGE;

        if ($data = $this->get_data()) {
            $this->cache_data((array) $data);
            if (is_null($redirecturl)) {
                $redirecturl = $PAGE->url;
            }
            redirect($redirecturl);
        }
    }

    /**
     * Never save these values to the cache
     *
     * @return array
     */
    public function cache_blacklist() {
        return array('sesskey', '_qf__'.$this->_formname);
    }

    /**
     * Set the cached data
     *
     * @param array $data
     */
    public function cache_data($data) {
        // Attempt to remove unwanted data.
        foreach ($this->cache_blacklist() as $blacklisted) {
            unset($data[$blacklisted]);
        }
        $this->_cache->set($this->get_cache_key(), $data);
    }

    /**
     * Get the cached value for an element
     *
     * @param string $name Element name
     * @throws \coding_exception
     * @return mixed
     */
    public function get_cached_value($name) {
        $data = $this->get_cached_data();
        if ($data === false or !array_key_exists($name, $data)) {
            throw new \coding_exception("Element with name = '$name' does not have a value in cache");
        }
        return $data[$name];
    }

    /**
     * Get all of the cached data
     *
     * @return array
     */
    public function get_cached_data() {
        $data = $this->_cache->get($this->get_cache_key());
        if ($data !== false) {
            return $data;
        }
        return array();
    }
}