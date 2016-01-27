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
 * Solr schema manipulation manager.
 *
 * @package   search_solr
 * @copyright 2015 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_solr;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/filelib.php');

/**
 * Schema class to interact with Solr schema.
 *
 * At the moment it only implements create which should be enough for a basic
 * moodle configuration in Solr.
 *
 * @package   search_solr
 * @copyright 2015 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class schema {

    /**
     * @var stdClass
     */
    protected $config = null;

    /**
     * Constructor.
     *
     * @throws \moodle_exception
     * @return void
     */
    public function __construct() {
        if (!$this->config = get_config('search_solr')) {
            throw new \moodle_exception('solrnotset', 'search_solr');
        }

        if (empty($this->config->server_hostname) || empty($this->config->collectionname)) {
            throw new \moodle_exception('solrnotset', 'search_solr');
        }
    }

    /**
     * Setup solr stuff required by moodle.
     *
     * @param  bool $checkexisting Whether to check if the fields already exist or not
     * @return bool
     */
    public function setup($checkexisting = true) {
        $fields = \search_solr\document::get_default_fields_definition();

        // Field id is already there.
        unset($fields['id']);

        return $this->add_fields($fields, $checkexisting);
    }

    /**
     * Adds the provided fields to Solr schema.
     *
     * Intentionally separated from create(), it can be called to add extra fields.
     * fields separately.
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     * @param  array $fields \core_search\document::$requiredfields format
     * @param  bool $checkexisting Whether to check if the fields already exist or not
     * @return bool
     */
    public function add_fields($fields, $checkexisting = true) {

        $curl = new \curl();

        if (!$this->config->server_hostname || !$this->config->collectionname) {
            throw new \moodle_exception('missingconfig', 'search_solr');
        }

        $url = rtrim($this->config->server_hostname, '/');
        if (!empty($this->config->server_port)) {
            $url .= ':' . $this->config->server_port;
        }
        $url .= '/solr/' . $this->config->collectionname;
        $schemaurl = $url . '/schema';

        // HTTP headers.
        $curl->setHeader('Content-type: application/json');
        if (!empty($this->config->server_username) && !empty($this->config->server_password)) {
            $authorization = $this->config->server_username . ':' . $this->config->server_password;
            $curl->setHeader('Authorization', 'Basic ' . base64_encode($authorization));
        }

        // Check that the server is available and the collection exists.
        $result = $curl->get($url . '/select?wt=json');
        if ($curl->error) {
            throw new \moodle_exception('connectionerror', 'search_solr');
        }
        if ($curl->info['http_code'] === 404) {
            throw new \moodle_exception('connectionerror', 'search_solr');
        }

        // Check that non of them exists.
        if ($checkexisting) {
            foreach ($fields as $fieldname => $data) {
                $results = $curl->get($schemaurl . '/fields/' . $fieldname);

                if ($curl->error) {
                    throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $curl->error);
                }

                if (!$results) {
                    throw new \moodle_exception('errorcreatingschema', 'search_solr', '', get_string('nodatafromserver', 'search_solr'));
                }
                $results = json_decode($results);

                // The field should not exist so we only accept 404 errors.
                if (empty($results->error) || (!empty($results->error) && $results->error->code !== 404)) {
                    if (!empty($results->error)) {
                        $errormsg = $results->error->msg;
                    } else {
                        $errormsg = get_string('schemafieldalreadyexists', 'search_solr', $fieldname);
                    }
                    throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $errormsg);
                }
            }
        }

        // Add all fields.
        foreach ($fields as $fieldname => $data) {

            if (!isset($data['type']) || !isset($data['stored']) || !isset($data['indexed'])) {
                throw new \coding_exception($fieldname . ' does not define all required field params: type, stored and indexed.');
            }
            // Changing default multiValued value to false as we want to match values easily.
            $params = array(
                'add-field' => array(
                    'name' => $fieldname,
                    'type' => $data['type'],
                    'stored' => $data['stored'],
                    'multiValued' => false,
                    'indexed' => $data['indexed']
                )
            );
            $results = $curl->post($schemaurl, json_encode($params));

            // We only validate if we are interested on it.
            if ($checkexisting) {
                if ($curl->error) {
                    throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $curl->error);
                }
                $this->check_results($results);
            }
        }

        return true;
    }

    /**
     * Checks that the results do not contain errors.
     *
     * @throws \moodle_exception
     * @param string $results curl response body
     * @return void
     */
    public function check_results($result) {

        if (!$result) {
            throw new \moodle_exception('errorcreatingschema', 'search_solr', '', get_string('nodatafromserver', 'search_solr'));
        }

        $results = json_decode($result);
        if (!$results) {
            if (is_scalar($result)) {
                $errormsg = $result;
            } else {
                $errormsg = json_encode($result);
            }
            throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $errormsg);
        }

        // It comes as error when fetching fields data.
        if (!empty($results->error)) {
            throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $results->error);
        }

        // It comes as errors when adding fields.
        if (!empty($results->errors)) {

            // We treat this error separately.
            $errorstr = '';
            foreach ($results->errors as $error) {
                $errorstr .= implode(', ', $error->errorMessages);
            }
            throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $errorstr);
        }

    }
}
