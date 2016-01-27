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
 * Forum search component.
 *
 * @package    mod_forum
 * @copyright  2015 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\search;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum search component.
 *
 * @package    mod_forum
 * @copyright  2015 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class indexer extends \core_search\base_mod {

    /**
     * @var string Forum posts filearea.
     */
    protected static $filearea = 'post';

    /**
     * @var array Internal quick static cache.
     */
    protected $forumsdata = array();

    /**
     * @var array Internal quick static cache.
     */
    protected $discussionsdata = array();

    /**
     * @var array Internal quick static cache.
     */
    protected $postsdata = array();

    /**
     * Returns recordset containing required data for indexing forum posts.
     *
     * @param int $modifiedfrom timestamp
     * @return moodle_recordset
     */
    public function get_recordset($modifiedfrom = 0) {
        global $DB;

        $sql = "SELECT fp.*, f.id AS forumid, f.course AS courseid, f.name AS forumname, f.intro AS forumintro, " .
                "f.introformat AS forumintroformat FROM {forum_posts} fp " .
                 "JOIN {forum_discussions} fd ON fd.id = fp.discussion " .
                 "JOIN {forum} f ON f.id = fd.forum " .
                "WHERE fp.modified >= ? ORDER BY fp.modified ASC";
        return $DB->get_recordset_sql($sql, array($modifiedfrom));
    }

    /**
     * Returns the documents associated with this post id.
     *
     * @param stdClass $post Post info.
     * @return \core_search\document
     */
    public function get_document($post) {

        try {
            $cm = $this->get_cm('forum', $post->forumid, $post->courseid);
            $context = \context_module::instance($cm->id);
            $user = self::get_user_names_data($post->userid);
        } catch (\dml_missing_record_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving mod_forum ' . $post->id . ' document, not all required data is available: ' .
                $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\dml_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving mod_forum' . $post->id . ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($post->id, $this->componentname);
        $doc->set('title', $post->subject);
        $doc->set('content', $post->message);
        $doc->set('contentformat', $post->messageformat);
        $doc->set('userfullname', fullname($user));
        $doc->set('contextid', $context->id);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', $post->courseid);
        $doc->set('userid', $user->id);
        $doc->set('created', $post->created);
        $doc->set('modified', $post->modified);
        $doc->set('name', $post->forumname);
        $doc->set('intro', $post->forumintro);
        $doc->set('introformat', $post->forumintroformat);

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @throws \dml_missing_record_exception
     * @throws \dml_exception
     * @param int $id Forum post id
     * @return bool
     */
    public function check_access($id) {
        global $USER;

        try {
            $post = $this->get_post($id);
            $forum = $this->get_forum($post->forum);
            $discussion = $this->get_discussion($post->discussion);
            $cminfo = $this->get_cm('forum', $forum->id, $forum->course);
            $cm = $cminfo->get_course_module_record();
        } catch (\dml_missing_record_exception $ex) {
            return \core_search\manager::ACCESS_DELETED;
        } catch (\dml_exception $ex) {
            return \core_search\manager::ACCESS_DENIED;
        }

        // Recheck uservisible although it should have already been checked in core_search.
        if ($cminfo->uservisible === false) {
            return \core_search\manager::ACCESS_DENIED;
        }

        if (!forum_user_can_see_post($forum, $discussion, $post, $USER, $cm)) {
            return \core_search\manager::ACCESS_DENIED;
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to the forum post discussion
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        // The post is already in static cache, we fetch it in self::search_access.
        $post = $this->get_post($doc->get('itemid'));
        return new \moodle_url('/mod/forum/discuss.php', array('d' => $post->discussion));
    }

    /**
     * Link to the forum.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        $contextmodule = \context::instance_by_id($doc->get('contextid'));
        return new \moodle_url('/mod/forum/view.php', array('id' => $contextmodule->instanceid));
    }

    /**
     * Returns the specified forum post from its internal cache.
     *
     * @throws \dml_missing_record_exception
     * @param int $postid
     * @return stdClass
     */
    protected function get_post($postid) {
        if (empty($this->postsdata[$postid])) {
            $this->postsdata[$postid] = forum_get_post_full($postid);
            if (!$this->postsdata[$postid]) {
                throw new \dml_missing_record_exception('forum_posts');
            }
        }
        return $this->postsdata[$postid];
    }

    /**
     * Returns the specified forum checking the internal cache.
     *
     * Store minimal information as this might grow.
     *
     * @throws \dml_exception
     * @param int $forumid
     * @return stdClass
     */
    protected function get_forum($forumid) {
        global $DB;

        if (empty($this->forumsdata[$forumid])) {
            $this->forumsdata[$forumid] = $DB->get_record('forum', array('id' => $forumid), '*', MUST_EXIST);
        }
        return $this->forumsdata[$forumid];
    }

    /**
     * Returns the discussion checking the internal cache.
     *
     * @throws \dml_missing_record_exception
     * @param int $discussionid
     * @return stdClass
     */
    protected function get_discussion($discussionid) {
        global $DB;

        if (empty($this->discussionsdata[$discussionid])) {
            $this->discussionsdata[$discussionid] = $DB->get_record('forum_discussions',
                array('id' => $discussionid), '*', MUST_EXIST);
        }
        return $this->discussionsdata[$discussionid];
    }
}
