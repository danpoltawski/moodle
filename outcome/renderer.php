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
 * Outcome Renderer
 *
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_outcome\coverage\coverage_interface;
use core_outcome\model\outcome_model;
use core_outcome\output\flash_messages;
use core_outcome\table\course_coverage_table;
use core_outcome\table\course_outcome_sets_table;
use core_outcome\table\course_performance_table;
use core_outcome\table\manage_outcome_sets_table;
use core_outcome\table\marking_table;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   core_outcome
 * @category  outcome
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_outcome_renderer extends plugin_renderer_base {
    /**
     * Allow this renderer to render widgets that use namespaces
     *
     * @param renderable $widget
     * @return string
     */
    public function render(renderable $widget) {
        $rendermethod = 'render_'.str_replace('\\', '_', get_class($widget));
        if (method_exists($this, $rendermethod)) {
            return $this->$rendermethod($widget);
        }
        return parent::render($widget);
    }

    /**
     * Get outcome description with doc num pre-appended if available.
     *
     * @param outcome_model $outcome
     * @param string|null $tag Optionally wrap the description with this tag
     * @return string
     */
    public function outcome_display(outcome_model $outcome, $tag = null) {
        $output = format_text($outcome->description, FORMAT_MOODLE, array(
            'para' => false,
        ));
        if (!empty($outcome->docnum)) {
            $output = format_string($outcome->docnum) . ' - ' . $output;
        }
        if (!empty($tag)) {
            return html_writer::tag($tag, $output, array('class' => 'outcome-description'));
        }
        return $output;
    }

    /**
     * Helper method to sort an array of cm_info classes
     *
     * @param cm_info $a
     * @param cm_info $b
     * @return int
     */
    protected function sort_cms(cm_info $a, cm_info $b) {
        return strnatcasecmp($a->name, $b->name);
    }

    /**
     * Helper method to sort an array of html_table_row classes
     * based on content of first cell
     *
     * @param html_table_row $a
     * @param html_table_row $b
     * @return int
     */
    protected function sort_html_rows(html_table_row $a, html_table_row $b) {
        return strnatcasecmp($a->cells[0]->text, $b->cells[0]->text);
    }

    /**
     * Render the administration of outcome sets
     *
     * @param manage_outcome_sets_table $table
     */
    public function outcome_sets_admin(manage_outcome_sets_table $table) {
        global $PAGE;

        $editurl = $PAGE->url;
        $editurl->param('action', 'outcomeset_edit');

        echo html_writer::start_tag('div', array('class' => 'outcome-set-actions'));
        echo html_writer::link($editurl, get_string('addnewoutcomeset', 'outcome'));

        if (has_capability('moodle/outcome:import', $PAGE->context)) {
            $importurl = $PAGE->url;
            $importurl->param('action', 'outcomeset_import');

            echo html_writer::link($importurl, get_string('importoutcomeset', 'outcome'));
        }
        echo html_writer::end_tag('div');

        $table->out(50, false);
    }

    /**
     * Render course outcome sets
     *
     * @param course_outcome_sets_table $table
     */
    public function course_outcome_sets(course_outcome_sets_table $table) {
        $table->out(50, false);
    }

    /**
     * Render outcome marking table
     *
     * @param moodleform $mform
     * @param marking_table $table
     */
    public function outcome_marking(moodleform $mform, marking_table $table) {
        $mform->display();
        $table->out(50, false);
    }

    /**
     * Render outcome course performance table
     *
     * @param moodleform $mform
     * @param course_performance_table $table
     */
    public function outcome_course_performance(moodleform $mform, course_performance_table $table) {
        $mform->display();
        $table->out(50, false);
    }

    /**
     * Render outcome course coverage table
     *
     * @param moodleform $mform
     * @param course_coverage_table $table
     */
    public function outcome_course_coverage(moodleform $mform, course_coverage_table $table) {
        $mform->display();
        $table->out(50, false);
    }

    /**
     * @param coverage_interface $coverage
     */
    public function outcome_course_unmapped(coverage_interface $coverage) {
        $header = html_writer::tag('h3', $coverage->get_unmapped_content_header(),
                array('class' => 'outcome-unmapped-header'));
        $htmltable = $coverage->get_unmapped_content();
        if (!($htmltable instanceof html_table) or empty($htmltable->data)) {
            $output = html_writer::tag('h5', get_string('nothingtodisplay'));
        } else {
            $output  = html_writer::table($htmltable);
        }

        echo html_writer::tag('div', $header . $output, array('class' => 'outcome-unmapped-report'));
    }

    /**
     * @param object[] $courses
     * @return string
     */
    public function mapped_courses_list($courses) {
        $items = array();
        foreach ($courses as $course) {
            $url   = new moodle_url('/course/view.php', array('id' => $course->id));
            $title = get_string('viewcoursex', 'outcome', format_string($course->fullname));
            $text  = '('.format_string($course->shortname).') '.format_string($course->fullname);

            $items[] = html_writer::link($url, $text, array('title' => $title));
        }
        return html_writer::alist($items);
    }

    /**
     * @param object $user
     * @param outcome_model $outcome
     * @param SplObjectStorage $activities
     * @return string
     */
    public function user_activity_completion($user, outcome_model $outcome, SplObjectStorage $activities) {
        $output = $this->outcome_display($outcome, 'p');

        if ($activities->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->head  = array(get_string('activity'), get_string('type', 'outcome'), get_string('completion', 'outcome'));
            $table->align = array(null, null, 'center');
            $table->attributes['class'] = 'generaltable outcome-user-activity-completion';

            $activities->rewind();
            while ($activities->valid()) {
                /** @var $activity cm_info */
                $activity = $activities->current();
                $progress = $activities->getInfo();

                $table->data[] = new html_table_row(array(
                    format_string($activity->name),
                    $activity->get_module_type_name(),
                    $this->activity_completion_icon($user, $activity, $progress),
                ));

                $activities->next();
            }
            usort($table->data, array($this, 'sort_html_rows'));
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param outcome_model $outcome
     * @param cm_info[] $content
     * @return string
     */
    public function performance_associated_content(outcome_model $outcome, $content) {
        $output = $this->outcome_display($outcome, 'p');

        if (empty($content)) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->head  = array(get_string('content', 'outcome'));
            $table->attributes['class'] = 'generaltable outcome-course-associated-content';

            usort($content, array($this, 'sort_cms'));

            foreach ($content as $cminfo) {
                $table->data[] = new html_table_row(array(format_string($cminfo->name)));
            }
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param outcome_model $outcome
     * @param SplObjectStorage $attempts
     * @return string
     */
    public function course_activity_attempt_grades(outcome_model $outcome, SplObjectStorage $attempts) {
        /** @var \core_outcome\area\area_info_interface $areainfo */
        /** @var cm_info[] $cms */

        $output = $this->outcome_display($outcome, 'p');

        if ($attempts->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->data  = array();
            $table->head  = array(
                get_string('content', 'outcome'),
                get_string('type', 'outcome'),
                get_string('grade'),
                get_string('totalpoints', 'outcome'),
            );
            $table->attributes['class'] = 'generaltable outcome-course-activities-attempts';

            // We group by activity, so get all possible activities and sort them.
            $cms = array();
            $attemptsbycm = array();
            foreach ($attempts as $areainfo) {
                $cmid = $areainfo->get_cm()->id;
                $cms[$cmid] = $areainfo->get_cm();

                if (!isset($attemptsbycm[$cmid])) {
                    $attemptsbycm[$cmid] = array();
                }
                $attemptsbycm[$cmid][] = $areainfo;
            }
            usort($cms, array($this, 'sort_cms'));

            // Now add each activity and any associated attempts to the table.
            $rows = array();
            foreach ($cms as $cm) {
                // We always add activity row, regardless if there is grade info or not.
                $cmrow = new html_table_row(array(
                    format_string($cm->name),
                    $cm->get_module_type_name(),
                ));
                $cmrows = array();
                foreach ($attemptsbycm[$cm->id] as $areainfo) {
                    if ($areainfo->get_area()->area == 'mod') {
                        // Found the activity attempt, add attempt data to activity row.
                        $row = $cmrow;
                    } else {
                        $row = new html_table_row(array($areainfo->get_item_name(), $areainfo->get_area_name()));
                        $row->attributes['class'] = 'outcome-activity-content-attempt';
                        $cmrows[] = $row;
                    }
                    $gradeinfo = $attempts->offsetGet($areainfo);
                    $row->cells[] = new html_table_cell(round($gradeinfo['avegrade']).'%');
                    $row->cells[] = new html_table_cell(round($gradeinfo['points']).'/'.
                            round($gradeinfo['possiblepoints']));
                }
                usort($cmrows, array($this, 'sort_html_rows'));

                // Ensure the activity row has enough cells.
                $cmrow->cells = array_pad($cmrow->cells, 4, new html_table_cell('-'));
                array_unshift($cmrows, $cmrow);

                $rows = array_merge($rows, $cmrows);
            }
            $table->data = $rows;
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param outcome_model $outcome
     * @param SplObjectStorage $activities
     * @return string
     */
    public function course_activity_completion(outcome_model $outcome, SplObjectStorage $activities) {
        $output = $this->outcome_display($outcome, 'p');

        if ($activities->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->head  = array(get_string('activity'), get_string('type', 'outcome'), get_string('completion', 'outcome'));
            $table->align = array(null, null, 'center');
            $table->attributes['class'] = 'generaltable outcome-course-activity-completion';

            $activities->rewind();
            while ($activities->valid()) {
                /** @var $activity cm_info */
                $activity = $activities->current();
                $progress = $activities->getInfo();

                $completion = is_null($progress->completion) ? '-' : round($progress->completion).'%';
                $table->data[] = new html_table_row(array(
                    format_string($activity->name),
                    $activity->get_module_type_name(),
                    $completion,
                ));

                $activities->next();
            }
            usort($table->data, array($this, 'sort_html_rows'));
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param outcome_model $outcome
     * @param SplObjectStorage $activities
     * @return string
     */
    public function course_activity_scale_grades(outcome_model $outcome, SplObjectStorage $activities) {
        $output = $this->outcome_display($outcome, 'p');

        if ($activities->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->head  = array(get_string('activity'), get_string('type', 'outcome'), get_string('scalevalue', 'outcome'));
            $table->attributes['class'] = 'generaltable outcome-course-scales-grades';

            $activities->rewind();
            while ($activities->valid()) {
                /** @var $activity cm_info */
                $activity = $activities->current();
                /** @var grade_grade $grade */
                $grade = $activities->getInfo();

                $table->data[] = new html_table_row(array(
                    format_string($activity->name),
                    $activity->get_module_type_name(),
                    $grade,
                ));

                $activities->next();
            }
            usort($table->data, array($this, 'sort_html_rows'));
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param object $user
     * @param cm_info $activity
     * @param null|object $progress
     * @return string
     */
    public function activity_completion_icon($user, cm_info $activity, $progress = null) {
        // Get progress information and state.
        if (!empty($progress)) {
            $state = $progress->completionstate;
            $date  = userdate($progress->timemodified);
        } else {
            $state = COMPLETION_INCOMPLETE;
            $date = '';
        }
        // Work out how it corresponds to an icon.
        switch ($state) {
            case COMPLETION_COMPLETE:
                $completiontype = 'y';
                break;
            case COMPLETION_COMPLETE_PASS:
                $completiontype = 'pass';
                break;
            case COMPLETION_COMPLETE_FAIL:
                $completiontype = 'fail';
                break;
            case COMPLETION_INCOMPLETE:
            default:
                $completiontype = 'n';
        }

        $completionicon = 'completion-'.
            ($activity->completion == COMPLETION_TRACKING_AUTOMATIC ? 'auto' : 'manual').
            '-'.$completiontype;

        $modcontext   = context_module::instance($activity->id);
        $describe     = get_string('completion-'.$completiontype, 'completion');
        $fulldescribe = get_string('progress-title', 'completion', array(
            'state'    => $describe,
            'date'     => $date,
            'user'     => fullname($user),
            'activity' => format_string($activity->name, true, array('context' => $modcontext)),
        ));

        return $this->output->pix_icon('i/'.$completionicon, $describe, 'moodle', array('title' => $fulldescribe));
    }

    /**
     * @param outcome_model $outcome
     * @param SplObjectStorage $activities
     * @return string
     */
    public function user_activity_scale_grades(outcome_model $outcome, SplObjectStorage $activities) {
        $output = $this->outcome_display($outcome, 'p');

        if ($activities->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->head  = array(get_string('activity'), get_string('type', 'outcome'), get_string('scalevalue', 'outcome'));
            $table->attributes['class'] = 'generaltable outcome-user-scales-completion';

            $activities->rewind();
            while ($activities->valid()) {
                /** @var $activity cm_info */
                $activity = $activities->current();
                /** @var grade_grade $grade */
                $grade = $activities->getInfo();

                $table->data[] = new html_table_row(array(
                    format_string($activity->name),
                    $activity->get_module_type_name(),
                    grade_format_gradevalue($grade->finalgrade, $grade->grade_item),
                ));

                $activities->next();
            }
            usort($table->data, array($this, 'sort_html_rows'));
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param outcome_model $outcome
     * @param SplObjectStorage $attempts
     * @return string
     */
    public function user_activity_attempt_grades(outcome_model $outcome, SplObjectStorage $attempts) {
        /** @var \core_outcome\area\area_info_interface $areainfo */
        /** @var cm_info[] $cms */

        $output = $this->outcome_display($outcome, 'p');

        if ($attempts->count() == 0) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->data  = array();
            $table->head  = array(
                get_string('content', 'outcome'),
                get_string('type', 'outcome'),
                get_string('grade'),
                get_string('pointvalue', 'outcome'),
            );
            $table->attributes['class'] = 'generaltable outcome-user-scales-completion';

            // We group by activity, so get all possible activities and sort them.
            $cms = array();
            foreach ($attempts as $areainfo) {
                $cms[$areainfo->get_cm()->id] = $areainfo->get_cm();
            }
            usort($cms, array($this, 'sort_cms'));

            // Now add each activity and any associated attempts to the table.
            foreach ($cms as $cm) {
                $table->data = array_merge($table->data, $this->activity_attempts_to_rows($cm, $attempts));
            }
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * Given an activity, find all attempts and build html_table_row instances
     *
     * @param cm_info $cm
     * @param SplObjectStorage $attempts
     * @return html_table_row[]
     */
    protected function activity_attempts_to_rows(cm_info $cm, SplObjectStorage $attempts) {
        $rows = array();

        // We always add activity row, regardless if there is grade info or not.
        $cmrow = new html_table_row(array(
            format_string($cm->name),
            $cm->get_module_type_name(),
        ));
        $cmrow->attributes['class'] = 'outcome-activity-attempt';

        $attempts->rewind();
        while ($attempts->valid()) {
            /** @var \core_outcome\area\area_info_interface $areainfo */
            $areainfo = $attempts->current();
            /** @var \core_outcome\model\attempt_model $attempt */
            $attempt = $attempts->getInfo();

            if ($areainfo->get_cm()->id == $cm->id) {
                if ($areainfo->get_area()->area == 'mod') {
                    // Found the activity attempt, add attempt data to activity row.
                    $row = $cmrow;
                } else {
                    $row = new html_table_row(array($areainfo->get_item_name(), $areainfo->get_area_name()));
                    $row->attributes['class'] = 'outcome-activity-content-attempt';
                    $rows[] = $row;
                }
                $row->cells[] = new html_table_cell(round($attempt->percentgrade).'%');
                $row->cells[] = new html_table_cell(round($attempt->rawgrade - $attempt->mingrade).'/'.
                    round($attempt->maxgrade - $attempt->mingrade));
            }
            $attempts->next();
        }
        // Sort all of the rows underneath the activity.
        usort($rows, array($this, 'sort_html_rows'));

        // Ensure the activity row has enough cells.
        $cmrow->cells = array_pad($cmrow->cells, 4, new html_table_cell('-'));

        // Add the activity row to the front.
        array_unshift($rows, $cmrow);

        return $rows;
    }

    /**
     * @param outcome_model $outcome
     * @param \core_outcome\area\area_info_interface[] $activities
     * @return string
     */
    public function coverage_activities(outcome_model $outcome, $activities) {
        $output = $this->outcome_display($outcome, 'p');

        if (empty($activities)) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->data  = array();
            $table->head  = array(
                get_string('content', 'outcome'),
                get_string('type', 'outcome'),
            );
            $table->attributes['class'] = 'generaltable outcome-coverage-activities';

            // We group by activity, so get all possible activities and sort them.
            /** @var cm_info[] $cms */
            $cms = array();
            $areasbycm = array();
            foreach ($activities as $areainfo) {
                $cminfo = $areainfo->get_cm();
                $cms[$cminfo->id] = $cminfo;

                if (!isset($areasbycm[$cminfo->id])) {
                    $areasbycm[$cminfo->id] = array();
                }
                $areasbycm[$cminfo->id][] = $areainfo;
            }
            usort($cms, array($this, 'sort_cms'));

            // Now add each activity and any associated attempts to the table.
            foreach ($cms as $cm) {
                $cmrow = new html_table_row(array(
                    format_string($cm->name),
                    $cm->get_module_type_name(),
                ));

                $rows = array();
                foreach ($areasbycm[$cm->id] as $areainfo) {
                    /** @var \core_outcome\area\area_info_interface $areainfo */
                    $modareaused = false;
                    if ($areainfo->get_area()->area == 'mod') {
                        $modareaused = true;
                        continue;
                    } else {
                        $row = new html_table_row(array($areainfo->get_item_name(), $areainfo->get_area_name()));
                        $row->attributes['class'] = 'outcome-sub-content';
                        $rows[] = $row;
                    }
                }

                if (empty($modareaused)) {
                    $cmrow->attributes['class'] = 'outcome-coverage-implicit';
                } else {
                    $cmrow->attributes['class'] = 'outcome-coverage-explicit';
                }

                usort($rows, array($this, 'sort_html_rows'));
                array_unshift($rows, $cmrow);

                $table->data = array_merge($table->data, $rows);
            }
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * @param outcome_model $outcome
     * @param SplObjectStorage $questions
     * @return string
     */
    public function coverage_questions(outcome_model $outcome, $questions) {
        $output = $this->outcome_display($outcome, 'p');

        if (empty($questions)) {
            $output .= $this->output->heading(get_string('nothingtodisplay'), 5);
        } else {
            $table        = new html_table();
            $table->data  = array();
            $table->head  = array(
                get_string('content', 'outcome'),
                get_string('type', 'outcome'),
            );
            $table->attributes['class'] = 'generaltable outcome-coverage-questions';

            $questions->rewind();
            while ($questions->valid()) {
                /** @var $areainfo \core_outcome\area\area_info_interface */
                $areainfo = $questions->current();

                $qbankonlystr = '';
                if (!$questions->getInfo()) {
                    $qbankonlystr = '*';
                }
                $table->data[] = new html_table_row(array($areainfo->get_item_name().$qbankonlystr,
                    get_string('pluginname', $areainfo->get_area()->component)));

                $questions->next();
            }
            usort($table->data, array($this, 'sort_html_rows'));
            $output .= html_writer::table($table);
        }

        return $output;
    }

    /**
     * Render flash messages
     *
     * @param flash_messages $flashmessages
     * @return string
     */
    public function render_core_outcome_output_flash_messages(flash_messages $flashmessages) {
        $output = '';
        foreach ($flashmessages->get_messages(flash_messages::GOOD) as $message) {
            $output .= $this->output->notification($message, 'notifysuccess');
        }
        foreach ($flashmessages->get_messages(flash_messages::BAD) as $message) {
            $output .= $this->output->notification($message);
        }
        $flashmessages->clear_all_messages();

        return $output;
    }
}