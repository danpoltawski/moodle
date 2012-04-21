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

defined('MOODLE_INTERNAL') || die();


class format_topics_renderer extends plugin_renderer_base {

    public function format_collapsed_requested($course, $sections, course_section_controller $controller, $requested) {
        global $PAGE;

        $o = '';
        $o = $this->format_header($course);
        $o.= html_writer::start_tag('ul', array('class' => 'topics'));

        foreach($sections as $section) {
            if ($section->section == $requested || $PAGE->user_is_editing()) {
                $o.= $this->section($course, $section, $controller);
            }else if($section->section == 0) {
                $o.= $this->section($course, $section, $controller);
            } else {
                $o.= $this->section_header($course, $section);
            }
        }

        $o .= html_writer::end_tag('ul');

        return $o;
    }

    public function format_collapsed($course, $sections, course_section_controller $controller) {
        global $PAGE;

        $o = '';
        $o = $this->format_header($course);

        $o.= html_writer::start_tag('ul', array('class' => 'topics'));

        foreach($sections as $section) {
            if ($section->current || $PAGE->user_is_editing()) {
                $o.= $this->section($course, $section, $controller);
            }else if($section->section == 0) {
                $o.= $this->section($course, $section, $controller);
            } else {
                $o.= $this->section_header($course, $section);
            }
        }

        $o .= html_writer::end_tag('ul');

        return $o;
    }

    public function format($course, $sections, course_section_controller $controller) {
        $o = '';

        $o = $this->format_header($course);

        $o .= html_writer::start_tag('ul', array('class' => 'topics'));
        foreach($sections as $section) {
            $section->current = false;
            if ($section->section == $course->marker) {
                $section->current = true;
            }
            $o.= $this->section($course, $section, $controller);
        }

        $o .= html_writer::end_tag('ul');

        return $o;
    }

    public function format_header($course) {
        return $this->output->heading(get_string('topicoutline'), 2, 'headingblock header outline');
    }

    public function section_header($course, $section, $open = false) {
        $name = $section->name;

        if($section->section ==0){
            return $this->output->heading('General', 4);
        }

        if (empty($name)){
            //TODO: fixme
            $name = 'Topic '.$section->section;
        }

        if ($open) {
            $icon = $this->output->pix_icon('t/expanded', get_string('clicktohideshow'));
        }else{
            $icon = $this->output->pix_icon('t/collapsed', get_string('clicktohideshow'));
        }


        $url = new moodle_url('/course/view.php', array('id' => $course->id, 'section' =>$section->section));
        $title = $icon . $this->output->spacer() . $name;

        return $this->output->heading(html_writer::link($url, $title), 4, 'header outline');
    }

    public function section($course, $section, course_section_controller $controller) {
        $o = '';

        $o.= $this->section_header($course, $section, true);

        $style = 'section main clearfix';
        if (!$section->visible) {
            $style.= ' hiden';
        } else if ($section->current) {
            $style.= ' current';
        }
        
        $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section, 'class' => $style));

        $o.= $this->left_side($section);
        $o.= $this->right_side($section);

        $o.= html_writer::start_tag('div', array('class'=>'content'));

        if ($section->name) {
            $o .= $this->section_name($section->name);
        }

        $o.= $this->section_summary($section->summary);

        $o.= $this->section_contents($section, $controller);

        $o.= html_writer::end_tag('div');
        $o.= html_writer::end_tag('li');

        return $o;
    }

    public function section_contents($section, $controller) {
        return $controller->get_section($section);
    }

    public function section_summary($summary) {
        return html_writer::tag('div', $summary, array('class' => 'summary'));
    }

    public function section_name($sectionname) {
        return $this->output->heading($sectionname);
    }

    public function right_side($section) {
        $o = '';
        $o.= html_writer::start_tag('div', array('class' => 'right side'));
        $o.= '&nbsp;&nbsp;';
        $o.= html_writer::end_tag('div');

        return $o;
    }

    public function left_side($section) {
        $o = '';
        $o.= html_writer::start_tag('div', array('class' => 'left side'));
        $o.= '&nbsp;&nbsp;';
        $o.= html_writer::end_tag('div');

        return $o;
    }

}
