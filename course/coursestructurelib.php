<?php
defined('MOODLE_INTERNAL') || die;

class course_structure {
    var $course = null;
    var $modinfo = null;
    var $sections = array();
    var $mods = array(); // course modules indexed by id
    var $modnames = array();    // all course module names (except resource!)
    var $modnamesplural = array();    // all course module names (plural form)
    var $modnamesused  = array();    // course module names used

    public function __construct($course, $editing = false) {
        $this->course = $course;
        $this->modinfo = get_fast_modinfo($course);
        $this->get_mods();
        $this->get_sections();
    }

    // this function replaces get_all_mods
    private function get_mods() {
        global $DB, $CFG;

        $allmods = $DB->get_records('modules', array('visible' => 1));
        foreach ($allmods as $mod) {
            if (!file_exists("$CFG->dirroot/mod/$mod->name/lib.php")) {
                continue;
            }

            $this->modnames[$mod->name] = get_string('modulename', $mod->name);
            $this->modnamesplural[$mod->name] = get_string('modulenameplural', $mod->name);
        }
        collatorlib::asort($this->modnames);

        if ($rawmods = $this->modinfo->cms) {
            foreach($rawmods as $mod) {    // Index the mods
                if (empty($this->modnames[$mod->modname])) {
                    continue;
                }
                $this->mods[$mod->id] = $mod;
                $this->mods[$mod->id]->modfullname = $this->modnames[$mod->modname];
                if (!$mod->visible and !has_capability('moodle/course:viewhiddenactivities', context_course::instance($courseid))) {
                    continue;
                }
                // Check groupings
                if (!groups_course_module_visible($mod)) {
                    continue;
                }
                $this->modnamesused[$mod->modname] = $this->modnames[$mod->modname];
            }
            collatorlib::asort($this->modnamesused);
        }
    }

    // this insertion replaces what was previously done in coruse formats
    public function get_sections() {
        global $DB;

        $sections = $DB->get_records('course_sections', array('course'=>$this->course->id),
            'section', 'section, id, course, name, summary, summaryformat, sequence, visible');

        for ($i=0; $i <= $this->course->numsections; $i++) {
            if (empty($sections[$i])) {
                $thissection = new stdClass;
                $thissection->course  = $this->course->id;   // Create a new section structure
                $thissection->section = $i;
                $thissection->name    = null;
                $thissection->summary  = '';
                $thissection->summaryformat = FORMAT_HTML;
                $thissection->visible  = 1;
                $thissection->id = $DB->insert_record('course_sections', $thissection);

                $sections[$i] = $thissection;
            }

            $sections[$i]->current = false;
            if (!empty($this->course->marker) && ($i == $this->course->marker)) {
                $sections[$i]->current = true;
            }

            $this->sections[$i] = $sections[$i];
        }
    }
}

class course_section_controller {

    var $course = null;
    var $mods = null;
    var $groupbuttons = false;
    var $groupbuttonslink = false;
    var $isediting = false;
    var $ismoving = false;
    var $modinfo = null;
    var $completioninfo = null;
    var $modnames = null;
    var $_modulenames = array();

    /**
     * @param $course course in question
     * @param $editing TODO: $PAGE->user_is_editing()
     */

    public function __construct($course, $editing = false) {
        $this->course = $course;
        if ($this->course->groupmode) {
            $this->groupbuttons = true;
        }

        if (!$this->course->groupmodeforce) {
            $this->groupbuttons = true;
            $this->groupbuttonslink = true;
        }

        $this->isediting = $editing;
        $this->ismoving = $this->isediting && ismoving($this->course->id);
        $this->modinfo = get_fast_modinfo($this->course);
        $this->completioninfo = new completion_info($this->course);

        get_all_mods($this->course->id, $mods, $modnames, $modnamespluaral, $modnamesused);
        $this->mods = $mods;
        $this->modnames = $modnames;
    }

    private function cm_is_moving($cmid) {
        global $USER;

        return $this->ismoving && ($cmid === $USER->activitycopy);
    }

    public function get_section($section, $hidecompletion = false) {
        global $CFG;

        if (empty($section->sequence) && !$this->ismoving) {
            return '';
        }

        $o =  html_writer::start_tag('ul', array('class'=> 'section img-text'));
        //FIXME: hack for unit tests..
        $o.="\n";

        $sectionmods = explode(",", $section->sequence);

        foreach ($sectionmods as $cmid) {
            if (empty($this->mods[$cmid])) {
                continue;
            }

            if (!$this->is_mod_visible($cmid)) {
                continue;
            }

            $mod = $this->mods[$cmid];

            if ($this->cm_is_moving($mod->id)) {
                continue;
            }


            $liclasses = array();
            $liclasses[] = 'activity';
            $liclasses[] = $mod->modname;
            $liclasses[] = 'modtype_'.$mod->modname;
            $extraclasses = $mod->get_extra_classes();
            if ($extraclasses) {
                $liclasses = array_merge($liclasses, explode(' ', $extraclasses));
            }
            $o.= html_writer::start_tag('li', array('class'=>join(' ', $liclasses), 'id'=>'module-'.$cmid));
            $o.= $this->mod_moving_html($mod);
            $o.= html_writer::start_tag('div', array('class'=> $this->mod_get_ident_class($mod)));

            // Get data about this course-module
            list($content, $instancename) =
                    get_print_section_cm_text($this->modinfo->cms[$cmid], $this->course);

            //Accessibility: for files get description via icon, this is very ugly hack!
            $altname = '';
            $altname = $mod->modfullname;
            if (!empty($customicon)) {
                $archetype = plugin_supports('mod', $mod->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                    $mimetype = mimeinfo_from_icon('type', $customicon);
                    $altname = get_mimetype_description($mimetype);
                }
            }
            // Avoid unnecessary duplication: if e.g. a forum name already
            // includes the word forum (or Forum, etc) then it is unhelpful
            // to include that in the accessible description that is added.
            if (false !== strpos(textlib::strtolower($instancename),
                    textlib::strtolower($altname))) {
                $altname = '';
            }
            // File type after name, for alphabetic lists (screen reader).
            if ($altname) {
                $altname = get_accesshide(' '.$altname);
            }

            // We may be displaying this just in order to show information
            // about visibility, without the actual link
            $contentpart = '';
            if ($mod->uservisible) {
                // Nope - in this case the link is fully working for user
                $linkclasses = '';
                $textclasses = '';
                if ($this->mod_is_dimmed($mod)) {
                    $linkclasses .= ' dimmed';
                    $textclasses .= ' dimmed_text';
                    $accesstext = '<span class="accesshide">'.
                        get_string('hiddenfromstudents').': </span>';
                } else {
                    $accesstext = '';
                }
                if ($linkclasses) {
                    $linkcss = 'class="' . trim($linkclasses) . '" ';
                } else {
                    $linkcss = '';
                }
                if ($textclasses) {
                    $textcss = 'class="' . trim($textclasses) . '" ';
                } else {
                    $textcss = '';
                }

                // Get on-click attribute value if specified
                $onclick = $mod->get_on_click();
                if ($onclick) {
                    $onclick = ' onclick="' . $onclick . '"';
                }

                if ($url = $mod->get_url()) {
                    // Display link itself
                    $o.= '<a ' . $linkcss . $mod->extra . $onclick .
                            ' href="' . $url . '"><img src="' . $mod->get_icon_url() .
                            '" class="activityicon" alt="' .
                            $this->modname($mod->modname). '" /> ' .
                            $accesstext . '<span class="instancename">' .
                            $instancename . $altname . '</span></a>';

                    // If specified, display extra content after link
                    if ($content) {
                        $contentpart = '<div class="' . trim('contentafterlink' . $textclasses) .
                                '">' . $content . '</div>';
                    }
                } else {
                    // No link, so display only content
                    $contentpart = '<div ' . $textcss . $mod->extra . '>' .
                            $accesstext . $content . '</div>';
                }

                if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', context_course::instance($this->course->id))) {
                    if (null === $this->groupings) {
                        $this->groupings = groups_get_all_groupings($this->course->id);
                    }
                    $o .=" <span class=\"groupinglabel\">(".format_string($this->groupings[$mod->groupingid]->name).')</span>';
                }
            } else {
                $textclasses = $extraclasses;
                $textclasses .= ' dimmed_text';
                if ($textclasses) {
                    $textcss = 'class="' . trim($textclasses) . '" ';
                } else {
                    $textcss = '';
                }
                $accesstext = '<span class="accesshide">' .
                        get_string('notavailableyet', 'condition') .
                        ': </span>';

                if ($url = $mod->get_url()) {
                    // Display greyed-out text of link
                    $o.= '<div ' . $textcss . $mod->extra .
                            ' >' . '<img src="' . $mod->get_icon_url() .
                            '" class="activityicon" alt="' .
                            $this->modname($mod->modname).
                            '" /> <span>'. $instancename . $altname .
                            '</span></div>';

                    // Do not display content after link when it is greyed out like this.
                } else {
                    // No link, so display only content (also greyed)
                    $contentpart = '<div ' . $textcss . $mod->extra . '>' .
                            $accesstext . $content . '</div>';
                }
            }

            // Module can put text after the link (e.g. forum unread)
            $o.= $mod->get_after_link();

            // If there is content but NO link (eg label), then display the
            // content here (BEFORE any icons). In this case cons must be
            // displayed after the content so that it makes more sense visually
            // and for accessibility reasons, e.g. if you have a one-line label
            // it should work similarly (at least in terms of ordering) to an
            // activity.
            if (empty($url)) {
                $o.= $contentpart;
            }


            if ($this->isediting) {
                if ($this->groupbuttons and plugin_supports('mod', $mod->modname, FEATURE_GROUPS, 0)) {
                    //todo check this!
                    if (! $mod->groupmodelink = $this->groupbuttonslink) {
                        $mod->groupmode = $course->groupmode;
                    }

                } else {
                    $mod->groupmode = false;
                }
                $o.= '&nbsp;&nbsp;';
                $o.= make_editing_buttons($mod, false, true, $mod->indent, $section->section);

                $o.= $mod->get_after_edit_icons();
            }

            // Completion
            $completion = $hidecompletion
                ? COMPLETION_TRACKING_NONE
                : $this->completioninfo->is_enabled($mod);
            if ($completion!=COMPLETION_TRACKING_NONE && isloggedin() &&
                !isguestuser() && $mod->uservisible) {
                $completiondata = $this->completioninfo->get_data($mod,true);
                $completionicon = '';
                if ($isediting) {
                    switch ($completion) {
                        case COMPLETION_TRACKING_MANUAL :
                            $completionicon = 'manual-enabled'; break;
                        case COMPLETION_TRACKING_AUTOMATIC :
                            $completionicon = 'auto-enabled'; break;
                        default: // wtf
                    }
                } else if ($completion==COMPLETION_TRACKING_MANUAL) {
                    switch($this->completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $completionicon = 'manual-n'; break;
                        case COMPLETION_COMPLETE:
                            $completionicon = 'manual-y'; break;
                    }
                } else { // Automatic
                    switch($this->completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $completionicon = 'auto-n'; break;
                        case COMPLETION_COMPLETE:
                            $completionicon = 'auto-y'; break;
                        case COMPLETION_COMPLETE_PASS:
                            $completionicon = 'auto-pass'; break;
                        case COMPLETION_COMPLETE_FAIL:
                            $completionicon = 'auto-fail'; break;
                    }
                }
                if ($completionicon) {
                    $imgsrc = $OUTPUT->pix_url('i/completion-'.$completionicon);
                    $imgalt = s(get_string('completion-alt-'.$completionicon, 'completion', $mod->name));
                    if ($completion == COMPLETION_TRACKING_MANUAL && !$isediting) {
                        $imgtitle = s(get_string('completion-title-'.$completionicon, 'completion', $mod->name));
                        $newstate =
                            $completiondata->completionstate==COMPLETION_COMPLETE
                            ? COMPLETION_INCOMPLETE
                            : COMPLETION_COMPLETE;
                        // In manual mode the icon is a toggle form...

                        // If this completion state is used by the
                        // conditional activities system, we need to turn
                        // off the JS.
                        if (!empty($CFG->enableavailability) &&
                            condition_info::completion_value_used_as_condition($course, $mod)) {
                            $extraclass = ' preventjs';
                        } else {
                            $extraclass = '';
                        }
                        $o.= "
<form class='togglecompletion$extraclass' method='post' action='".$CFG->wwwroot."/course/togglecompletion.php'><div>
<input type='hidden' name='id' value='{$mod->id}' />
<input type='hidden' name='modulename' value='".s($mod->name)."' />
<input type='hidden' name='sesskey' value='".sesskey()."' />
<input type='hidden' name='completionstate' value='$newstate' />
<input type='image' src='$imgsrc' alt='$imgalt' title='$imgtitle' />
</div></form>";
                    } else {
                        // In auto mode, or when editing, the icon is just an image
                        $o.= "<span class='autocompletion'>";
                        $o.= "<img src='$imgsrc' alt='$imgalt' title='$imgalt' /></span>";
                    }
                }
            }

            // If there is content AND a link, then display the content here
            // (AFTER any icons). Otherwise it was displayed before
            if (!empty($url)) {
                $o.= $contentpart;
            }

            // Show availability information (for someone who isn't allowed to
            // see the activity itself, or for staff)
            if (!$mod->uservisible) {
                $o.= '<div class="availabilityinfo">'.$mod->availableinfo.'</div>';
            } else if ($this->can_view_hidden($mod) && !empty($CFG->enableavailability)) {
                $ci = new condition_info($mod);
                $fullinfo = $ci->get_full_information();
                if($fullinfo) {
                    $o.= '<div class="availabilityinfo">'.get_string($mod->showavailability
                        ? 'userrestriction_visible'
                        : 'userrestriction_hidden','condition',
                        $fullinfo).'</div>';
                }
            }

            $o.= html_writer::end_tag('div');
            $o.= html_writer::end_tag('li')."\n";
        }


        if ($this->ismoving) {
            $o.= $this->get_moving_html($section->id);
        }

        $o.= html_writer::end_tag('ul');
        //FIXME: hack for unit tests..
        $o.= "<!--class='section'-->\n\n";

        return $o;
    }

    private function can_view_hidden($mod) {
        return has_capability('moodle/course:viewhiddenactivities', context_module::instance($mod->id));
    }

    private function mod_is_dimmed($mod) {
        // In some cases the activity is visible to user, but it is
        // dimmed. This is done if viewhiddenactivities is true and if:
        // 1. the activity is not visible, or
        // 2. the activity has dates set which do not include current, or
        // 3. the activity has any other conditions set (regardless of whether
        //    current user meets them)

        if (!$this->can_view_hidden($mod)) {
            return false;
        }

        if (!$mod->visible) {
            return true;
        }

        if (empty($CFG->enableavailability)) {
            return false;
        }

        if ($mod->availablefrom > time()) {
            return true;
        }

        if ($mod->availableuntil && $mod->availableuntil < time()){
            return true;
        }

        if (count($mod->conditionsgrade) > 0 ) {
            return true;
        }

        if (count($mod->conditionscompletion) > 0) {
            return true;
        }

        return false;
    }


    private function is_mod_visible($cmid) {
        if (isset($this->modinfo->cms[$cmid])) {
            // We can continue (because it will not be displayed at all)
            // if:
            // 1) The activity is not visible to users
            // and
            // 2a) The 'showavailability' option is not set (if that is set,
            //     we need to display the activity so we can show
            //     availability info)
            // or
            // 2b) The 'availableinfo' is empty, i.e. the activity was
            //     hidden in a way that leaves no info, such as using the
            //     eye icon.
            if (!$this->modinfo->cms[$cmid]->uservisible &&
                (empty($this->modinfo->cms[$cmid]->showavailability) ||
                empty($this->modinfo->cms[$cmid]->availableinfo))) {
                    // visibility shortcut
                    return false;
                }
        } else {
            $mod = $this->mods[$cmid];
            if (!file_exists("$CFG->dirroot/mod/$mod->modname/lib.php")) {
                // module not installed
                return false;
            }
            if (!coursemodule_visible_for_user($mod) &&
                empty($mod->showavailability)) {
                    // full visibility check
                    return false;
            }
        }


        return true;
    }

    public function modname($name) {
        if (!isset($this->_modnames[$name])) {
            $this->_modulenames[$name] = get_string('modulename', $name);
        }
        return $this->_modulenames[$name];
    }

    private function mod_moving_html($mod) {
        global $USER, $OUTPUT;

        if (!$this->ismoving) {
            return '';
        }
        
        $url = new moodle_url('/course/mod.php',
            array('moveto'=>$mod->id, 'sesskey'=>sesskey())
        );
        $strmovefull = strip_tags(get_string('movefull', '', "$USER->activitycopyname"));

        return html_writer::link($url,
            $OUTPUT->pix_icon('movehere', get_string('movehere'), 'moodle', 
                               array('class'=>'movetarget')),
                array('title' => $strmovefull)
            );
    }

    private function get_moving_html($sectionid) {
        global $OUTPUT;

        $url = new moodle_url('/course/mod.php', array('movetosection' => $sectionid, 'sesskey' => sesskey()));

        return html_writer::tag('li', 
            html_writer::link($url, 
            $OUTPUT->pix_icon('movehere', get_string('movehere'), 'moodle',
                                array('class' => 'movetarget')),
                array('title' => get_string('movehere'))
            ));
    }

    private function mod_get_ident_class($mod) {
            $classes = array('mod-indent');
            if (!empty($mod->indent)) {
                $classes[] = 'mod-indent-'.$mod->indent;
                if ($mod->indent > 15) {
                    $classes[] = 'mod-indent-huge';
                }
            }
            return join(' ', $classes);
    }
}
