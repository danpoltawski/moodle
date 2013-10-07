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
 * Achievement Standards Network (ASN) Importer
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace outcomeimport_asn;

use coding_exception;
use core_outcome\import\import_abstract;
use core_outcome\model\outcome_model;
use core_outcome\model\outcome_set_model;
use XMLReader;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import extends import_abstract {

    public function process_file($file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) != 'xml') {
            throw new \moodle_exception('pleaseusexml', 'outcomeimport_asn');
        }
        $reader = new XMLReader();
        $reader->open($file);

        while ($reader->read()) {
            if ($reader->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            switch ($reader->name) {
                case 'asn:StandardDocument':
                    $this->process_outcome_set($reader);
                    break;
                case 'asn:Statement':
                    $this->process_outcome($reader);
                    break;
            }
        }
        $reader->close();
    }

    /**
     * We are inside of a <asn:StandardDocument> tag, read until
     * the end of the tag while extracting outcome set attributes.
     *
     * @param XMLReader $reader
     * @return outcome_set_model
     * @throws coding_exception
     */
    public function process_outcome_set(XMLReader $reader) {
        $model = new outcome_set_model();
        $model->provider = 'ASN';

        // The about attribute is used to lookup parents.
        // Any child of the outcome set has a parent ID of null.
        $model->idnumber = $reader->getAttribute('rdf:about');
        if (empty($model->idnumber)) {
            throw new coding_exception('Missing rdf:about attribute on asn:StandardDocument tag');
        }
        $this->set_map_value($model->idnumber, null);

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::END_ELEMENT and $reader->name == 'asn:StandardDocument') {
                break;
            }
            if ($reader->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            switch ($reader->name) {
                case 'dc:title':
                    $this->read_value($model->name, $reader);
                    break;
                case 'dcterms:description':
                    $this->read_value($model->description, $reader);
                    break;
                case 'asn:repositoryDate':
                    $this->read_value($model->revision, $reader);
                    break;
                case 'asn:jurisdiction':
                    $region = $reader->getAttribute('rdf:resource');
                    if (!empty($region)) {
                        $model->region = $this->trim_uri($region);
                    }
                    break;
            }
        }
        $this->save_outcome_set($model);

        return $model;
    }

    /**
     * We are inside of a <asn:Statement> tag, read until
     * the end of the tag while extracting outcome attributes.
     *
     * @param XMLReader $reader
     * @return outcome_model
     * @throws coding_exception
     */
    public function process_outcome(XMLReader $reader) {
        $model = new outcome_model();

        // The about attribute is used to lookup parents.
        $model->idnumber = $reader->getAttribute('rdf:about');
        if (empty($model->idnumber)) {
            throw new coding_exception('Missing rdf:about attribute on asn:Statement tag');
        }

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::END_ELEMENT and $reader->name == 'asn:Statement') {
                break;
            }
            if ($reader->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            switch ($reader->name) {
                case 'dcterms:description':
                    $this->read_value($model->description, $reader);
                    break;
                case 'asn:statementNotation':
                    $this->read_value($model->docnum, $reader);
                    break;
                case 'asn:indexingStatus':
                    $assessable = $this->trim_uri($reader->getAttribute('rdf:resource'));
                    if ($assessable == 'Yes') {
                        $model->assessable = 1;
                    } else {
                        $model->assessable = 0;
                    }
                    break;
                case 'dcterms:educationLevel':
                    $edulevel = $this->trim_uri($reader->getAttribute('rdf:resource'));
                    if (!empty($edulevel)) {
                        $model->edulevels[] = $this->get_education_level($edulevel);
                    }
                    break;
                case 'dcterms:subject':
                    $subject = $this->trim_uri($reader->getAttribute('rdf:resource'));
                    if (!empty($subject)) {
                        $model->subjects[] = $this->get_subject($subject);
                    }
                    break;
                case 'gemq:isChildOf':
                    $parent = $reader->getAttribute('rdf:resource');
                    if (empty($parent)) {
                        throw new coding_exception('Missing rdf:resource attribute on gemq:isChildOf tag under asn:Statement tag');
                    }
                    $model->parentid = $this->get_map_value($parent);
                    break;
            }
        }
        $this->save_outcome($model);
        $this->set_map_value($model->idnumber, $model->id);

        return $model;
    }

    /**
     * Helper method: read XML value and if it has a value, then
     * set it to the passed property.
     *
     * @param $property
     * @param XMLReader $reader
     */
    public function read_value(&$property, XMLReader $reader) {
        $reader->read();
        if ($reader->hasValue) {
            $property = $reader->value;
        }
    }

    /**
     * Subjects are stored in the lang file for customization or
     * for easy addition of new subjects.
     *
     * Subjects can be found http://elastic1.jesandco.org/asn/resolver/scheme/ASNTopic/
     *
     * Note: each subject can have child subjects,
     * like http://elastic1.jesandco.org/asn/resolver/scheme/ASNTopic/socialStudies
     *
     * @param $subject
     * @return string
     */
    public function get_subject($subject) {
        $identifier = 'subject:'.$subject;
        if (get_string_manager()->string_exists($identifier, 'outcomeimport_asn')) {
            return get_string($identifier, 'outcomeimport_asn');
        }
        debugging("Subject not found in lang file $subject (identifier = $identifier, component = outcomeimport_asn");

        return $subject;
    }

    /**
     * Education levels are stored in the lang file for customization or
     * for easy addition of new subjects.
     *
     * Education levels can be found http://elastic1.jesandco.org/asn/resolver/scheme/ASNEducationLevel/
     *
     * Note: each education level can have child levels,
     * like http://elastic1.jesandco.org/asn/resolver/scheme/ASNEducationLevel/PreKto12
     *
     * @param $edulevel
     * @return string
     */
    public function get_education_level($edulevel) {
        $identifier = 'edulevel:'.$edulevel;
        if (get_string_manager()->string_exists($identifier, 'outcomeimport_asn')) {
            return get_string($identifier, 'outcomeimport_asn');
        }
        debugging("Education level not found in lang file $edulevel (identifier = $identifier, component = outcomeimport_asn");

        return $edulevel;
    }

    /**
     * Get the last value at the end of a URI
     *
     * Example:
     *      Given http://purl.org/ASN/scheme/ASNEducationLevel/K
     *      Return K
     *
     * @param $value
     * @return string
     */
    public function trim_uri($value) {
        $pos = strrpos($value, '/');
        if ($pos === false) {
            return $value;
        }
        return substr($value, $pos + 1);
    }
}
