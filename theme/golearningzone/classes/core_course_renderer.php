<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 * @package   theme_bootstrapbase
 *
 * NOTE: this code is based on code from bootstrap theme by Bas Brands and other contributors.
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/course/renderer.php");
require_once($CFG->dirroot . "/mod/url/locallib.php");

class theme_golearningzone_core_course_renderer extends core_course_renderer 
{
    public function course_search_form($value = '', $format = 'plain') 
    {
        // Totara: Code from Moodle 3.0
        static $count = 0;
        $formid = 'coursesearch';
        if ((++$count) > 1) {
            $formid .= $count;
        }

        switch ($format) {
            case 'navbar' :
                $formid = 'coursesearchnavbar';
                $inputid = 'navsearchbox';
                $inputsize = 20;
                break;
            case 'short' :
                $inputid = 'shortsearchbox';
                $inputsize = 12;
                break;
            default :
                $inputid = 'coursesearchbox';
                $inputsize = 30;
        }

        $strsearchcourses= get_string("searchcourses");
        $searchurl = new moodle_url('/course/search.php');

        $output = html_writer::start_tag('form', array('id' => $formid, 'action' => $searchurl, 'method' => 'get'));
        $output .= html_writer::start_tag('fieldset', array('class' => 'coursesearchbox invisiblefieldset'));
        $output .= html_writer::tag('label', $strsearchcourses.': ', array('for' => $inputid));
        $output .= html_writer::empty_tag('input', array('type' => 'text', 'id' => $inputid,
            'size' => $inputsize, 'name' => 'search', 'value' => s($value)));
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'value' => get_string('go')));
        $output .= html_writer::end_tag('fieldset');
        $output .= html_writer::end_tag('form');

        return $output;
    }

    // /**
    //  * Renders html to display a name with the link to the course module on a course page
    //  *
    //  * If module is unavailable for user but still needs to be displayed
    //  * in the list, just the name is returned without a link
    //  *
    //  * Note, that for course modules that never have separate pages (i.e. labels)
    //  * this function return an empty string
    //  *
    //  * @param cm_info $mod
    //  * @param array $displayoptions
    //  * @return string
    //  */
    // public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {
    //     global $CFG;
    //     $output = '';
    //     if (!$mod->uservisible && empty($mod->availableinfo)) {
    //         // nothing to be displayed to the user
    //         return $output;
    //     }

    //     if (!$mod->url) {
    //         return $output;
    //     }
        
    //     //Accessibility: for files get description via icon, this is very ugly hack!
    //     $instancename = $mod->get_formatted_name();
    //     $altname = $mod->modfullname;
    //     // Avoid unnecessary duplication: if e.g. a forum name already
    //     // includes the word forum (or Forum, etc) then it is unhelpful
    //     // to include that in the accessible description that is added.
    //     if (false !== strpos(core_text::strtolower($instancename),
    //             core_text::strtolower($altname))) {
    //         $altname = '';
    //     }
    //     // File type after name, for alphabetic lists (screen reader).
    //     if ($altname) {
    //         $altname = get_accesshide(' '.$altname);
    //     }

    //     // For items which are hidden but available to current user
    //     // ($mod->uservisible), we show those as dimmed only if the user has
    //     // viewhiddenactivities, so that teachers see 'items which might not
    //     // be available to some students' dimmed but students do not see 'item
    //     // which is actually available to current student' dimmed.
    //     $linkclasses = '';
    //     $accesstext = '';
    //     $textclasses = '';
    //     if ($mod->uservisible) {
    //         $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
    //         $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
    //             has_capability('moodle/course:viewhiddenactivities', $mod->context);
    //         if ($accessiblebutdim) {
    //             $linkclasses .= ' dimmed';
    //             $textclasses .= ' dimmed_text';
    //             if ($conditionalhidden) {
    //                 $linkclasses .= ' conditionalhidden';
    //                 $textclasses .= ' conditionalhidden';
    //             }
    //             // Show accessibility note only if user can access the module himself.
    //             $accesstext = get_accesshide(get_string('hiddenfromstudents').':'. $mod->modfullname);
    //         }
    //     } else {
    //         $linkclasses .= ' dimmed';
    //         $textclasses .= ' dimmed_text';
    //     }

    //     $groupinglabel = $mod->get_grouping_label($textclasses);

    //     // Totara: Display link itself with flex icon.
    //     global $OUTPUT;
    //     $activitylink  = '';
    //     $activitylink .= $accesstext;
    //     $activitylink .= html_writer::tag(
    //             'span', 
    //             $instancename . $altname, 
    //             array('class' => 'instancename', 'data-movetext' => 'true')
    //     );

    //     if ($mod->uservisible) {
    //         $output .= $activitylink . $groupinglabel;
    //     } else {
    //         // We may be displaying this just in order to show information
    //         // about visibility, without the actual link ($mod->uservisible)
    //         $output .= html_writer::tag('div', $activitylink, array('class' => $textclasses)) .
    //                 $groupinglabel;
    //     }
    //     return $output;
    // }

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) 
    {
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->uservisible && empty($mod->availableinfo)) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }

        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        // This div is used to indent the content.
        $output .= html_writer::div('', $indentclasses);

        $output .= html_writer::start_tag('div', ['class' => 'row']);
        $output .= html_writer::start_tag('div', ['class' => 'col-md-12 text-right', 'style' => 'margin-bottom:5px;']);
        $output .=  $this->editIcons($mod, $displayoptions, $sectionreturn);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div', ['class' => 'row']);

        $isFaceToFace = $this->getFaceToFace($mod);
        $mainSize = $isFaceToFace ? 8 : 9;
        $actionsSize = $isFaceToFace ? 4 : 3;

        $output .=  '<div class="col-sm-2 col-xs-4 text-center">'
                        .$this->icon($mod).
                    '</div>
                    <div class="col-sm-10 col-xs-8">
                        <div class="row">
                            <div class="col-md-'.$mainSize.' col-sm-8 col-xs-12">
                                <div class="row">
                                    <div class="col-md-12 activity-name activityinstance">'
                                        .$this->course_section_cm_name($mod, $displayoptions).'
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 activity-description">'
                                        .$this->text($mod, $displayoptions).'
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-'.$actionsSize.' col-sm-4 col-xs-12 activity-actions text-right">
                                <div class="row">
                                    <div class="col-sm-12 col-xs-8 launch-button ' .($isFaceToFace ? "face-to-face" : "").'">
                                        '.$this->launchButton($mod).'
                                    </div>
                                    <div class="col-sm-12 col-xs-4">
                                        '.$this->completionIcon($course, $completioninfo, $mod, $displayoptions).'
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';

        // show availability info (if module is not available)
        $output .= $this->course_section_cm_availability($mod, $displayoptions);

        $output .= html_writer::end_tag('div'); // $indentclasses

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        // to prevent changing default behaviour, using hack: add class to container
        $dimmed = strpos($output, 'dimmed') !== false ? ' text-muted' : '';
        $output = html_writer::tag('div', $output, array('class' => 'mod-indent-outer' . $dimmed));

        return $output;
    }


        /**
     * Renders html to display the module content on the course page (i.e. text of the labels)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_text(cm_info $mod, $displayoptions = array()) {
        $output = '';
        if (!$mod->showdescription || (!$mod->uservisible && empty($mod->availableinfo))) {
            // nothing to be displayed to the user
            return $output;
        }

        $content = $mod->get_formatted_content([
            'overflowdiv' => false, 
            'noclean'     => true
        ]);

        global $DB;
        if (plugin_supports('mod', $mod->modname, FEATURE_MOD_INTRO, true) && !$content) {
            $data = $DB->get_record($mod->modname, array('id' => $mod->instance), '*', MUST_EXIST);
            $content = $data->intro;
        } elseif (!$content) {
            $content = $mod->content;
        }

        $content = '<div>'.$content.'</div>';

        $accesstext = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            if ($accessiblebutdim) {
                $textclasses .= ' dimmed_text';
                if ($conditionalhidden) {
                    $textclasses .= ' conditionalhidden';
                }
                // Show accessibility note only if user can access the module himself.
                $accesstext = get_accesshide(get_string('hiddenfromstudents').':'. $mod->modfullname);
            }
        } else {
            $textclasses .= ' dimmed_text';
        }
        if ($mod->url) {
            if ($content) {
                // If specified, display extra content after link.
                $output = html_writer::tag('div', $content, array('class' =>
                        trim('contentafterlink ' . $textclasses)));
            }
        } else {
            $groupinglabel = $mod->get_grouping_label($textclasses);

            // No link, so display only content.
            $output = html_writer::tag('div', $accesstext . $content . $groupinglabel,
                    array('class' => 'contentwithoutlink ' . $textclasses));
        }
        return $output;
    }

    private function icon($mod)
    {
        global $OUTPUT;
        return $mod->render_icon($OUTPUT, 'activityicon');
    }

    private function text($mod, $displayoptions)
    {
        $facetoface = $this->getFaceToFace($mod);
        if ($facetoface) {
            return $this->facetofaceText($facetoface);
        }
        
        return $this->course_section_cm_text($mod, $displayoptions);
    }

    private function editIcons($mod, $displayoptions, $sectionreturn)
    {
        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

        if (!empty($modicons)) {
            return html_writer::span($modicons, 'actions');
        }

        return '';
    }

    private function launchButton($mod)
    {
        $url = $mod->url;
        if (!$url) {
            return '';
        }

        global $CFG;
        global $DB;

        $text = '';
        $onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);

        if ($this->getFaceToFace($mod)) {
            $text = get_string('facetoface-view-all-sessions', 'theme_golearningzone');
        } else {
            $text = get_string('activity-launch', 'theme_golearningzone');
        }

        return html_writer::link($url, $text, ['class' => 'btn', 'onclick' => $onclick]);
    }

    private function completionIcon($course, $completioninfo, $mod, $displayoptions)
    {
        global $DB;

        $completioninfo = new completion_info($course);

        if ($completioninfo->is_enabled($mod) == COMPLETION_TRACKING_MANUAL) {
            $record = $DB->get_record('display_options', ['cmid' => $mod->id]);
            $twofa = $record && $record->enable_twofa ? 1 : 0;

            if ($twofa) {
                return '<div class="twofa" data-mod-id="'.$mod->instance.'">'.
                    $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions).
                '</div>';
            }
        }

        return $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);
    }

    private function getFaceToFace($mod)
    {
        if ($mod->get_module_type_name() != get_string('facetoface', 'mod_facetoface')) {
            return false;
        }

        global $DB;
        return $DB->get_record('facetoface', array('id' => $mod->instance));
    }

    private function getUrl($mod)
    {
        if ($mod->get_module_type_name() != get_string('pluginname', 'mod_url')) {
            return false;
        }

        global $DB;
        return $DB->get_record('url', array('id' => $mod->instance));
    }

    private function facetofaceText($facetoface)
    {
        $sessions = facetoface_get_sessions($facetoface->id, '', 0);
        $tableData = [];
        foreach ($sessions as $id => $session) {
            $timenow = time();
            if (!$session->cntdates) {
                continue;
            } elseif ($session->cntdates  && facetoface_has_session_started($session, $timenow) && facetoface_is_session_in_progress($session, $timenow)) {
                continue;
            } elseif ($session->cntdates  && facetoface_has_session_started($session, $timenow)) {
                continue;
            }

            $date = new DateTime();
            $date->setTimestamp($session->sessiondates[0]->timestart);
            $tableData[] = [
                'date'    => $date->format('d.m.y'),
                'text'    => $session->details,
                'sign-up' => "/mod/facetoface/signup.php?s=$id&backtoallsessions=1"
            ];
        }

        $ret = '<div class="row" style="margin-bottom:5px;">
                    <div class="col-md-12">
                        <b>'.get_string('facetofacetext', 'theme_golearningzone').'</b>
                    </div>
                </div>';

        foreach ($tableData as $row) {
            $ret .= "<div class=\"row\">
                        <div class=\"col-md-2 col-xs-4\">
                            <b>{$row['date']}</b>
                        </div>
                        <div class=\"col-md-5 col-xs-4\">
                            {$row['text']}
                        </div>
                        <div class=\"col-md-2 col-xs-4\">
                            <a style=\"text-transform:uppercase;\" class=\"theme-color\" href=\"{$row['sign-up']}\">"
                                .get_string('signup', 'mod_facetoface').
                            "</a>
                        </div>
                    </div>";
        }

        return $ret;
    }
}
