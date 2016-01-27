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
 * Solr engine.
 *
 * @package    search_solr
 * @copyright  2015 Daniel Neis Araujo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_solr;

defined('MOODLE_INTERNAL') || die();

/**
 * Solr engine.
 *
 * @package    search_solr
 * @copyright  2015 Daniel Neis Araujo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class engine extends \core_search\engine {

    /**
     * @var string The date format used by solr.
     */
    const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @var int Highlighting fragsize.
     */
    const SET_FRAG_SIZE = 500;

    /**
     * @var int Commit documents interval (number of miliseconds).
     */
    const AUTOCOMMIT_WITHIN = 15000;

    /**
     * @var \SolrClient
     */
    protected $client = null;

    /**
     * @var array Fields that can be highlighted.
     */
    protected $highlightfields = array('title', 'content', 'userfullname', 'name', 'intro');

    /**
     * Prepares a Solr query, applies filters and executes it returning its results.
     *
     * @throws \core_search\engine_exception
     * @param stdClass $data containing query and filters.
     * @param array $usercontexts Context where the user has access. Empty if can access all contexts.
     * @return \core_search\document[] Results or false if no results
     */
    public function execute_query($data, $usercontexts) {
        global $USER, $CFG;

        // If there is any problem we trigger the exception as soon as possible.
        $this->client = $this->get_search_client();

        if (!$this->is_server_ready()) {
            throw new \core_search\engine_exception('engineserverstatus', 'search');
        }

        $query = new \SolrQuery();
        $this->set_query($query, $data->queryfield);
        $this->add_fields($query);

        // Search filters applied.
        if (!empty($data->title)) {
            $query->addFilterQuery('title:' . $data->title);
        }
        if (!empty($data->author)) {
            $query->addFilterQuery('userfullname:' . $data->author);
        }
        if (!empty($data->component)) {
            $query->addFilterQuery('component:' . $data->component);
        }

        if (!empty($data->timestart) or !empty($data->timeend)) {
            if (empty($data->timestart)) {
                $data->timestart = '*';
            } else {
                $data->timestart = \search_solr\document::format_time_for_engine($data->timestart);
            }
            if (empty($data->timeend)) {
                $data->timeend = '*';
            } else {
                $data->timeend = \search_solr\document::format_time_for_engine($data->timeend);
            }

            $query->addFilterQuery('modified:[' . $data->timestart . ' TO ' . $data->timeend . ']');
        }

        // And finally restrict it to the context where the user can access.
        if ($usercontexts) {
            if (!empty($data->component)) {
                $query->addFilterQuery('contextid:(' . implode(' OR ', $usercontexts[$data->component]) . ')');
            } else {
                // Join all components context into a single array and implode.
                $allcontexts = array();
                foreach ($usercontexts as $componentcontexts) {
                    foreach ($componentcontexts as $contextid) {
                        // Ensure they are unique.
                        $allcontexts[$contextid] = $contextid;
                    }
                }
                $query->addFilterQuery('contextid:(' . implode(' OR ', $allcontexts) . ')');
            }
        }

        try {
            return $this->query_response($this->client->query($query));
        } catch (\SolrClientException $ex) {
            debugging('Error executing the provided query: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return array();
        }
    }

    /**
     * Prepares a new query by setting the query, start offset and rows to return.
     * @param SolrQuery $query
     * @param object $queryfield Containing query and filters.
     */
    public function set_query($query, $queryfield) {

        // Set hightlighting.
        $query->setHighlight(true);
        foreach ($this->highlightfields as $field) {
            $query->addHighlightField($field);
        }
        $query->setHighlightFragsize(static::SET_FRAG_SIZE);
        $query->setHighlightSimplePre('<span class="highlight">');
        $query->setHighlightSimplePost('</span>');

        $query->setQuery($queryfield);

        // A reasonable max.
        $query->setRows(\core_search\manager::MAX_RESULTS);
    }

    /**
     * Sets fields to be returned in the result.
     *
     * These fields should be the same fields specified as 'stored'.
     *
     * @param SolrQuery $query object.
     */
    public function add_fields($query) {
        $fields = array('id', 'itemid', 'title', 'content', 'userfullname', 'contextid', 'component', 'type', 'courseid', 'userid', 'created', 'modified', 'name', 'intro');

        foreach ($fields as $field) {
            $query->addField($field);
        }
    }

    /**
     * Finds the key common to both highlighing and docs array returned from response.
     * @param object $response containing results.
     */
    public function add_highlight_content($response) {
        $highlightedobject = $response->highlighting;
        foreach ($response->response->docs as $doc) {
            $x = $doc->id;
            $highlighteddoc = $highlightedobject->$x;
            $this->merge_highlight_field_values($doc, $highlighteddoc);
        }
    }

    /**
     * Adds the highlighting array values to docs array values.
     *
     * @throws \core_search\engine_exception
     * @param object $doc containing the results.
     * @param object $highlighteddoc containing the highlighted results values.
     */
    public function merge_highlight_field_values($doc, $highlighteddoc) {

        foreach ($this->highlightfields as $field) {
            if (!empty($doc->$field)) {
                switch ($field) {
                    case 'userfullname':
                        if (!empty($highlighteddoc->$field)) {
                            $doc->$field = $highlighteddoc->$field;
                        }
                        break;

                    default:
                        if (!empty($highlighteddoc->$field)) {
                            // Replace by the highlighted result.
                            $doc->$field = $highlighteddoc->$field;
                        } else {
                            // Returned field value.

                            // No way we can make this work if there is any multivalue field.
                            if (is_array($doc->{$field})) {
                                // Triggering it here as it is the first time we inspect the returned field's contents.
                                throw new \core_search\engine_exception('multivaluedfield', 'search_solr', '', $field);
                            }
                            $doc->$field = substr($doc->{$field}, 0, static::SET_FRAG_SIZE);
                        }
                        break;
                }
            }
        }
    }

    /**
     * Filters the response on Moodle side.
     *
     * @param object $queryresponse containing the response return from solr server.
     * @return array $results containing final results to be displayed.
     */
    public function query_response($queryresponse) {

        $response = $queryresponse->getResponse();
        $numgranted = 0;

        $docs = $response->response->docs;
        if (!empty($response->response->numFound)) {
            $this->add_highlight_content($response);

            // Iterate through the results checking its availability and whether they are available for the user or not.
            foreach ($docs as $key => $docdata) {
                $componentname = $docdata->component;
                if (!$componentsearch = $this->get_search_component($docdata->component)) {
                    unset($docs[$key]);
                    continue;
                }

                $docdata = $this->standarize_solr_obj($docdata);

                $access = $componentsearch->check_access($docdata['itemid']);
                switch ($access) {
                    case \core_search\manager::ACCESS_DELETED:
                        $this->delete_by_id($docdata['id']);
                        unset($docs[$key]);
                        break;
                    case \core_search\manager::ACCESS_DENIED:
                        unset($docs[$key]);
                        break;
                    case \core_search\manager::ACCESS_GRANTED:
                        $numgranted++;

                        // Add the doc.
                        $docs[$key] = $this->to_document($componentsearch, $docdata);
                        break;
                }

                // This should never happen.
                if ($numgranted >= \core_search\manager::MAX_RESULTS) {
                    $docs = array_slice($docs, 0, \core_search\manager::MAX_RESULTS, true);
                    break;
                }
            }
        }

        return $docs;
    }

    /**
     * Returns a standard php array from a \SolrObject instance.
     *
     * @param \SolrObject $obj
     * @return array The returned document as an array.
     */
    public function standarize_solr_obj(\SolrObject $obj) {
        $properties = $obj->getPropertyNames();

        $docdata = array();
        foreach($properties as $name) {
            // http://php.net/manual/en/solrobject.getpropertynames.php#98018.
            $name = trim($name);
            $docdata[$name] = $obj->offsetGet($name);
        }
        return $docdata;
    }

    /**
     * Adds a document to the search engine.
     *
     * This does not commit to the search engine.
     *
     * @param array $doc
     * @return void
     */
    public function add_document($doc) {

        $solrdoc = new \SolrInputDocument();
        foreach ($doc as $field => $value) {
            $solrdoc->addField($field, $value);
        }

        try {
            $result = $this->get_search_client()->addDocument($solrdoc, true, static::AUTOCOMMIT_WITHIN);
        } catch (\SolrClientException $e) {
            debugging('Solr client error adding document with id ' . $doc['id'] . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Commits all pending changes.
     *
     * @return void
     */
    public function commit() {
        $this->get_search_client()->commit();
    }

    /**
     * Defragments the index.
     *
     * @return void
     */
    public function optimize() {
        $this->get_search_client()->optimize();
    }

    /**
     * Deletes the specified document.
     *
     * @param string $id The document id to delete
     * @return void
     */
    public function delete_by_id($id) {
        $this->get_search_client()->deleteById($id);
    }

    /**
     * Delete all component documents.
     *
     * @param string $componentname Frankenstyle name
     * @return void
     */
    public function delete($componentname = null) {
        if ($componentname) {
            $this->get_search_client()->deleteByQuery('component:' . $componentname);
        } else {
            $this->get_search_client()->deleteByQuery('*:*');
        }
    }

    /**
     * Pings the Solr server using search_solr config
     *
     * @return bool
     */
    public function is_server_ready() {

        if (!$this->client = $this->get_search_client(false)) {
            debugging('Error connecting to solr server, ensure that the hostname and the collection you specified are correct and that the server is ready and available.', DEBUG_DEVELOPER);
            return false;
        }

        try {
            @$this->client->ping();
            return true;
        } catch (\SolrClientException $ex) {
            debugging('Solr client error: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\SolrServerException $ex) {
            debugging('Solr server error: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
        // Let other exceptions be triggered as usual.
    }

    /**
     * Checks if the PHP Solr extension is available.
     *
     * @return bool
     */
    public function is_installed() {
        return function_exists('solr_get_version');
    }

    /**
     * Returns the solr client instance.
     *
     * @throws \core_search\engine_exception
     * @param bool $triggerexception
     * @return \SolrClient
     */
    public function get_search_client($triggerexception = true) {

        // Type comparison as it is set to false if not available.
        if ($this->client !== null) {
            return $this->client;
        }

        if (!function_exists('solr_get_version')) {
            debugging('Solr PHP extension not available.', DEBUG_DEVELOPER);
            $this->client = false;

        } else if (empty($this->config->server_hostname) || empty($this->config->collectionname)) {
            debugging('No solr configuration found.', DEBUG_DEVELOPER);
            $this->client = false;

        } else {

            $options = array(
                'hostname' => $this->config->server_hostname,
                'path'     => '/solr/' . $this->config->collectionname,
                'login'    => !empty($this->config->server_username) ? $this->config->server_username : '',
                'password' => !empty($this->config->server_password) ? $this->config->server_password : '',
                'port'     => !empty($this->config->server_port) ? $this->config->server_port : '',
                'issecure' => !empty($this->config->secure) ? $this->config->secure : '',
                'ssl_cert' => !empty($this->config->ssl_cert) ? $this->config->ssl_cert : '',
                'ssl_cert_only' => !empty($this->config->ssl_cert_only) ? $this->config->ssl_cert_only : '',
                'ssl_key' => !empty($this->config->ssl_key) ? $this->config->ssl_key : '',
                'ssl_password' => !empty($this->config->ssl_keypassword) ? $this->config->ssl_keypassword : '',
                'ssl_cainfo' => !empty($this->config->ssl_cainfo) ? $this->config->ssl_cainfo : '',
                'ssl_capath' => !empty($this->config->ssl_capath) ? $this->config->ssl_capath : '',
            );

            // If php solr extension 1.0.3-alpha installed, one may choose 3.x or 4.x solr from admin settings page.
            $this->client = new \SolrClient($options);
        }

        if ($this->client === false && $triggerexception) {
            throw new \core_search\engine_exception('engineserverstatus', 'search_solr');
        }

        return $this->client;
    }
}
