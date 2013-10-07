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
 * Academic Benchmarks (AB) Importer
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace outcomeimport_ab;

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
    /**
     * Keep track of available education levels
     *
     * @var array
     */
    protected $edulevels = array();

    /**
     * Keep track of the available subjects
     *
     * @var array
     */
    protected $subjects = array();

    public function process_file($file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) != 'xml') {
            throw new \moodle_exception('pleaseusexml', 'outcomeimport_ab');
        }
        $reader = new XMLReader();
        $reader->open($file);

        while ($reader->read()) {
            if ($reader->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->name == 'standard_document') {
                $this->process_outcome_set($reader);
            }
            if ($reader->name == 'standard') {
                $this->process_outcomes($reader);
            }
        }
        $reader->close();
    }

    /**
     * We are inside of a <standard_document> tag, read until
     * we get to the <standard> tag while extracting outcome
     * set attributes as well as subjects and education levels
     * used by this outcome set.
     *
     * @param XMLReader $reader
     * @return outcome_set_model
     * @throws coding_exception
     */
    public function process_outcome_set(XMLReader $reader) {
        $model = new outcome_set_model();
        $model->idnumber = $reader->getAttribute('uid');
        $model->provider = $reader->getAttribute('uid_provider');
        $model->revision = $reader->getAttribute('revision_date');

        while ($reader->read()) {
            if ($reader->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->name == 'standard') {
                break;
            }
            switch ($reader->name) {
                case 'title':
                    $this->read_value($model->name, $reader);
                    break;
                case 'subject':
                    $code = $reader->getAttribute('code');
                    $this->read_value($label, $reader);
                    $this->add_subject($code, $label);
                    break;
                case 'grade_range':
                    $code = $reader->getAttribute('code');
                    $this->read_value($label, $reader);
                    $this->add_education_level($code, $label);
                    break;
                case 'organization':
                    $this->read_value($model->region, $reader);
                    break;
            }
        }
        $this->save_outcome_set($model);

        return $model;
    }

    /**
     * We convert the <standard> tag into a non-assessable outcome and add
     * all outcomes within the standard underneath it.
     *
     * @param XMLReader $reader
     * @return outcome_model
     */
    public function process_outcomes(XMLReader $reader) {
        $model = new outcome_model();
        $model->parentid   = null;
        $model->idnumber   = $reader->getAttribute('uid');
        $model->assessable = 0;

        // We cheat to safely extract title and grade range so we can save parent before processing children.
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $xml = simplexml_import_dom($doc->importNode($reader->expand(), true));

        $model->description = (string) $xml->title;
        $model->subjects    = $this->get_subjects();
        $model->edulevels   = $this->get_education_levels((string) $xml->grade_range_ref['code']);

        unset($doc, $xml);

        $this->save_outcome($model);

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::END_ELEMENT and $reader->name == 'standard') {
                break;
            }
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'item') {
                $this->process_outcome($model, $reader);
            }
        }

        return $model;
    }

    /**
     * We are inside of a <item> tag, read until
     * the end of the tag while extracting outcome attributes.
     *
     * @param outcome_model $root This is the root outcome
     * @param XMLReader $reader
     * @throws coding_exception
     * @return outcome_model
     */
    public function process_outcome(outcome_model $root, XMLReader $reader) {
        $parentuid = $reader->getAttribute('parent_uid');
        if (empty($parentuid)) {
            $parentid = $root->id;
        } else {
            $parentid = $this->get_map_value($parentuid);
        }
        $model = new outcome_model();
        $model->parentid  = $parentid;
        $model->idnumber  = $reader->getAttribute('uid');
        $model->edulevels = $root->edulevels;
        $model->subjects  = $root->subjects;

        $docnum = $reader->getAttribute('doc_num');
        if (!empty($docnum)) {
            $model->docnum = $docnum;
        }
        if ($reader->getAttribute('linkable') == 'Y') {
            $model->assessable = 1;
        } else {
            $model->assessable = 0;
        }
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'statement') {
                $this->read_value($model->description, $reader);
                break;
            }
        }
        $this->save_outcome($model);
        $this->set_map_value($model->idnumber, $model->id);

        return $model;
    }

    public function add_education_level($code, $label) {
        $this->edulevels[$code] = $label;
    }

    /**
     * All outcomes in a standard belong to the same single
     * education level
     *
     * @param $code
     * @return array
     * @throws coding_exception
     */
    public function get_education_levels($code) {
        if (!array_key_exists($code, $this->edulevels)) {
            throw new coding_exception("Grade range code does not exist: $code");
        }
        return array($this->edulevels[$code]);
    }

    public function add_subject($code, $label) {
        $this->subjects[$code] = $label;
    }

    /**
     * Each outcome does not have a different set of subjects,
     * so just assign the same list of subjects to each
     * outcome.
     *
     * @return array
     */
    public function get_subjects() {
        return array_values($this->subjects);
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
}
