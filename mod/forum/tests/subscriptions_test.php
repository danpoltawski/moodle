<?php

defined('MOODLE_INTERNAL') || die();

class mod_forum_subscriptions_testcase extends advanced_testcase {
    private $users = array();
    private $course = null;
    private $forum = null;
    private $cm = null;
    private $enrolplugin = null;
    private $enrolinstance = null;

    public function setUp() {
        $this->resetAfterTest(true);

        // create 10 users.
        for($i = 0; $i < 9; $i++) {
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

    private function enrol_user($userid) {
        $this->enrolplugin->enrol_user($this->enrolinstance, $userid);
    }

    public function test_forum_get_potential_subscribers_rs_empty() {
        $context = context_module::instance($this->cm->id);

        $rs = forum_get_potential_subscribers_rs($context, 0, 'u.id, u.firstname', 'u.firstname, u.lastname');
        // No potential subscribers, because nobody enrolled in course.
        $this->assertFalse($rs->valid());
        $rs->close();
    }

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
        foreach($rs as $potsub) {
            $count++;
            // Verify that we are being returned the users we subscribed.
            $this->assertArrayHasKey($potsub->id, $enrolledusers);

            // Check that the firstname matches
            $this->assertEquals($enrolledusers[$potsub->id]->firstname, $potsub->firstname);

            // Check that the lastname matches
            $this->assertEquals($enrolledusers[$potsub->id]->lastname, $potsub->lastname);
        }
        $rs->close();

        $enrolledcount = count($enrolledusers);
        // Count we now have the same number of potential subscribers as we enrolled.
        $this->assertEquals($count, count($enrolledusers));
        $rs->close();
    }
}

        /*
        // Subscribe one of our users!
        $subscriber = array_pop($enrolledusers);
        forum_subscribe($subscriber->id, $this->forum->id);

        // Get potential subscribers.
        $rs = forum_get_potential_subscribers_rs($context, 0, 'u.id, u.firstname, u.lastname');

        $count = 0;
        foreach($rs as $potsub) {
            // Verify that the user we subscribed is no longer  'potential subscriber'.
            $this->assertNotEquals($subscriber->id, $potsub->id);
            $count++;
        }
        $this->assertEquals($count, ($enrolledcount-1));
        $rs->close();
         */
