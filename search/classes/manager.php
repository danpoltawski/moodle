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
 * Search subsystem manager.
 *
 * @package   core_search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_search;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/accesslib.php');

/**
 * Search subsystem manager.
 *
 * @package   core_search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * @var int Text contents.
     */
    const TYPE_TEXT = 1;

    /**
     * @var int User can not access the document.
     */
    const ACCESS_DENIED = 0;

    /**
     * @var int User can access the document.
     */
    const ACCESS_GRANTED = 1;

    /**
     * @var int The document was deleted.
     */
    const ACCESS_DELETED = 2;

    /**
     * @var int Maximum number of results that will be retrieved from the search engine.
     */
    const MAX_RESULTS = 100;

    /**
     * @var int Number of results per page.
     */
    const DISPLAY_RESULTS_PER_PAGE = 10;

    /**
     * @var \core_search\base[] Enabled search components.
     */
    protected static $enabledsearchcomponents = null;

    /**
     * @var \core_search\base[] All system search components.
     */
    protected static $allsearchcomponents = null;

    /**
     * @var \core_search
     */
    protected static $instance = null;

    /**
     * @var \core_search\engine
     */
    protected $engine = null;

    /**
     * Constructor, use \core_search\manager::instance instead to get a class instance.
     *
     * @param \core_search\base The search engine to use
     */
    public function __construct($engine) {
        $this->engine = $engine;
    }

    /**
     * Returns an initialised \core_search instance.
     *
     * It requires global search to be enabled. Use \core_search\manager::is_global_search_enabled
     * to verify it is enabled.
     *
     * @throws \moodle_exception
     * @throws \core_search\engine_exception
     * @return \core_search::manager
     */
    public static function instance() {
        global $CFG;

        // One per request, this should be purged during testing.
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (!self::is_global_search_enabled()) {
            throw new \moodle_exception('globalsearchdisabled', 'search');
        }

        $classname = '\\search_' . $CFG->searchengine . '\\engine';
        if (!class_exists($classname)) {
            throw new \core_search\engine_exception('enginenotfound', 'search', '', $CFG->searchengine);
        }

        $engine = new $classname();

        if (!$engine->is_installed()) {
            throw new \core_search\engine_exception('enginenotinstalled', 'search', '', $CFG->searchengine);
        }
        if (!$engine->is_server_ready()) {
            throw new \core_search\engine_exception('engineserverstatus', 'search');
        }

        self::$instance = new \core_search\manager($engine);
        return self::$instance;
    }

    /**
     * Returns whether global search is enabled or not.
     *
     * @return bool
     */
    public static function is_global_search_enabled() {
        global $CFG;
        return !empty($CFG->enableglobalsearch);
    }

    /**
     * Returns the search engine.
     *
     * @return \core_search\engine
     */
    public function get_engine() {
        return $this->engine;
    }

    /**
     * Returns a component indexer class name.
     *
     * @param string $componentname
     * @return string
     */
    public static function get_component_classname($componentname) {
        return '\\' . $componentname . '\\search\\indexer';
    }

    /**
     * Returns whether the component supports search.
     *
     * @param string $component Frankenstyle component name
     * @return bool
     */
    public static function is_component_supported($component) {
        $classname = self::get_component_classname($component);
        if (class_exists($classname) && method_exists($classname, 'is_supported') && $classname::is_supported()) {
            return true;
        }

        return false;
    }

    /**
     * Returns the an instance of the component search.
     *
     * @param string $componentname Frankenstyle component name
     * @return \core_search\base|bool False if the component does not implement search
     */
    public static function get_search_component($componentname) {

        // Try both caches, as long as the component is supported that is fine.
        if (!empty(self::$allsearchcomponents[$componentname])) {
            return self::$allsearchcomponents[$componentname];
        }
        if (!empty(self::$enabledsearchcomponents[$componentname])) {
            return self::$enabledsearchcomponents[$componentname];
        }

        $classname = self::get_component_classname($componentname);
        if (class_exists($classname) && $classname::is_supported()) {
            return new $classname();
        }

        return false;
    }

    /**
     * Return the list of components featuring global search.
     *
     * @param bool $enabled Return only the enabled ones.
     * @return \core_search\base[]
     */
    public static function get_search_components_list($enabled = false) {

        // Two different arrays, we don't expect these arrays to be big.
        if (!$enabled && self::$allsearchcomponents !== null) {
            return self::$allsearchcomponents;
        } else if ($enabled && self::$enabledsearchcomponents !== null) {
            return self::$enabledsearchcomponents;
        }

        $searchcomponents = array();

        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $plugintype => $unused) {
            $plugins = \core_component::get_plugin_list($plugintype);
            foreach ($plugins as $pluginname => $unused) {

                $plugin = $plugintype . '_' . $pluginname;
                if (self::is_component_supported($plugin)) {
                    $classname = self::get_component_classname($plugin);
                    $searchclass = new $classname();
                    if (!$enabled || ($enabled && $searchclass->is_enabled())) {
                        $searchcomponents[$plugin] = $searchclass;
                    }
                }
            }
        }

        $subsystems = \core_component::get_core_subsystems();
        foreach ($subsystems as $subsystemname => $subsystempath) {
            $componentname = 'core_' . $subsystemname;
            if (self::is_component_supported($componentname)) {
                $classname = self::get_component_classname($componentname);
                $searchclass = new $classname();
                if (!$enabled || ($enabled && $searchclass->is_enabled())) {
                    $searchcomponents[$componentname] = $searchclass;
                }

            }
        }

        // Cache results.
        if ($enabled) {
            self::$enabledsearchcomponents = $searchcomponents;
        } else {
            self::$allsearchcomponents = $searchcomponents;
        }

        return $searchcomponents;
    }

    /**
     * Clears all static caches.
     *
     * @return void
     */
    public static function clear_static() {

        self::$enabledsearchcomponents = null;
        self::$allsearchcomponents = null;
        self::$instance = null;
    }

    /**
     * Returns the contexts the user can access.
     *
     * The returned value is a multidimensional array because some search engines can structure
     * information by components and there will be a performance benefit on passing only some contexts
     * instead of the whole context array set.
     *
     * @return bool|array Indexed by component frankenstyle name. Returns true if the user can see everything.
     */
    protected function get_components_user_accesses() {
        global $CFG, $USER;

        // All results for admins. Eventually we could add a new capability for managers.
        if (is_siteadmin()) {
            return true;
        }

        $componentsbylevel = array();

        // Split components by context level so we only iterate only once through courses and cms.
        $componentslist = self::get_search_components_list(true);
        foreach ($componentslist as $component => $unused) {
            $classname = self::get_component_classname($component);
            $searchcomponent = new $classname();
            foreach ($classname::get_levels() as $level) {
                $componentsbylevel[$level][$component] = $searchcomponent;
            }
        }

        // This will store component - allowed contexts relations.
        $componentscontexts = array();

        if (!empty($componentsbylevel[CONTEXT_SYSTEM])) {
            // We add system context to all components working at this level. Here each component is fully responsible of
            // the access control as we can not automate much, we can not even check guest access as some components might
            // want to allow guests to retrieve data from them.

            $systemcontextid = \context_system::instance()->id;
            foreach ($componentsbylevel[CONTEXT_SYSTEM] as $componentname => $searchclass) {
                $componentscontexts[$componentname][] = $systemcontextid;
            }
        }

        // Get the courses where the current user has access.
        $courses = enrol_get_my_courses(array('id', 'cacherev'));
        if (isloggedin() || isguestuser()) {
            $courses[SITEID] = get_course(SITEID);
        }
        $site = \course_modinfo::instance(SITEID);
        foreach ($courses as $course) {

            // Info about the course modules.
            $modinfo = get_fast_modinfo($course);

            if (!empty($componentsbylevel[CONTEXT_COURSE])) {
                // Add the course contexts the user can view.

                $coursecontext = \context_course::instance($course->id);
                foreach ($componentsbylevel[CONTEXT_COURSE] as $componentname => $searchclass) {
                    if ($course->visible || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                        $componentscontexts[$componentname][$coursecontext->id] = $coursecontext->id;
                    }
                }
            }

            if (!empty($componentsbylevel[CONTEXT_MODULE])) {
                // Add the module contexts the user can view (cm_info->uservisible).

                foreach ($componentsbylevel[CONTEXT_MODULE] as $componentname => $searchclass) {

                    // Removing the plugintype 'mod_' prefix.
                    $modulename = substr($componentname, 4);

                    $modinstances = $modinfo->get_instances_of($modulename);
                    foreach ($modinstances as $modinstance) {
                        if ($modinstance->uservisible) {
                            $componentscontexts[$componentname][$modinstance->context->id] = $modinstance->context->id;
                        }
                    }
                }
            }
        }

        return $componentscontexts;
    }

    /**
     * Returns documents from the engine based on the data provided.
     *
     * It might return the results from the cache instead.
     *
     * @param stdClass $formdata
     * @return \core_search\document[]
     */
    public function search(\stdClass $formdata) {

        $cache = \cache::make('core', 'search_results');

        // Generate a string from all query filters, not including $componentscontext here
        // as being a user cache it is not needed.
        $querykey = $this->generate_query_key($formdata);

        // Look for cached results before executing it.
        if ($results = $cache->get($querykey)) {
            return $results;
        }

        $componentscontexts = $this->get_components_user_accesses();
        if (!$componentscontexts) {
            // User can not access any context.
            $docs = array();
        } else {
            $docs = $this->engine->execute_query($formdata, $componentscontexts);
        }

        // Cache results.
        $cache->set($querykey, $docs);

        return $docs;
    }

    /**
     * We generate the key ourselves so MUC knows that it contains simplekeys.
     *
     * @param stdClass $formdata
     * @return string
     */
    protected function generate_query_key($formdata) {

        // Empty values by default (although queryfield should always have a value).
        $fields = array('queryfield', 'title', 'author', 'component', 'timestart', 'timeend', 'page');

        // Just in this function scope.
        $params = clone $formdata;
        foreach ($fields as $field) {
            if (empty($params->{$field})) {
                $params->{$field} = '';
            }
        }

        // Although it is not likely, we prevent cache hits if available search components change during the session.
        $enabledcomponents = implode('-', array_keys(self::get_search_components_list(true)));

        return md5($params->queryfield . 'title=' . $params->title . 'author=' . $params->author . 'component=' .
            $params->component . 'timestart=' . $params->timestart . 'timeend=' . $params->timeend . 'page=' . $params->page .
            $enabledcomponents);
    }

    /**
     * Merge separate index segments into one.
     */
    public function optimize_index() {
        $this->engine->optimize();
    }

    /**
     * Index all documents.
     *
     * @throws \moodle_exception
     * @return bool Whether there was any updated document or not.
     */
    public function index() {
        global $CFG;

        // Unlimited time.
        \core_php_time_limit::raise();

        $anyupdate = false;

        $searchcomponents = $this->get_search_components_list(true);
        foreach ($searchcomponents as $componentname => $componentsearch) {

            if (CLI_SCRIPT && !PHPUNIT_TEST) {
                mtrace('Processing ' . $componentsearch->get_component_visible_name() . ' component');
            }

            $indexingstart = time();

            // This is used to store this component config.
            list($componentconfigname, $varname) = $componentsearch->get_config_var_name();

            $lastindexrun = get_config($componentconfigname, $varname . '_lastindexrun');
            $numrecords = 0;
            $numdocs = 0;
            $numdocsignored = 0;
            $lastindexeddoc = 0;

            // Iteration delegated to the component.
            $recordset = $componentsearch->get_recordset($lastindexrun);

            // Pass get_document as callback.
            $iterator = new \core\dml\recordset_walk($recordset, array($componentsearch, 'get_document'));
            foreach ($iterator as $document) {

                if (!$document instanceof \core_search\document) {
                    continue;
                }

                $docdata = $document->export_for_engine();
                switch ($docdata['type']) {
                    case self::TYPE_TEXT:
                        $this->engine->add_document($docdata);
                        $numdocs++;
                        break;
                    default:
                        $numdocsignored++;
                        $iterator->close();
                        throw new \moodle_exception('doctypenotsupported', 'search');
                }

                $lastindexeddoc = $document->get('modified');
                $numrecords++;
            }

            if ($numdocs > 0) {
                $anyupdate = true;

                // Commit all remaining documents.
                $this->engine->commit();

                if (CLI_SCRIPT && !PHPUNIT_TEST) {
                    mtrace('Processed ' . $numrecords . ' records containing ' . $numdocs . ' documents for ' . $componentname .
                        ' component. Commits completed.');
                }
            } else if (CLI_SCRIPT && !PHPUNIT_TEST) {
                mtrace('No new documents to index for ' . $componentname . ' component.');
            }

            // Store last index run once documents have been commited to the search engine.
            set_config($varname . '_indexingstart', $indexingstart, $componentconfigname);
            set_config($varname . '_indexingend', time(), $componentconfigname);
            set_config($varname . '_docsignored', $numdocsignored, $componentconfigname);
            set_config($varname . '_docsprocessed', $numdocs, $componentconfigname);
            set_config($varname . '_recordsprocessed', $numrecords, $componentconfigname);
            if ($lastindexeddoc > 0) {
                set_config($varname . '_lastindexrun', $lastindexeddoc, $componentconfigname);
            }
        }

        if ($anyupdate) {
            $event = \core\event\search_indexed::create(
                    array('context' => \context_system::instance()));
            $event->trigger();
        }

        return $anyupdate;
    }

    /**
     * Resets components config tables after index deletion as re-indexing will be done from start.
     *
     * @throws \moodle_exception
     * @param string $componentname Frankenstyle component name.
     * @return void
     */
    public function reset_config($componentname = false) {

        if (!empty($componentname)) {
            $components = array();
            if (!$components[$componentname] = self::get_search_component($componentname)) {
                throw new \moodle_exception('errorcomponentnotavailable', 'search', '', $componentname);
            }
        } else {
            // Only the enabled ones.
            $components = self::get_search_components_list(true);
        }

        foreach ($components as $componentsearch) {

            list($componentname, $varname) = $componentsearch->get_config_var_name();

            set_config($varname . '_indexingstart', 0, $componentname);
            set_config($varname . '_indexingend', 0, $componentname);
            set_config($varname . '_lastindexrun', 0, $componentname);
            set_config($varname . '_docsignored', 0, $componentname);
            set_config($varname . '_docsprocessed', 0, $componentname);
            set_config($varname . '_recordsprocessed', 0, $componentname);
        }
    }

    /**
     * Deletes a component index or all component indexes if no component provided.
     *
     * @param string $componentname The component frankenstyle name or false for all
     * @return void
     */
    public function delete_index($componentname = false) {
        if (!empty($componentname)) {
            $this->engine->delete($componentname);
            $this->reset_config($componentname);
        } else {
            $this->engine->delete();
            $this->reset_config();
        }
        $this->engine->commit();
    }

    /**
     * Deletes index by id.
     *
     * @param int Solr Document string $id
     */
    public function delete_index_by_id($id) {
        $this->engine->delete_by_id($id);
        $this->engine->commit();
    }

    /**
     * Returns search components configuration.
     *
     * @param \core_search\base[] $searchcomponents
     * @return \stdClass[] $configsettings
     */
    public function get_components_config($searchcomponents) {

        $allconfigs = get_config('search');
        $vars = array('indexingstart', 'indexingend', 'lastindexrun', 'docsignored', 'docsprocessed', 'recordsprocessed');

        $configsettings =  array();
        foreach ($searchcomponents as $componentsearch) {

            $componentname = $componentsearch->get_component_name();

            $configsettings[$componentname] = new \stdClass();
            list($componentname, $varname) = $componentsearch->get_config_var_name();

            if (!$componentsearch->is_enabled()) {
                // We delete all indexed data on disable so no info.
                foreach ($vars as $var) {
                    $configsettings[$componentname]->{$var} = 0;
                }
            } else {
                foreach ($vars as $var) {
                    $configsettings[$componentname]->{$var} = get_config($componentname, $varname .'_' . $var);
                }
            }

            // Formatting the time.
            if (!empty($configsettings[$componentname]->lastindexrun)) {
                $configsettings[$componentname]->lastindexrun = userdate($configsettings[$componentname]->lastindexrun);
            } else {
                $configsettings[$componentname]->lastindexrun = get_string('never');
            }
        }
        return $configsettings;
    }
}
