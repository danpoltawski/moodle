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
 * Flash Messages Tests
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\output\flash_messages;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_output_flash_messages_test extends basic_testcase {
    protected $_session;

    /**
     * @var flash_messages
     */
    protected $_flash;

    protected function setUp() {
        $this->_session = new stdClass;
        $this->_flash = new flash_messages('', '_flashmessages', $this->_session);
    }

    public function test_add_string() {
        $this->_flash->add_string('test');
        $this->_flash->add_string('test2', flash_messages::GOOD);

        $expected = (object) array(
            '_flashmessages' => array(
                flash_messages::BAD => array(
                    'test',
                ),
                flash_messages::GOOD => array(
                    'test2',
                )
            )
        );

        $this->assertEquals($expected, $this->_session);
    }

    public function test_has_messages() {
        $this->assertFalse($this->_flash->has_messages(flash_messages::BAD));
        $this->_flash->add_string('test');
        $this->assertTrue($this->_flash->has_messages(flash_messages::BAD));
    }

    public function test_get_messages() {
        $this->_flash->add_string('test');
        $this->_flash->add_string('test2', flash_messages::GOOD);

        $expected = (object) array(
            '_flashmessages' => array(
                flash_messages::GOOD => array(
                    'test2',
                )
            )
        );

        $this->assertEquals(array('test'), $this->_flash->get_messages(flash_messages::BAD));
        $this->assertEquals($expected, $this->_session);

        $this->assertEquals(array('test2'), $this->_flash->get_messages(flash_messages::GOOD));
        $this->assertFalse(property_exists($this->_session, '_flashmessages'));
    }

    public function test_get_all_messages() {
        $this->_flash->add_string('test');
        $this->_flash->add_string('test2', flash_messages::GOOD);

        $expected = array(
            flash_messages::BAD  => array(
                'test',
            ),
            flash_messages::GOOD => array(
                'test2',
            )
        );

        $this->assertEquals($expected, $this->_flash->get_all_messages());
        $this->assertFalse(property_exists($this->_session, '_flashmessages'));
        $this->assertEquals(0, count(get_object_vars($this->_session)));
    }
}