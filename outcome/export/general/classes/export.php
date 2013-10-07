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
 * Moodle General Outcome Set Export
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace outcomeexport_general;

use core_outcome\export\export_interface;
use core_outcome\model\outcome_model;
use core_outcome\model\outcome_set_model;
use XMLWriter;

defined('MOODLE_INTERNAL') || die();

/**
 * Basic export XML format.
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export implements export_interface {

    public function get_extension() {
        return 'xml';
    }

    public function export($file, outcome_set_model $outcomeset, array $outcomes) {
        $writer = new XMLWriter();
        $writer->openUri($file);

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);
        $writer->setIndentString('    ');

        $writer->startElement('data');
        $writer->writeAttribute('component', 'outcomeexport_general');

        $this->export_outcome_set($writer, $outcomeset);
        foreach ($outcomes as $outcome) {
            $this->export_outcome($writer, $outcome);
        }
        $writer->endElement();
    }

    public function export_outcome_set(XMLWriter $writer, outcome_set_model $model) {
        $writer->startElement('outcomeSet');
        $writer->writeElement('id', $model->id);
        $writer->writeElement('idnumber', $model->idnumber);
        $writer->writeElement('name', $model->name);
        $writer->writeElement('description', $model->description);
        $writer->writeElement('provider', $model->provider);
        $writer->writeElement('revision', $model->revision);
        $writer->writeElement('region', $model->region);
        $writer->writeElement('deleted', $model->deleted);
        $writer->endElement();
    }

    public function export_outcome(XMLWriter $writer, outcome_model $model) {
        $writer->startElement('outcome');
        $writer->writeElement('id', $model->id);
        $writer->writeElement('parentid', $model->parentid);
        $writer->writeElement('idnumber', $model->idnumber);
        $writer->writeElement('docnum', $model->docnum);
        $writer->writeElement('description', $model->description);
        $writer->writeElement('assessable', $model->assessable);
        $writer->writeElement('deleted', $model->deleted);

        foreach ($model->subjects as $subject) {
            $writer->writeElement('subject', $subject);
        }
        foreach ($model->edulevels as $edulevel) {
            $writer->writeElement('edulevel', $edulevel);
        }
        $writer->endElement();
    }
}
