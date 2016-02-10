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
 * URL resource searcher
 *
 * @package    mod_forum
 * @copyright  2016 Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_url\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Class indexer
 * @package mod_url\search
 */
class indexer extends \core_search\base_mod {

    /**
     * Returns recordset containing required data for indexing url posts.
     *
     * @param int $modifiedfrom timestamp
     * @return \moodle_recordset
     */
    public function get_recordset($modifiedfrom = 0) {
        global $DB;

        return $DB->get_recordset_select('url', 'timemodified >= ?', array($modifiedfrom));
    }

    /**
     * Returns the mod_url associated with this  id.
     *
     * @param \stdClass $url URL record.
     * @return \core_search\document
     */
    public function get_document($url) {

        $cm = $this->get_cm('url', $url->id, $url->course);
        $context = \context_module::instance($cm->id);

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($url->id, $this->componentname);
        $doc->set('title', $url->name);
        $doc->set('content', $url->intro);
        $doc->set('contentformat', $url->introformat);
        $doc->set('contextid', $context->id);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', $url->course);
        $doc->set('modified', $url->timemodified);

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @throws \dml_missing_record_exception
     * @throws \dml_exception
     * @param int $id URL instance id
     * @return bool
     */
    public function check_access($id) {
        global $DB;

        // TODO: URM isn't this expensive?
        $url = $DB->get_record('url', array('id' => $id));
        try {
            $cminfo = $this->get_cm('url', $url->id, $url->course);
            $cminfo->get_course_module_record();
        } catch (\dml_missing_record_exception $ex) {
            return \core_search\manager::ACCESS_DELETED;
        } catch (\dml_exception $ex) {
            return \core_search\manager::ACCESS_DENIED;
        }

        // Recheck uservisible although it should have already been checked in core_search.
        if ($cminfo->uservisible === false) {
            return \core_search\manager::ACCESS_DENIED;
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to the mod_url instance.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {;
        return $this->get_context_url($doc);
    }

    /**
     *  Link to the mod_url instance.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return new \moodle_url('/mod/url/view.php', array('u' => $doc->get('itemid')));
    }
}
