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
 * Base class for search engines.
 *
 * All search engines must extend this class.
 *
 * @package   core_search
 * @copyright 2015 Daniel Neis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_search;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for search engines.
 *
 * All search engines must extend this class.
 *
 * @package   core_search
 * @copyright 2015 Daniel Neis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class engine {

    /**
     * The search engine configuration.
     *
     * @var stdClass
     */
    protected $config = null;

    /**
     * @var array Internal cache.
     */
    protected $cachedcomponents = array();

    /**
     * @var array Internal cache.
     */
    protected $cachedcourses = array();

    /**
     * @var string Frankenstyle plugin name.
     */
    protected $pluginname = null;

    /**
     * Initialises the search engine configuration.
     *
     * Search engine availability should be checked separately.
     *
     * @see self::is_installed
     * @see self::is_server_ready
     * @return void
     */
    public function __construct() {

        $classname = get_class($this);
        if (strpos($classname, '\\') === false) {
            throw new \coding_exception('"' . $classname . '" class should specify its component namespace and it should be named engine.');
        } else if (strpos($classname, '_') === false) {
            throw new \coding_exception('"' . $classname . '" class namespace should be its frankenstyle name');
        }

        // This is search_xxxx config.
        $this->pluginname = substr($classname, 0, strpos($classname, '\\'));
        if ($config = get_config($this->pluginname)) {
            $this->config = $config;
        } else {
            $this->config = new stdClass();
        }
    }

    /**
     * Returns a course instance checking internal caching.
     *
     * @param int $courseid
     * @return stdClass
     */
    protected function get_course($courseid) {
        if (!empty($this->cachedcourses[$courseid])) {
            return $this->cachedcourses[$courseid];
        }

        // No need to clone, only read.
        $this->cachedcourses[$courseid] = get_course($courseid, false);

        return $this->cachedcourses[$courseid];
    }

    /**
     * Returns a search instance of the specified component checking internal caching.
     *
     * @param string $componentname Frankenstyle name
     * @return \core_search\base
     */
    protected function get_search_component($componentname) {

        if (isset($this->cachedcomponents[$componentname]) && $this->cachedcomponents[$componentname] === false) {
            // We already checked that component and it is not available.
            return false;
        }

        if (!isset($this->cachedcomponents[$componentname])) {
            // First result that matches this component.

            $this->cachedcomponents[$componentname] = \core_search\manager::get_search_component($componentname);
            if ($this->cachedcomponents[$componentname] === false) {
                // The component does not support search or it is not available any more.
                $this->cachedcomponents[$componentname] = false;

                return false;
            }

            if (!$this->cachedcomponents[$componentname]->is_enabled()) {
                // We skip the component if it is not enabled.

                // Marking it as false so next time we don' need to check it again.
                $this->cachedcomponents[$componentname] = false;

                return false;
            }
        }

        return $this->cachedcomponents[$componentname];
    }

    /**
     * Returns a document instance prepared to be rendered.
     *
     * @param \core_search\base $componentsearch
     * @param array $docdata
     * @return \core_search\document
     */
    protected function to_document(\core_search\base $componentsearch, $docdata) {

        $doc = \core_search\document_factory::instance($docdata['itemid'], $docdata['component'], $this);
        $doc->set_data_from_engine($docdata);
        $doc->set_doc_url($componentsearch->get_doc_url($doc));
        $doc->set_context_url($componentsearch->get_context_url($doc));
        $doc->set_filearea($componentsearch::get_filearea());

        // Uses the internal caches to get required data needed to render the document later.
        $course = $this->get_course($doc->get('courseid'));
        $doc->set_extra('coursefullname', $course->fullname);
        $doc->set_extra('componentvisiblename', $componentsearch->get_component_visible_name());

        return $doc;
    }

    /**
     * Returns the plugin name.
     *
     * @return string Frankenstyle plugin name.
     */
    public function get_plugin_name() {
        return $this->pluginname;
    }

    /**
     * Gets the document class used by this search engine.
     *
     * Default implementation that looks for a document class in the current namespace
     * falling back to \core_search\document.
     *
     * Note that, if you are extending a search engine which extends \core_search\document you
     * must overwrite this class specifying which document class should be used.
     *
     * Publicly available because search components do not have access to the engine details,
     * \core_search\document_factory accesses this function.
     *
     * @return string
     */
    public function get_document_classname() {
        $classname = $this->pluginname . '\\document';
        if (!class_exists($classname)) {
            $classname = '\\core_search\\document';
        }
        return $classname;
    }

    /**
     * Optimizes the search engine.
     *
     * Should be overwritten if the search engine can optimize its contents.
     *
     * @return void
     */
    public function optimize() {
        // Nothing by default.
    }

    /**
     * Does the system satisfy all the requirements.
     *
     * Should be overwritten if the search engine has any system dependencies
     * that needs to be checked.
     *
     * @return bool
     */
    public function is_installed() {
        return true;
    }

    /**
     * Is the server ready to use.
     *
     * This should also check that the search engine configuration is ok.
     *
     * @return bool
     */
    abstract function is_server_ready();

    /**
     * Adds a document to the search engine.
     *
     * @param array $doc
     * @return void
     */
    abstract function add_document($doc);

    /**
     * Commits changes to the server.
     *
     * @return void
     */
    abstract function commit();

    /**
     * Executes the query on the engine.
     *
     * @param  stdClass $filters Query and filters to apply.
     * @param  array    $usercontexts Contexts where the user has access. True if the user can access all contexts.
     * @return \core_search\document[] Results or false if no results
     */
    abstract function execute_query($filters, $usercontexts);

    /**
     * Delete all component documents.
     *
     * @param string $componentname Frankenstyle name
     * @return void
     */
    abstract function delete($componentname = null);
}
