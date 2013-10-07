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

use core_outcome\model\outcome_set_model;
use outcomeimport_general\import;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcomeimport_general_import_test extends basic_testcase {

    public function test_process_file() {
        $setmock = $this->getMock('\core_outcome\service\outcome_set_helper', array('save_outcome_set'));
        $setmock->expects($this->once())
            ->method('save_outcome_set')
            ->with($this->isInstanceOf('\core_outcome\model\outcome_set_model'));

        $outcomemock = $this->getMock('\core_outcome\service\outcome_helper', array('save_outcome'));
        $outcomemock->expects($this->once())
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
        $reader->open(__DIR__.'/fixtures/outcomeSet.xml');
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'outcomeSet') {
                $model = $import->process_outcome_set($reader);
                break;
            }
        }
        $reader->close();

        $this->assertInstanceOf('\core_outcome\model\outcome_set_model', $model);
        $this->assertSame($model, $import->get_outcomeset());
        $this->assertEquals('IDNUMBER', $model->idnumber);
        $this->assertEquals('Name', $model->name);
        $this->assertEquals('Description', $model->description);
        $this->assertEquals('Revision', $model->revision);
        $this->assertEquals('Provider', $model->provider);
        $this->assertEquals('Region', $model->region);
    }

    public function test_process_outcome() {
        $mock = $this->getMock('\core_outcome\service\outcome_helper', array('save_outcome'));
        $mock->expects($this->once())
            ->method('save_outcome')
            ->with($this->isInstanceOf('\core_outcome\model\outcome_model'));

        $outcomeset     = new outcome_set_model();
        $outcomeset->id = 1;

        $import = new import($mock);
        $import->set_outcomeset($outcomeset);
        $import->set_map_value('1', 5);

        $model  = null;
        $reader = new XMLReader();
        $reader->open(__DIR__.'/fixtures/outcome.xml');

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT and $reader->name == 'outcome') {
                $model = $import->process_outcome($reader);
                break;
            }
        }
        $reader->close();

        $this->assertInstanceOf('\core_outcome\model\outcome_model', $model);
        $this->assertEquals('IDNUMBER', $model->idnumber);
        $this->assertEquals('A.B.C', $model->docnum);
        $this->assertEquals('Description', $model->description);
        $this->assertEquals(1, $model->assessable);
        $this->assertEquals(0, $model->deleted);
        $this->assertEquals(array('9', '10'), $model->edulevels);
        $this->assertEquals(array('Math', 'English'), $model->subjects);
        $this->assertEquals(5, $model->parentid);

        // We assert null because our mock prevents ID from being set.
        $this->assertNull($import->get_map_value('5'), 'Outcome registered itself in the map');
    }
}