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
 * Document representation.
 *
 * @package    core_search
 * @copyright  2015 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_search;

defined('MOODLE_INTERNAL') || die();

/**
 * Respresents a document to index.
 *
 * @package    core_search
 * @copyright  2015 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document implements \renderable, \templatable {

    /**
     * @var array $data The document data.
     */
    protected $data = array();

    /**
     * @var array Extra data needed to render the document.
     */
    protected $extradata = array();

    /**
     * @var \moodle_url Link to the document.
     */
    protected $docurl = null;

    /**
     * @var \moodle_url Link to the document context.
     */
    protected $contexturl = null;

    /**
     * @var string The document filearea inside the component.
     */
    protected $filearea = null;

    /**
     * All required fields any doc should contain.
     *
     * We have to choose a format to specify field types, using solr format as we have to choose one and solr is the
     * default search engine.
     *
     * Search engine plugins are responsible of setting their appropriate field types and map these naming to whatever format
     * they need.
     *
     * @var array
     */
    protected static $requiredfields = array(
        'id' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => false
        ),
        'itemid' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => true
        ),
        'title' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'content' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'contentformat' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => false
        ),
        'contextid' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => true
        ),
        'component' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'type' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => true
        ),
        'courseid' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => false
        ),
        'modified' => array(
            'type' => 'tdate',
            'stored' => true,
            'indexed' => true
        ),
    );

    /**
     * All optional fields docs can contain.
     *
     * Although it matches solr fields format, this is just to define the field types. Search
     * engine plugins are responsible of setting their appropriate field types and map these
     * naming to whatever format they need.
     *
     * @var array
     */
    protected static $optionalfields = array(
        'userid' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => false
        ),
        'userfullname' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'name' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'intro' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'introformat' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => false
        ),
        'created' => array(
            'type' => 'tdate',
            'stored' => true,
            'indexed' => true
        ),
    );

    /**
     * We ensure that the document has a unique id accross search components.
     *
     * @param int $itemid An id unique to the search component
     * @param string $component
     * @return void
     */
    public function __construct($itemid, $component) {

        if (!is_numeric($itemid)) {
            throw new \coding_exception('The itemid should be an integer');
        }

        $this->data['id'] = $component . '-' . $itemid;
        $this->data['component'] = $component;
        $this->data['itemid'] = intval($itemid);
    }

    /**
     * Setter.
     *
     * Basic checkings to prevent common issues.
     *
     * If the field is a string tags will be stripped, if it is an integer or a date it
     * will be casted to a PHP integer. tdate fields values are expected to be timestamps.
     *
     * @throws \coding_exception
     * @param string $fieldname The field name
     * @param string|int $value The value to store
     * @return string|int The stored value
     */
    public function set($fieldname, $value) {

        if (!empty(static::$requiredfields[$fieldname])) {
            $fielddata = static::$requiredfields[$fieldname];
        } else if (!empty(static::$optionalfields[$fieldname])) {
            $fielddata = static::$optionalfields[$fieldname];
        }

        if (empty($fielddata)) {
            throw new \coding_exception('"' . $fieldname . '" field does not exist.');
        }

        // tdate fields should be set as timestamps, later they might be converted to
        // a date format, it depends on the search engine.
        if (($fielddata['type'] === 'int' || $fielddata['type'] === 'tdate') && !is_numeric($value)) {
            throw new \coding_exception('"' . $fieldname . '" value should be an integer and its value is "' . $value . '"');
        }

        // We want to be strict here, there might be engines that expect us to
        // provide them data with the proper type already set.
        if ($fielddata['type'] === 'int' || $fielddata['type'] === 'tdate') {
            $this->data[$fieldname] = intval($value);
        } else {
            $this->data[$fieldname] = trim($value, "\r\n");
        }

        return $this->data[$fieldname];
    }

    /**
     * Sets data to this->extradata
     *
     * This data can be retrieved using \core_search\document->get($fieldname).
     *
     * @param string $fieldname
     * @param string $value
     * @return void
     */
    public function set_extra($fieldname, $value) {
        $this->extradata[$fieldname] = $value;
    }

    /**
     * Getter.
     *
     *
     * @throws \coding_exception
     * @param string $field
     * @return string|null if not set
     */
    public function get($field) {

        if (isset($this->data[$field])) {
            return $this->data[$field];
        }

        // Fallback to extra data.
        if (isset($this->extradata[$field])) {
            return $this->extradata[$field];
        }

        return null;
    }

    /**
     * Checks if a field is set.
     *
     * @param string $field
     * @return bool
     */
    public function is_set($field) {
        return (isset($this->data[$field]) || isset($this->extradata[$field]));
    }

    /**
     * Returns all default fields definitions.
     *
     * @return array
     */
    public static function get_default_fields_definition() {
        return static::$requiredfields + static::$optionalfields;
    }

    /**
     * Formats the timestamp preparing the time fields to be inserted into the search engine.
     *
     * By default it just returns a timestamp so any search engine could just store integers
     * and use integers comparison to get documents between x and y timestamps, but search
     * engines might be interested in using their own field formats. They can do it extending
     * this class in \search_xxx\document.
     *
     * @param int $timestamp
     * @return string
     */
    public static function format_time_for_engine($timestamp) {
        return $timestamp;
    }

    /**
     * Formats a string value for the search engine.
     *
     * Search engines may overwrite this method to apply restrictions, like limiting the size.
     * The default behaviour is just returning the string.
     *
     * @param string $string
     * @return string
     */
    public static function format_string_for_engine($string) {
        return $string;
    }

    /**
     * Returns a timestamp from the value stored in the search engine.
     *
     * By default it just returns a timestamp so any search engine could just store integers
     * and use integers comparison to get documents between x and y timestamps, but search
     * engines might be interested in using their own field formats. They should do it extending
     * this class in \search_xxx\document.
     *
     * @param string $time
     * @return int
     */
    public static function import_time_from_engine($time) {
        return $time;
    }

    /**
     * Fills the document with data coming from the search engine.
     *
     * @throws \core_search\engine_exception
     * @param array $docdata
     * @return void
     */
    public function set_data_from_engine($docdata) {
        $fields = static::$requiredfields + static::$optionalfields;
        foreach ($fields as $fieldname => $field) {

            // Optional params might not be there.
            if (!empty($docdata[$fieldname])) {
                if ($field['type'] === 'tdate') {
                    // Time fields may need a preprocessing.
                    $this->set($fieldname, static::import_time_from_engine($docdata[$fieldname]));
                } else {
                    // No way we can make this work if there is any multivalue field.
                    if (is_array($docdata[$fieldname])) {
                        throw new \core_search\engine_exception('multivaluedfield', 'search_solr', '', $fieldname);
                    }
                    $this->set($fieldname, $docdata[$fieldname]);
                }
            }
        }
    }

    /**
     * docurl setter
     *
     * @param \moodle_url $url
     * @return void
     */
    public function set_doc_url(\moodle_url $url) {
        $this->docurl = $url;
    }

    /**
     * Gets the url to the doc.
     *
     * @throws \moodle_exception
     * @return \moodle_url
     */
    public function get_doc_url() {
        if (empty($this->docurl)) {
            throw new \moodle_exception('docwithoutlink', 'search');
        }

        return $this->docurl;
    }

    public function set_context_url(\moodle_url $url) {
        $this->contexturl = $url;
    }

    /**
     * Gets the url to the context.
     *
     * @throws \moodle_exception
     * @return \moodle_url
     */
    public function get_context_url() {
        if (empty($this->contexturl)) {
            throw new \moodle_exception('docwithoutlink', 'search');
        }

        return $this->contexturl;
    }

    /**
     * Sets the document filearea if it has one.
     *
     * It depends on the component.
     *
     * @return string
     */
    public function set_filearea($filearea) {
        $this->filearea = $filearea;
    }

    /**
     * Gets the document filearea if it has one.
     * @return string
     */
    public function get_filearea() {
        return $this->filearea;
    }

    /**
     * Returns the document ready to submit to the search engine.
     *
     * @throws \coding_exception
     * @return array
     */
    public function export_for_engine() {

        // We accept an empty time modified and we fallback to created.
        if (empty($this->data['modified'])) {
            if (empty($this->data['created'])) {
                throw new \coding_exception('Missing created field in document with id "' . $this->data['id'] . '"');
            }
            $this->data['modified'] = $this->data['created'];
        }

        // We don't want to affect the document instance.
        $data = $this->data;

        // Apply specific engine-dependant formats and restrictions.
        foreach (static::$requiredfields as $fieldname => $field) {

            // We also check that we have everything we need; they all need a value, !isset is not enough.
            if (!isset($data[$fieldname])) {
                throw new \coding_exception('Missing "' . $fieldname . '" field in document with id "' . $this->data['id'] . '"');
            }

            if ($field['type'] === 'tdate') {
                // Overwrite the timestamp with the engine dependant format.
                $data[$fieldname] = static::format_time_for_engine($data[$fieldname]);
            } else if ($field['type'] === 'string') {
                // Overwrite the timestamp with the engine dependant format.
                $data[$fieldname] = static::format_string_for_engine($data[$fieldname]);
            }
        }

        foreach (static::$optionalfields as $fieldname => $field) {
            if (!isset($data[$fieldname])) {
                continue;
            }
            if ($field['type'] === 'tdate') {
                // Overwrite the timestamp with the engine dependant format.
                $data[$fieldname] = static::format_time_for_engine($data[$fieldname]);
            } else if ($field['type'] === 'string') {
                // Overwrite the timestamp with the engine dependant format.
                $data[$fieldname] = static::format_string_for_engine($data[$fieldname]);
            }
        }

        return $data;
    }

    /**
     * Export the document data to be used as a template context.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $created = null;
        if ($this->get('created')) {
            $created = userdate($this->get('modified'));
        }
        $modified = null;
        if ($this->get('modified')) {
            $modified = userdate($this->get('modified'));
        }
        // Pity that we have to include the separator this way.
        return [
            'courseurl' => new \moodle_url('/course/view.php?id=' . $this->get('courseid')),
            'coursefullname' => $this->format_string($this->get('coursefullname')),
            'userurl' => new \moodle_url('/user/view.php', array('id' => $this->get('userid'), 'course' => $this->get('courseid'))),
            'userfullname' => $this->format_string($this->get('userfullname')),
            'modified' => $modified,
            'created' => $created,
            'title' => $this->format_string($this->get('title')),
            'docurl' => $this->get_doc_url(),
            'content' => $this->format_text($this->get('content'), $this->get('contentformat'),
                $this->get_filearea(), $this->get('itemid')),
            'contexturl' => $this->get_context_url(),
            'name' => $this->format_string($this->get('name')),
            'intro' => $intro,
            'separator' => get_separator(),
        ];
    }

    /**
     * Formats a text string coming from the search engine.
     *
     * @param  string $text Text to format
     * @param  int    $format Identifier of the text format to be used
     * @param  string $filearea The text filearea
     * @param  int    $itemid
     * @return string
     */
    protected function format_text($text, $format, $filearea, $itemid) {

        // If the document's component don't have a filearea we don't need to rewrite the text.
        if ($filearea) {
            $text = file_rewrite_pluginfile_urls($text, 'pluginfile.php', $this->get('contextid'), $this->get('component'),
                $filearea, $itemid);
        }

        // Trust would depend on the user that added the content, is complicated to check and would require extra fields.
        $options = array('context' => $this->get('contextid'), 'trusted' => false);
        return shorten_text(format_text($text, $format, $options), 200);
    }

    /**
     * Formats a string coming from the search engine.
     *
     * @param string $string
     * @return string
     */
    protected function format_string($string) {
        return shorten_text(format_string($string, true, array('context' => $this->get('contextid'))), 100);
    }
}
