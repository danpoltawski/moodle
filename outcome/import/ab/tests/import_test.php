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
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\model\outcome_model;
use core_outcome\model\outcome_set_model;
use outcomeimport_ab\import;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcomeimport_ab_import_test extends basic_testcase {

    public function test_process_file() {
        $setmock = $this->getMock('\core_outcome\service\outcome_set_helper', array('save_outcome_set'));
        $setmock->expects($this->once())
            ->method('save_outcome_set')
            ->with($this->isInstanceOf('\core_outcome\model\outcome_set_model'));

        $outcomemock = $this->getMock('\core_outcome\service\outcome_helper', array('save_outcome'));
        $outcomemock->expects($this->exactly(2))
            ->method('save_outcome')
            ->with($this->isInstanceOf('\core_outcome\model\outcome_model'));

        $import = new import($outcomemock, $setmock);
        $import->process_file(__DIR__.'/fixtures/Base.xml');

        $this->assertInstanceOf('\core_outcome\model\outcome_set_model', $import->get_result());
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_process_file_json() {
        $import = new import();
        $import->process_file('./NotRealFile.json');
    }

    public function test_process_outcome_set() {
        $mock = $this->getMock('\core_outcome\service\outcome_set_helper', array('save_outcome_set'));
        $mock->expects($this->once())
            ->method('save_outcome_set')
            ->with($this->isInstanceOf('\core_outcome\model\outcome_set_model'));

        $import = new import(null, $mock);
        $model  = null;
        $reader = new XMLReader();
        $reader->open(__DIR__.'/fixtures/standard_document.xml');
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'standard_document') {
                $model = $import->process_outcome_set($reader);
                break;
            }
        }
        $reader->close();

        $this->assertInstanceOf('\core_outcome\model\outcome_set_model', $model);
        $this->assertSame($model, $import->get_outcomeset());
        $this->assertEquals('IDNUMBER', $model->idnumber);
        $this->assertEquals('NAME', $model->name);
        $this->assertEquals('REVISION', $model->revision);
        $this->assertEquals('PROVIDER', $model->provider);
        $this->assertEquals('REGION', $model->region);
        $this->assertEquals(array('SUBJECT'), $import->get_subjects());
        $this->assertEquals(array('EDULEVEL'), $import->get_education_levels('EDULEVELCODE'));
    }

    public function test_process_outcomes() {
        $mock = $this->getMock('\core_outcome\service\outcome_helper', array('save_outcome'));
        $mock->expects($this->once())
            ->method('save_outcome')
            ->with($this->isInstanceOf('\core_outcome\model\outcome_model'));

        $outcomeset     = new outcome_set_model();
        $outcomeset->id = 1;

        $import = new import($mock);
        $import->set_outcomeset($outcomeset);
        $import->add_education_level('EDULEVELCODE', 'EDULEVEL');
        $import->add_subject('SUBJECTCODE', 'SUBJECT');

        $model  = null;
        $reader = new XMLReader();
        $reader->open(__DIR__.'/fixtures/standard.xml');

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'standard') {
                $model = $import->process_outcomes($reader);
                break;
            }
        }
        $reader->close();

        $this->assertInstanceOf('\core_outcome\model\outcome_model', $model);
        $this->assertEquals('IDNUMBER', $model->idnumber);
        $this->assertEquals('DESCRIPTION', $model->description);
        $this->assertEquals(0, $model->assessable);
        $this->assertEquals(array('EDULEVEL'), $model->edulevels);
        $this->assertEquals(array('SUBJECT'), $model->subjects);
        $this->assertNull($model->parentid);
    }

    public function test_process_outcome() {
        $mock = $this->getMock('\core_outcome\service\outcome_helper', array('save_outcome'));
        $mock->expects($this->once())
            ->method('save_outcome')
            ->with($this->isInstanceOf('\core_outcome\model\outcome_model'));

        $outcomeset     = new outcome_set_model();
        $outcomeset->id = 1;

        $root = new outcome_model();
        $root->id = 1;
        $root->edulevels = array('EDULEVEL');
        $root->subjects = array('SUBJECT');

        $import = new import($mock);
        $import->set_outcomeset($outcomeset);

        $model  = null;
        $reader = new XMLReader();
        $reader->open(__DIR__.'/fixtures/item.xml');

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'item') {
                $model = $import->process_outcome($root, $reader);
                break;
            }
        }
        $reader->close();

        $this->assertInstanceOf('\core_outcome\model\outcome_model', $model);
        $this->assertEquals($root->id, $model->parentid);
        $this->assertEquals('IDNUMBER', $model->idnumber);
        $this->assertEquals('DESCRIPTION', $model->description);
        $this->assertEquals(1, $model->assessable);
        $this->assertEquals('DOCNUM', $model->docnum);
        $this->assertEquals(array('EDULEVEL'), $model->edulevels);
        $this->assertEquals(array('SUBJECT'), $model->subjects);

        // We assert null because our mock prevents ID from being set.
        $this->assertNull($import->get_map_value('IDNUMBER'), 'Outcome registered itself in the map');
    }
}