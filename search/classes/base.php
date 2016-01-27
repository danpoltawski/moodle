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
 * Search base class to be extended by components implementing search.
 *
 * @package    core_search
 * @copyright  2015 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_search;

defined('MOODLE_INTERNAL') || die();

/**
 * Base search implementation.
 *
 * Moodle components and plugins interested in filling the search engine
 * with data should extend this class (or any extension of this class)
 *
 * @package    core_search
 * @copyright  2015 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {

    /**
     * The context levels the search implementation is working on.
     *
     * @var array
     */
    protected static $levels = [CONTEXT_SYSTEM];

    /**
     * User data required to show their fullnames. Indexed by userid.
     *
     * Static as we want it shared accross search components.
     *
     * @var stdClass[]
     */
    protected static $usersdata = array();

    /**
     * The item files filearea name.
     *
     * Search components which contents can include files should
     * overwrite this attribute to set the filearea name.
     *
     * @var string
     */
    protected static $filearea = null;

    /**
     * Constructor.
     *
     * @throws \coding_exception
     * @return void
     */
    public final function __construct() {

        $classname = get_class($this);

        // Detect possible issues when defining the class.
        if (strpos($classname, '\search') === false) {
            throw new \coding_exception($classname . ' class should specify its component namespace and it should be named search.');
        } else if (strpos($classname, '_') === false) {
            throw new \coding_exception($classname . ' class namespace should be its component frankenstyle name');
        }

        $this->componentname = substr($classname, 0, strpos($classname, '\\'));
        $this->componenttype = substr($this->componentname, 0, strpos($this->componentname, '_'));
    }

    /**
     * Whether the component supports global search or not.
     *
     * Initially returning true as there is no point on implementing a class
     * if it is not supported. Components might override it if they have to
     * deal with extra requirements to support search.
     *
     * @return bool
     */
    public static function is_supported() {
        return true;
    }

    /**
     * Returns context levels property.
     *
     * @return int
     */
    public static function get_levels() {
        return static::$levels;
    }

    /**
     * Returns the item filearea.
     *
     * @return string
     */
    public static function get_filearea() {
        return static::$filearea;
    }

    /**
     * Returns the search component name.
     *
     * It might be the plugin name (whole frankenstyle name) or the core subsystem name.
     *
     * @return string
     */
    public function get_component_name() {
        return $this->componentname;
    }

    /**
     * Returns the component type.
     *
     * It might be a plugintype or 'core' for core subsystems.
     *
     * @return string
     */
    public function get_component_type() {
        return $this->componenttype;
    }

    /**
     * Returns the component visible name.
     *
     * @param bool $lazyload Usually false, unless when in admin settings.
     * @return string
     */
    public function get_component_visible_name($lazyload = false) {
        if ($this->componenttype === 'core') {
            // Stripping the component type. Would be better to have a proper name for each
            // moodle subsystem, but we can defer this when implementing subsystems search.
           return get_string('subsystemname', 'search', substr($this->componentname, 5));
        } else {
            return get_string('pluginname', $this->componentname, null, $lazyload);
        }
    }

    /**
     * Returns the component type visible name
     *
     * Just core if it is a core subsystem or the plugin type name.
     *
     * @param bool $lazyload Usually false, unless when in admin settings.
     * @return string
     */
    public function get_component_type_visible_name($lazyload = false) {
        if ($this->componenttype === 'core') {
            return get_string('core');
        } else {
            return get_string('type_' . $this->componenttype, 'core_plugin', $lazyload);
        }
    }

    /**
     * Returns the config var name.
     *
     * It depends on whether it is a moodle subsystem or a plugin as plugin-related config should remain in their own scope.
     *
     * @return string Config var path including the plugin (or component) and the varname where 
     */
    public function get_config_var_name() {

        if ($this->componenttype === 'core') {
            // Core subsystems config in search.
            return array('search', $this->componentname);
        }

        // Plugins config in the plugin scope.
        return array($this->componentname, 'search');
    }

    /**
     * Is the search component enabled by the system administrator?
     *
     * @return bool
     */
    public function is_enabled() {
        list($componentname, $varname) = $this->get_config_var_name();
        return (bool)get_config($componentname, 'enable' . $varname);
    }

    /**
     * Returns user data checking the internal static cache.
     *
     * Including here the minimum required user information as this may grow big.
     *
     * @param int $userid
     * @return stdClass
     */
    public static function get_user_names_data($userid) {
        global $DB;

        if (empty(self::$usersdata[$userid])) {
            $fields = get_all_user_name_fields(true);
            self::$usersdata[$userid] = $DB->get_record('user', array('id' => $userid), 'id, ' . $fields);
        }
        return self::$usersdata[$userid];
    }

    /**
     * Returns a recordset ordered by modification date ASC.
     *
     * Each record can include any data self::get_document might need but it must:
     * - Include an 'id' field: Unique identifier (in this component scope) of a document to index in the search engine
     *   If the indexed data is a files filearea, the 'id' value should match the filearea itemid.
     * - Only return data modified since $modifiedfrom, including $modifiedform to prevent
     *   some records from not being indexed (e.g. your-timemodified-fieldname >= $modifiedfrom)
     * - Order the returned data by time modified in ascending order, as \core_search::manager will need to store the modified time
     *   of the last indexed document.
     *
     * @param int $modifiedfrom
     * @return moodle_recordset
     */
    abstract public function get_recordset($modifiedfrom = 0);

    /**
     * Returns the document related with the provided record.
     *
     * This method receives a record with the document id and other info returned by get_recordset
     * that might be useful here. The idea is to restrict database queries to minimum as this
     * function will be called for each document to index. As an alternative, use cached data.
     *
     * Internally it should use \core_search\document to standarize the documents before
     * sending them to the search engine.
     *
     * @param stdClass $record A record containing the indexed document id and a modified timestamp
     * @return \core_search\document
     */
    abstract public function get_document($record);

    /**
     * Can the current user see the document.
     *
     * @param int $id The internal search component entity id.
     * @return bool
     */
    abstract public function check_access($id);

    /**
     * Returns a url to the document, it might match self::get_context_url().
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    abstract public function get_doc_url(\core_search\document $doc);

    /**
     * Returns a url to the document context.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    abstract public function get_context_url(\core_search\document $doc);
}
