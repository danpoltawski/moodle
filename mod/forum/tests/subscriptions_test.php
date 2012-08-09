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
 * Tests for various forum subscription methods
 *
 * @package    mod_forum
 * @category   phpunit
 * @copyright  2012 Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for various forum subscription methods
 *
 * @package    mod_forum
 * @category   phpunit
 * @copyright  2012 Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forum_subscriptions_testcase extends advanced_testcase {
    /** @var array test users */
    private $users = array();
    /** @var stdClass course record from db */
    private $course = null;
    /** @var stdClass forum record from db */
    private $forum = null;
    /** @var stdClass coursemodule record*/
    private $cm = null;
    /** @var enrol_plugin manual enrolment plugin, set by setUp*/
    private $enrolplugin = null;
    /** @var enrol_plugin instance for enrolling in $course */
    private $enrolinstance = null;

    /**
     * Set up a forum instance in a course, and enrolment plugin to use
     * for enrolling users.
     */
    public function setUp() {
        $this->resetAfterTest(true);

        // Create 10 users.
        for ($i = 0; $i < 9; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }

        $this->course = $this->getDataGenerator()->create_course();
        $this->forum = $this->getDataGenerator()->create_module('forum', array('course'=>$this->course->id));
        $this->cm = get_coursemodule_from_instance('forum', $this->forum->id, $this->course->id, false, MUST_EXIST);

        // TODO: this should be a helper method in core??
        $this->enrolplugin = enrol_get_plugin('manual');
        $this->enrolplugin->add_instance($this->course);

        foreach (enrol_get_instances($this->course->id, false) as $instance) {
            if ($instance->enrol === 'manual') {
                $this->enrolinstance = $instance;
                break;
            }
        }

        // Test we have an enrol instance..
        $this->assertNotNull($this->enrolinstance);
    }

    /**
     * Helper method for enrolling a user in our course
     * @param int $userid
     */
    private function enrol_user($userid) {
        $this->enrolplugin->enrol_user($this->enrolinstance, $userid);
    }

    /**
     * Test forum_get_potential_subscribers() with emply list.
     */
    public function test_forum_get_potential_subscribers_empty() {
        $context = context_module::instance($this->cm->id);

        $subscribers = forum_get_potential_subscribers($context, 0, 'u.id, u.firstname', 'u.firstname, u.lastname');

        // No potential subscribers, because nobody enrolled in course, empty array.
        $this->assertTrue(is_array($subscribers));
        $this->assertEmpty($subscribers);
    }

    /**
     * Test forum_get_potential_subscribers_rs() with emply list.
     */
    public function test_forum_get_potential_subscribers_rs_empty() {
        $context = context_module::instance($this->cm->id);

        $rs = forum_get_potential_subscribers_rs($context, 0, 'u.id, u.firstname', 'u.firstname, u.lastname');
        // No potential subscribers, because nobody enrolled in course.
        $this->assertFalse($rs->valid());
        $rs->close();
    }

    /**
     * Test forum_get_potential_subscribers() with users enrolled
     * in course.
     */
    public function test_forum_get_potential_subscribers_three() {
        $context = context_module::instance($this->cm->id);

        // Enrol three users to our course.
        $enrolledusers = array();
        for ($i = 0; $i < 3; $i++) {
            $u = $this->users[$i];
            $enrolledusers[$u->id] = $u;
            $this->enrol_user($u->id);
        }

        // Get potential subscribers.
        $subscribers = forum_get_potential_subscribers($context, 0, 'u.id, u.firstname, u.lastname');
        $this->assertTrue(is_array($subscribers));

        // Count we now have the same number of potential subscribers as we enrolled.
        $this->assertEquals(count($subscribers), count($enrolledusers));

        foreach ($subscribers as $potsub) {
            // Verify that we are being returned the users we subscribed.
            $this->assertArrayHasKey($potsub->id, $enrolledusers);

            // Check that the firstname matches.
            $this->assertEquals($enrolledusers[$potsub->id]->firstname, $potsub->firstname);
            // Check that the lastname matches.
            $this->assertEquals($enrolledusers[$potsub->id]->lastname, $potsub->lastname);
        }

    }

    /**
     * Test forum_get_potential_subscribers_rs() with users enrolled
     * in course.
     */
    public function test_forum_get_potential_subscribers_rs_three() {
        $context = context_module::instance($this->cm->id);

        // Enrol three users to our course.
        $enrolledusers = array();
        for ($i = 0; $i < 3; $i++) {
            $u = $this->users[$i];
            $enrolledusers[$u->id] = $u;
            $this->enrol_user($u->id);
        }

        // Get potential subscribers.
        $rs = forum_get_potential_subscribers_rs($context, 0, 'u.id, u.firstname, u.lastname');

        $count = 0;
        foreach ($rs as $potsub) {
            $count++;
            // Verify that we are being returned the users we subscribed.
            $this->assertArrayHasKey($potsub->id, $enrolledusers);

            // Check that the firstname matches.
            $this->assertEquals($enrolledusers[$potsub->id]->firstname, $potsub->firstname);

            // Check that the lastname matches.
            $this->assertEquals($enrolledusers[$potsub->id]->lastname, $potsub->lastname);
        }
        $rs->close();

        // Count we now have the same number of potential subscribers as we enrolled.
        $this->assertEquals($count, count($enrolledusers));
        $rs->close();
    }

    /**
     * Test forum_forum_subscribed_users()
     */
    public function test_forum_subscribed_users() {

        // Enrol three users to our course.
        $enrolledusers = array();
        for ($i = 0; $i < 3; $i++) {
            $u = $this->users[$i];
            $enrolledusers[$u->id] = $u;
            $this->enrol_user($u->id);
        }

        $subscribers = forum_subscribed_users($this->course, $this->forum);

        // No subscribed users at the moment..
        $this->assertEmpty($subscribers);

        // Subscribe one of our users!
        $subscriber = array_pop($enrolledusers);
        forum_subscribe($subscriber->id, $this->forum->id);

        // Get subscribed users again..
        $subscribers= forum_subscribed_users($this->course, $this->forum);

        $this->assertTrue(is_array($subscribers));
        // Verify only the one user found..
        $this->assertEquals(1, count($subscribers));

        // Verify that the array is indexed by users ID.
        $this->assertArrayHasKey($subscriber->id, $subscribers);

        // Verify that the firstname/lastnames match.
        $this->assertEquals($subscriber->firstname, $subscribers[$subscriber->id]->firstname);
        $this->assertEquals($subscriber->lastname, $subscribers[$subscriber->id]->lastname);
    }

    /**
     * Test forum_forum_subscribed_users_rs()
     */
    public function test_forum_subscribed_users_rs() {

        // Enrol three users to our course.
        $enrolledusers = array();
        for ($i = 0; $i < 3; $i++) {
            $u = $this->users[$i];
            $enrolledusers[$u->id] = $u;
            $this->enrol_user($u->id);
        }

        $rs = forum_subscribed_users_rs($this->course, $this->forum);

        // No subscribed users at the moment..
        $this->assertFalse($rs->valid());
        $rs->close();

        // Subscribe one of our users!
        $subscriber = array_pop($enrolledusers);
        forum_subscribe($subscriber->id, $this->forum->id);

        // Get subscribed users again..
        $rs = forum_subscribed_users_rs($this->course, $this->forum);
        $count = 0;
        foreach ($rs as $potsub) {
            // Only one user should be found..
            $this->assertEquals($count, 0);
            $this->assertEquals($subscriber->id, $potsub->id);
            $count++;
        }
        // Verify only the one user found..
        $this->assertEquals($count, 1);
        $rs->close();
    }
}
