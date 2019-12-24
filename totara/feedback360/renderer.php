<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralms.com>
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara
 * @subpackage totara_feedback360
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once('lib.php');

/**
 * Output renderer for totara_feedback360s module
 */
class totara_feedback360_renderer extends plugin_renderer_base {

    /**
     * Return a button that when clicked, takes the user to feedback360 creation form
     *
     * @return string HTML to display the button
     */
    public function create_feedback360_button() {
        return $this->output->single_button(new moodle_url('/totara/feedback360/general.php'),
            get_string('createfeedback360', 'totara_feedback360'), 'get');
    }

    public function preview_feedback360_button($feedback360id) {
        $preview_params = array('feedback360id' => $feedback360id, 'preview' => 1);
        $preview_url = new moodle_url('/totara/feedback360/feedback.php', $preview_params);
        $preview_str = get_string('preview', 'totara_feedback360');
        $preview_button = new single_button($preview_url, $preview_str, 'get');
        $preview_button->class .= ' feedback360-previewer';
        $preview_button->add_action(new popup_action('click', new moodle_url($preview_url, $preview_params), 'previewpopup',
                array('height' => 800, 'width' => 1000)));
        return $this->render($preview_button);
    }

    /**
     * Renders a single button to request feedback
     *
     * @deprecated deprecated since 10
     * @param int $userid
     * @return string
     */
    public function request_feedback360_button($userid) {
        $request_params = array('action' => 'form', 'userid' => $userid);
        $request_url = new moodle_url('/totara/feedback360/request.php', $request_params);
        $request_str = get_string('requestfeedback360', 'totara_feedback360');
        $request_options = array('class' => 'requestbutton');
        return $this->output->single_button($request_url, $request_str, 'get', $request_options);
    }

    /**
     * Renders a table containing feedback360s list for manager
     *
     * @param array $feedback360s array of feedback360 object
     * @param int $userid User id to show actions according their rights
     * @return string HTML table
     */
    public function feedback360_manage_table($feedback360s = array(), $userid = null) {
        global $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        if (empty($feedback360s)) {
            return get_string('nofeedback360s', 'totara_feedback360');
        }

        $tableheader = array(get_string('name', 'totara_feedback360'),
                             get_string('assignments', 'totara_feedback360'),
                             get_string('anonymous', 'totara_feedback360'),
                             get_string('status', 'totara_feedback360'),
                             get_string('options', 'totara_feedback360'));

        $feedback360stable = new html_table();
        $feedback360stable->summary = '';
        $feedback360stable->head = $tableheader;
        $feedback360stable->data = array();
        $feedback360stable->attributes = array('class' => 'generaltable fullwidth');

        $stractivate = get_string('activate', 'totara_feedback360');
        $strclose = get_string('close', 'totara_feedback360');
        $strsettings = get_string('settings', 'totara_feedback360');
        $strdelete = get_string('delete', 'totara_feedback360');
        $strclone = get_string('copy', 'moodle');

        $systemcontext = context_system::instance();

        $data = array();
        foreach ($feedback360s as $feedback360) {
            $name = format_string($feedback360->name);
            $activateurl = new moodle_url('/totara/feedback360/activation.php',
                    array('id' => $feedback360->id, 'action' => 'activate'));
            $closeurl = new moodle_url('/totara/feedback360/activation.php',
                    array('id' => $feedback360->id, 'action' => 'close'));
            $editurl = new moodle_url('/totara/feedback360/general.php',
                    array('id' => $feedback360->id));
            $deleteurl = new moodle_url('/totara/feedback360/manage.php',
                    array('id' => $feedback360->id, 'action' => 'delete'));
            $cloneurl = new moodle_url('/totara/feedback360/manage.php',
                    array('id' => $feedback360->id, 'action' => 'copy'));

            $row = array();
            if (has_capability('totara/feedback360:managefeedback360', $systemcontext, $userid)) {
                $row[] = html_writer::link($editurl, $name);
            } else {
                $row[] = $name;
            }

            $assign = new totara_assign_feedback360('feedback360', new feedback360($feedback360->id));
            $countassignments = $assign->get_current_users_count();
            if ($feedback360->status == feedback360::STATUS_DRAFT) {
                $row[] = get_string('assignedtoxdraftusers', 'totara_feedback360', $countassignments);
            } else {
                $row[] = get_string('assignedtoxusers', 'totara_feedback360', $countassignments);
            }
            if (!empty($feedback360->anonymous)) {
                $row[] = get_string('yes');
            } else {
                $row[] = '<span class="accesshide">'.get_string('no').'</span>';
            }
            $row[] = feedback360::display_status($feedback360->status);
            $options = '';
            if (has_capability('totara/feedback360:managefeedback360', $systemcontext, $userid)) {
                $clone = $this->output->action_icon($cloneurl, new pix_icon('/t/copy', $strclone, 'moodle'));
                if ($feedback360->status == feedback360::STATUS_ACTIVE) {
                    $edit_error = get_string('error:feedback360noteditable', 'totara_feedback360');
                    $edit = $this->output->pix_icon('/t/edit_gray', $edit_error, 'moodle', array('class' => 'disabled iconsmall'));

                    $delete_error = get_string('error:feedback360isactive', 'totara_feedback360');
                    $delete = $this->output->pix_icon('/t/delete_gray', $delete_error, 'moodle', array('class' => 'disabled iconsmall'));
                } else {
                    $edit = $this->output->action_icon($editurl, new pix_icon('/t/edit', $strsettings, 'moodle'));
                    $delete = $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'));
                }

                $options .= $edit;
                $options .= $clone;
                $options .= $delete;
            }

            $activate = '';
            if (has_capability('totara/feedback360:manageactivation', $systemcontext, $userid)) {
                if ($feedback360->status == feedback360::STATUS_ACTIVE) {
                    $activate = $this->output->action_link($closeurl, $strclose);
                } else if ($feedback360->status == feedback360::STATUS_DRAFT) {
                    $activate = $this->output->action_link($activateurl, $stractivate);
                }
            }
            $row[] = $options . ' ' . $activate;

            $data[] = $row;
        }
        $feedback360stable->data = $data;

        return html_writer::table($feedback360stable);

    }

    public function feedback360_management_tabs($feedback360id, $currenttab = 'general') {
        global $CFG;

        $tabs = array();
        $row = array();
        $activated = array();
        $inactive = array();

        if ($feedback360id < 1) {
            $inactive = array('content', 'assignments', 'recipients');
        }

        $systemcontext = context_system::instance();
        if (has_capability('totara/feedback360:managefeedback360', $systemcontext)) {
            $row[] = new tabobject('general', $CFG->wwwroot . '/totara/feedback360/general.php?id='
                    . $feedback360id, get_string('general'));
        }
        if (has_capability('totara/feedback360:managepageelements', $systemcontext)) {
            $row[] = new tabobject('content', $CFG->wwwroot . '/totara/feedback360/content.php?feedback360id='
                    . $feedback360id, get_string('content', 'totara_feedback360'));
        }
        $capabilities = array('totara/feedback360:viewassignedusers', 'totara/feedback360:assignfeedback360togroup');
        if (has_any_capability($capabilities, $systemcontext)) {
            $row[] = new tabobject('assignments', $CFG->wwwroot . '/totara/feedback360/assignments.php?id='
                    . $feedback360id, get_string('assignments', 'totara_feedback360'));
        }

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }


    /**
     * Returns a table showing the currently assigned groups of users
     *
     * @param array $assignments group assignment info
     * @param int $itemid the id of the feedback360 object users are assigned to
     * @return string HTML
     */
    public function display_assigned_groups($assignments, $itemid) {
        $tableheader = array(get_string('assigngrouptype', 'totara_feedback360'),
                             get_string('assignsourcename', 'totara_feedback360'),
                             get_string('assignincludechildren', 'totara_feedback360'),
                             get_string('assignnumusers', 'totara_feedback360'),
                             get_string('actions'));

        $feedback360 = new feedback360($itemid);

        $table = new html_table();
        $table->attributes['class'] = 'fullwidth generaltable';
        $table->summary = '';
        $table->head = $tableheader;
        $table->data = array();
        if (empty($assignments)) {
            $table->data[] = array(get_string('nogroupassignments', 'totara_feedback360'));
        } else {
            foreach ($assignments as $assign) {
                $includechildren = ($assign->includechildren == 1) ? get_string('yes') : get_string('no');
                $row = array();
                $row[] = new html_table_cell($assign->grouptypename);
                $row[] = new html_table_cell($assign->sourcefullname);
                $row[] = new html_table_cell($includechildren);
                $row[] = new html_table_cell($assign->groupusers);
                // Only show delete if feedback360 is draft status.
                if ($feedback360->status == feedback360::STATUS_DRAFT) {
                    $delete = $this->output->action_icon(
                            new moodle_url('/totara/feedback360/assignments.php',
                                array('id' => $itemid, 'deleteid' => $assign->id, 'sesskey' => sesskey())),
                            new pix_icon('t/delete', get_string('delete')));
                    $row[] = new html_table_cell($delete);
                } else {
                    $row[] = '';
                }
                $table->data[] = $row;
            }
        }
        $out = $this->output->container(html_writer::table($table), 'clearfix', 'assignedgroups');
        return $out;
    }

    /**
     * Display feedback header.
     *
     * @param feedback360_responder $resp
     * @param user_record $subjectuser      The subject of the feedback.
     * @param int $anonymous 1 if the feedback is being submitted anonymously, otherwise 0.
     * @param int $numresponders Total number of users who have been asked to respond to this feedback.
     * @return string HTML
     */
    public function display_feedback_header(feedback360_responder $resp, $subjectuser, $anonymous, $numresponders) {
        global $CFG, $USER;

        // The heading.
        $a = new stdClass();
        $a->username = fullname($subjectuser);
        $a->userid = $subjectuser->id;
        $a->site = $CFG->wwwroot;
        $a->profileurl = "{$CFG->wwwroot}/user/profile.php?id={$subjectuser->id}";

        $anonmessage = false;
        if ($resp->is_email()) {
            if (!$resp->tokenaccess and $subjectuser->id == $USER->id) {
                $titlestr = 'userownheaderfeedback';
            } else {
                $a->responder = $resp->get_email();
                $titlestr = 'userheaderfeedbackbyemail';
                $anonmessage = true;
            }
        } else {
            if ($resp->userid == $resp->subjectid) {
                // Users self evaluation.
                $titlestr = 'userownheaderselfevalfeedback';
            } else if ($subjectuser->id == $USER->id) {
                $titlestr = 'userownheaderfeedback';
            } else {
                $titlestr = 'userheaderfeedback';
                $anonmessage = true;
            }
        }

        $message = html_writer::tag('p', get_string($titlestr, 'totara_feedback360', $a));
        if ($anonmessage) {
            if ($anonymous) {
                $anonmessage = get_string('feedbackanonymous', 'totara_feedback360', $numresponders);
            } else {
                $anonmessage = get_string('feedbacknotanonymous', 'totara_feedback360');
            }
            $message .= html_writer::tag('p', $anonmessage);
        }

        $content = $this->output->user_picture($subjectuser, array('link' => false)) . $message;

        if (!$resp->is_completed() && !$resp->is_fake()) {
            $savebutton = new single_button(new moodle_url('#'), get_string('saveprogress', 'totara_feedback360'));
            $savebutton->formid = 'saveprogress';
            $save = html_writer::tag('div', $this->output->render($savebutton), array('class' => 'feedback360-save'));
            $content = $save . $content;
        }

        $out = html_writer::tag('div', '', array('class' => "empty", 'id' => 'feedbackhead-anchor'));

        // HACK ALERT: Notifications by default put the content through clean text.
        // This won't work for the above save progress button, but because they clean it in code the template doesn't attempt to
        // clean it itself.
        // We can get around this by using the template directly and making sure that we clean ourselves.
        $context = array('message' => $content);
        $out .= html_writer::tag('div', $this->render_from_template('core/notification_info', $context), array('id' => 'feedbackhead'));

        return $out;
    }

    public function display_preview_feedback_header(feedback360_responder $resp, $feedbackname) {
        $headerstr = get_string('previewheader', 'totara_feedback360', $feedbackname);
        $subheader = get_string('previewsubheader', 'totara_feedback360');

        $content = $this->output->heading($headerstr);
        $content .= $subheader;

        $out = html_writer::tag('div', '', array('class' => "empty", 'id' => 'feedbackhead-anchor'));
        $out .= $this->notification($content, 'notifymessage');

        return $out;
    }

    public function display_userview_header($user) {
        global $USER;

        $header = '';
        if ($USER->id != $user->id) {
            $picture = $this->output->user_picture($user);
            $name = fullname($user);
            $url = new moodle_url('/user/profile.php', array('id' => $user->id));
            $link = html_writer::link($url, $name);
            $viewstr = html_writer::tag('strong', get_string('viewinguserxfeedback360', 'totara_feedback360', $link));

            $header = html_writer::tag('div', $picture . ' ' . $viewstr,
                array('class' => "plan_box notifymessage totara-feedback360-head-relative", 'id' => 'feedbackhead'));
        }

        return $header;
    }

    /**
     * Returns the base markup for a paginated user table widget
     *
     * @param  boolean $show_assignedvia    Show the "Assigned Via" column?
     * @return string HTML
     */
    public function display_user_datatable($show_assignedvia=true) {
        $table = new html_table();
        $table->id = 'datatable';
        $table->attributes['class'] = 'generaltable clearfix';
        $table->head = array(get_string('learner'));
        if ($show_assignedvia) {
            $table->head[] = get_string('assignedvia', 'totara_core');
        }
        $out = $this->output->container(html_writer::table($table), 'clearfix', 'assignedusers');
        return $out;
    }


    /**
     * Get status name and call to action
     *
     * @param int $status
     * @param int $id
     * @return string
     */
    public function feedback360_additional_actions($status, $id) {
        $activateurl = new moodle_url('/totara/feedback360/activation.php', array('id' => $id, 'action' => 'activate'));
        $closeurl = new moodle_url('/totara/feedback360/activation.php', array('id' => $id, 'action' => 'close'));

        $strstatusnow = feedback360::display_status($status);
        $strstatusat = get_string('statusat', 'totara_feedback360');
        $feedback360 = new feedback360($id);

        $preview = $this->preview_feedback360_button($id);
        $activate = '';
        if ($feedback360->status == feedback360::STATUS_ACTIVE) {
            $activate = $this->output->action_link($closeurl, get_string('closenow', 'totara_feedback360'));
        } else if ($feedback360->status == feedback360::STATUS_DRAFT || $feedback360->status == feedback360::STATUS_CLOSED) {
            $activate = $this->output->action_link($activateurl,  get_string('activatenow', 'totara_feedback360'));
        }

        $out  = html_writer::start_tag('div', array('class' => 'additional_actions'));
        $out .= $strstatusat;
        $out .= $strstatusnow . ' ';
        $out .= $activate;
        $out .= $preview;
        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * Confirm feedback360 delete
     *
     * @param feedback360 $feedback360
     * @return string
     */
    public function confirm_delete_feedback360 (feedback360 $feedback360) {
        $html = '';
        if ($feedback360->status != feedback360::STATUS_DRAFT) {
            $completed_responses = $feedback360->count_completed_answers();
            $html .= html_writer::div(get_string('deletefeedback360responses', 'totara_feedback360', $completed_responses));
            $html .= html_writer::empty_tag('br');
        }

        $params = array('action' => 'delete',
                        'confirm' => 1,
                        'id' => $feedback360->id,
                        'sesskey' => sesskey(),
                  );

        $cancel = $this->output->single_button(new moodle_url('/totara/feedback360/manage.php'), get_string('cancel'), 'get');
        $continue = $this->output->single_button(new moodle_url('/totara/feedback360/manage.php', $params), get_string('continue'), 'post');
        $html .= html_writer::tag('div', $continue . $cancel, array('class' => 'buttons', 'id' => 'notice'));
        return $html;
    }

    /**
     * Confirm feedback360 quesiton delete
     *
     * @param feedback360_question $question
     * @return string
     */
    public function confirm_question_delete(feedback360_question $question) {
        $msg = get_string('confirmdeletequestion', 'totara_feedback360', $question->name);

        $params = array('action' => 'delete',
                        'confirm' => 1,
                        'id' => $question->id,
                        'feedback360id' => $question->feedback360id,
                        'sesskey' => sesskey(),
                  );

        $continue = new moodle_url('/totara/feedback360/content.php', $params);
        $cancel = new moodle_url('/totara/feedback360/content.php', array('feedback360id' => $question->feedback360id));

        return $this->output->confirm($msg, $continue, $cancel);
    }

    public function confirm_activation_feedback360 ($feedback360, $errors) {

        if (!empty($errors)) {
            $out = $this->heading(get_string('error:activationconfirmation', 'totara_feedback360'));
            $out .= html_writer::tag('p', get_string('feedback360fixerrors', 'totara_feedback360'));
            $errordesc = array();
            foreach ($errors as $error) {
                $errordesc[] = html_writer::tag('li', $error);
            }
            $out .= html_writer::tag('ul', implode('', $errordesc), array('class' => 'feedback360errorlist'));
            $buttons = array();
            $buttons[] = $this->output->single_button(new moodle_url('/totara/feedback360/content.php',
                    array('feedback360id' => $feedback360->id)), get_string('backtofeedback360', 'totara_feedback360',
                            $feedback360->name), 'get');
            $out .= html_writer::tag('div', implode(' ', $buttons), array('class' => 'buttons'));
            return $out;
        } else {
            $msg = get_string('confirmactivatefeedback360', 'totara_feedback360', $feedback360->name);
            $params = array('id' => $feedback360->id,
                                'action' => 'activate',
                                'confirm' => 1,
                                'sesskey' => sesskey()
                          );

            $continueurl = new moodle_url('/totara/feedback360/activation.php', $params);
            $cancelurl = new moodle_url('/totara/feedback360/manage.php');

            return $this->output->confirm($msg, $continueurl, $cancelurl);
        }
    }

    public function confirm_close_feedback360 ($feedback360) {
        $msg = get_string('confirmclosefeedback360', 'totara_feedback360', $feedback360->name);
        $params = array('id' => $feedback360->id,
                            'action' => 'close',
                            'confirm' => 1,
                            'sesskey' => sesskey()
                      );
        $continueurl = new moodle_url('/totara/feedback360/activation.php', $params);
        $cancelurl = new moodle_url('/totara/feedback360/manage.php');

        return $this->output->confirm($msg, $continueurl, $cancelurl);

    }

    /**
     * Retruns list of questions of particular page
     *
     * @param array $quests of stdClass
     * @return string
     */
    public function list_questions($quests) {
        $list = array();
        if ($quests) {
            $feedback360 = new feedback360(current($quests)->feedback360id);

            $stredit = get_string('settings', 'totara_question');
            $strclone = get_string('copy');
            $strdelete = get_string('delete', 'totara_question');
            $strup =  get_string('moveup', 'totara_question');
            $strdown =  get_string('movedown', 'totara_question');
            $last = end($quests);
            $first = true;
            reset($quests);

            $questtypes = question_manager::get_registered_elements();
            foreach ($quests as $quest) {
                $question = new feedback360_question($quest->id);
                $posuplink = $posdownlink = '';
                $attrs = array(
                    'class' => '',
                    'data-questid' => $quest->id
                );

                if (!$first) {
                    $posupurl = new moodle_url('/totara/feedback360/content.php', array('action' => 'posup',
                        'id' => $quest->id, 'feedback360id' => $feedback360->id, 'sesskey' => sesskey()));
                    $posuplink = $this->output->action_icon($posupurl, new pix_icon('/t/up', $strup, 'moodle'), null,
                            array('class' => 'action-icon js-hide'));
                } else {
                    $attrs['class'] .= ' first';
                    $first = false;
                    $posuplink = $this->output->spacer(array('width' => 21, 'height' => 15));
                }
                if ($quest->id != $last->id) {
                    $posdownurl = new moodle_url('/totara/feedback360/content.php', array('action' => 'posdown',
                            'id' => $quest->id, 'feedback360id' => $feedback360->id, 'sesskey' => sesskey()));
                    $posdownlink = $this->output->action_icon($posdownurl, new pix_icon('/t/down', $strdown, 'moodle'), null,
                            array('class' => 'action-icon js-hide'));
                } else {
                    $attrs['class'] .= ' last';
                    $posdownlink = $this->output->spacer(array('width' => 21, 'height' => 15));
                }
                $editurl = new moodle_url('/totara/feedback360/content.php', array('action' => 'edit',
                    'id' => $quest->id, 'feedback360id' => $feedback360->id));
                $cloneurl = new moodle_url('/totara/feedback360/content.php', array('action' => 'clone',
                    'id' => $quest->id, 'feedback360id' => $feedback360->id));
                $deleteurl = new moodle_url('/totara/feedback360/content.php', array('action' => 'delete',
                    'id' => $quest->id, 'feedback360id' => $feedback360->id));;

                $dragdrop = $this->pix_icon('/i/dragdrop', '', 'moodle', array('class' => 'iconsmall js-show-inline move'));
                $editlink = $this->output->action_icon($editurl, new pix_icon('/t/edit', $stredit, 'moodle'), null,
                    array('class' => 'action-icon edit'));
                $clonelink = $this->output->action_icon($cloneurl, new pix_icon('/t/copy', $strclone, 'moodle'), null,
                    array('class' => 'action-icon copy'));
                $deletelink = $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
                    array('class' => 'action-icon delete'));

                $questtext = html_writer::tag('strong', format_string($question->get_name())) .
                             html_writer::empty_tag('br') .
                             html_writer::tag('label', $questtypes[$quest->datatype]['type']);
                $strquest = html_writer::tag('span', $questtext, array('class' => 'feedback360-quest-list-name'));

                $actions = '';
                if (feedback360::is_draft($feedback360)) {
                    $actions = html_writer::tag('span', $posuplink.$posdownlink.$dragdrop.$editlink.$clonelink.$deletelink,
                            array('class'=>'feedback360-quest-actions'));
                } else {
                    $strview = get_string('view');
                    $viewlink = $this->output->action_icon($editurl, new pix_icon('/t/preview', $strview, 'moodle'), null,
                        array('class' => 'action-icon view'));

                    $actions = html_writer::tag('span', $viewlink, array('class'=>'feedback360-quest-actions'));
                }
                $list[] = html_writer::tag('li', $actions.$strquest, $attrs);
            }
            $nav = html_writer::tag('ul', implode($list), array('id'=>'feedback360-quest-list',
                'class' => 'feedback360-quest-list yui-nav'));
            return html_writer::tag('div', $nav, array('class' => 'yui-u first'));
        }
        return '';
    }

    /**
     * Prints out the table containing all of the users active feedback forms.
     *
     * @param int userid
     * @param array $user_assignments   an array of records from feedback360_user_assignment relating to userid
     * @param bool  $canmanage
     * @return string   the html table
     */
    public function myfeedback_user_table($userid, $user_assignments, $canmanage) {
        global $DB, $USER;

        $out = '';

        $user_table = new html_table();
        $user_table->attributes = array('class' => 'generaltable feedback360_usertable');
        $user_table->head = array(
            get_string('name', 'totara_feedback360'),
            get_string('responses', 'totara_feedback360'),
            get_string('duedate', 'totara_feedback360'),
            get_string('anonymous', 'totara_feedback360')
        );
        if ($canmanage) {
            $user_table->head[] = get_string('options', 'totara_feedback360');
        }

        $stryes = new lang_string('yes');
        $strno = new lang_string('no');
        $stranoncancel = new lang_string('anoncancel', 'totara_feedback360');
        $strstop = new lang_string('stop', 'totara_feedback360');

        $nodata = true;
        foreach ($user_assignments as $user_assignment) {
            // Count how many requests for the feedback you have sent, including self evaluations.
            $requests = $DB->count_records('feedback360_resp_assignment',
                array('feedback360userassignmentid' => $user_assignment->id));

            // Count how many replies to your feedback you have received.
            $respondparams = array('uaid' => $user_assignment->id, 'uid' => $userid);

            $respondsql = "SELECT count(*)
                             FROM {feedback360_resp_assignment} re
                            WHERE re.feedback360userassignmentid = :uaid
                              AND re.timecompleted > 0"

            ;
            $responses = $DB->count_records_sql($respondsql, $respondparams);

            $newresponses = $DB->count_records_sql($respondsql . ' AND re.viewed = 0', $respondparams);

            $nodata = false;
            // Set up some variables for the cells.
            $res = new stdClass();
            $res->total = $requests;
            $res->responded = $responses;
            $res->new = $newresponses;

            $duedate = !empty($user_assignment->timedue) ? userdate($user_assignment->timedue,
                    get_string('strftimedate', 'langconfig')) : '';
            $nameurl = new moodle_url('/totara/feedback360/request/view.php',
                    array('userassignment' => $user_assignment->id));
            $namelink = html_writer::link($nameurl, format_string($user_assignment->name));
            $anonymous = !empty($user_assignment->anonymous) ? $stryes : $strno;

            // The contents of the options column.
            if ($canmanage) {

                if ($user_assignment->status != feedback360::STATUS_ACTIVE) {
                    // Feedback closed.
                    $options = get_string('closed', 'totara_feedback360');

                } else if (empty($requests)) {
                    // No requests for feedback have been made, we just display the 'Request feedback' button.

                    $params = array(
                        'userid' => $userid,
                        'action' => 'users',
                        'formid' => $user_assignment->id);
                    $url = new moodle_url('/totara/feedback360/request.php', $params);
                    $options = $this->output->single_button($url, get_string('requestfeedback360', 'totara_feedback360'), 'get');

                } else {
                    // Feedback requests have been made, display the required options.
                    $editparams = array('action' => 'users', 'userid' => $userid, 'formid' => $user_assignment->id, 'update' => 1);
                    $editurl = new moodle_url('/totara/feedback360/request.php', $editparams);
                    $editstr = get_string('edit');
                    $edit = $this->output->action_icon($editurl, new pix_icon('/t/edit', $editstr, 'moodle'));
                    $remindparams = array('userformid' => $user_assignment->id);
                    $remindurl = new moodle_url('/totara/feedback360/request/remind.php', $remindparams);
                    $remindstr = get_string('remind', 'totara_feedback360');
                    $remind = $this->output->action_icon($remindurl, new pix_icon('/t/email', $remindstr, 'moodle'));

                    if (!empty($user_assignment->anonymous)) {
                        $cancel = $this->output->pix_icon('/t/stop_gray', $stranoncancel, 'moodle', array('class' => 'iconsmall'));
                    } else {
                        $cancelparams = array('userformid' => $user_assignment->id);
                        $cancelurl = new moodle_url('/totara/feedback360/request/stop.php', $cancelparams);
                        $cancel = $this->output->action_icon($cancelurl, new pix_icon('/t/stop', $strstop, 'moodle'));
                    }

                    // Self evaluation button.
                    if (feedback360::can_self_evaluate($user_assignment->id, $userid)) {
                        $selfevaluateparams = array(
                            'userid' => $userid,
                            'feedback360id' => $user_assignment->feedback360id
                        );
                        $selfevaluateurl = new moodle_url('/totara/feedback360/feedback.php', $selfevaluateparams);

                        // Disable button for managers viewing the the users feedback.
                        $options = $userid == $USER->id ? array() : array('disabled' => true);

                        $selfevaluate = $this->output->single_button($selfevaluateurl,
                            get_string('evaluateyourself', 'totara_feedback360'), 'get', $options);
                    } else {
                        $selfevaluate = null;
                    }

                    if ($res->total == $res->responded) {
                        $options = $edit . $selfevaluate;
                    } else {
                        $options = $edit . $remind . $cancel . $selfevaluate;
                    }
                }
            }

            // Responses.
            if (empty($requests)) {
                $respond = get_string('nonerequested', 'totara_feedback360');
            } else {
                $respond = get_string('responsecount', 'totara_feedback360', $res);
                if (!empty($newresponses)) {
                    $respond .= html_writer::tag('strong', get_string('responsecountnew', 'totara_feedback360', $res));
                }
            }

            // Set up the row for the table.
            $cells = array();
            $cells['name'] = new html_table_cell($namelink);
            $cells['responses'] = new html_table_cell($respond);
            $cells['duedate'] = new html_table_cell($duedate);
            $cells['anonymous'] = new html_table_cell($anonymous);
            if ($canmanage) {
                $cells['options'] = new html_table_cell($options);
            }

            $row = new html_table_row($cells);
            $user_table->data[] = $row;
        }

        if ($nodata) {
            $cell = new html_table_cell(get_string('nofeedbackavailable', 'totara_feedback360'));
            $cell->colspan = count($user_table->head);
            $user_table->data[] = new html_table_row(array($cell));
        }

        $out .= $this->output->render($user_table);
        return $out;
    }

    /**
     * Displays a table of feedbacks that have been requested of a user
     *
     * @param int userid                the id of the user who we are printing this out for
     * @param array $resp_assignments   an array of resp_assignment records relating to userid:w
     */
    public function myfeedback_colleagues_table($userid, $resp_assignments) {
        global $USER;

        $out = '';

        $colleague_table = new html_table();
        $colleague_table->head = array(
            get_string('name', 'totara_feedback360'),
            get_string('duedate', 'totara_feedback360'),
            get_string('options', 'totara_feedback360')
        );

        if (empty($resp_assignments)) {
            $cell = new html_table_cell(get_string('nofeedback360togive', 'totara_feedback360'));
            $cell->colspan = count($colleague_table->head);
            $colleague_table->data[] = new html_table_row(array($cell));
        } else {
            foreach ($resp_assignments as $resp_assignment) {
                // Set up some variables for the cells.
                $completed = $resp_assignment->timecompleted;
                $hidebutton = false;

                $answerparams = array();
                $answerparams['userid'] = $resp_assignment->assignedby;
                $answerparams['feedback360id'] = $resp_assignment->feedback360id;
                if ($USER->id != $userid) {
                    $answerparams['viewas'] = $userid;
                    $hidebutton = true;
                }

                $answerurl = new moodle_url('/totara/feedback360/feedback.php', $answerparams);
                if (!empty($completed)) {
                    // Complete.
                    $status = get_string('completed', 'totara_feedback360');

                    $revstr = ($USER->id == $userid) ? 'reviewnow' : 'reviewnowmanager';
                    $options = $this->output->action_link($answerurl, get_string($revstr, 'totara_feedback360'));
                } else {
                    if (!$hidebutton) {
                        $options = $this->output->single_button($answerurl, get_string('answernow', 'totara_feedback360'), 'get');
                    } else {
                        $options = '';
                    }
                    if (empty($resp_assignment->timedue)) {
                        // Infinite time.
                        $status = '';
                    } else if ($resp_assignment->timedue < time()) {
                        // Overdue.
                        $status = get_string('overdue', 'totara_feedback360');
                    } else {
                        // Pending.
                        $status = get_string('pending', 'totara_feedback360');
                    }
                }

                $duedate = !empty($resp_assignment->timedue) ? userdate($resp_assignment->timedue,
                        get_string('strftimedate', 'langconfig')) : '';
                $profileurl = new moodle_url('/user/profile.php', array('id' => $resp_assignment->assignedby));
                $userlink = html_writer::link($profileurl, fullname($resp_assignment), array('class' => 'userlink'));

                // Set up the row for the table.
                $cells = array();
                $cells['name'] = new html_table_cell($userlink);
                $cells['duedate'] = new html_table_cell($duedate . ' ' . $status);
                $cells['options'] = new html_table_cell($options);

                $row = new html_table_row($cells);
                $colleague_table->data[] = $row;
            }
        }
        $out .= html_writer::table($colleague_table);
        return $out;
    }

    /**
     * Shows a table of all the users/emails with resp_assignments pertaining to a user_assignment
     * information about whether or not they have replied and a link to view replies.
     *
     * @param object $user_assignment   a user_assignment record
     * @param array  $resp_assignments  an array of resp_assignment records related to user_assignment
     * @param int $anonymous If 1 feedback is anonymous, if 0 it is not.
     * @return string HTML for the table.
     */
    public function view_request_infotable($user_assignment, $resp_assignments, $anonymous) {

        $out = '';
        $request_infotable = new html_table();
        $request_infotable->head = array(
            get_string('nameemail', 'totara_feedback360'),
            get_string('completed', 'totara_feedback360'),
            get_string('response', 'totara_feedback360'),
        );

        foreach ($resp_assignments as $resp_assignment) {
            if (!empty($resp_assignment->timecompleted)) {
                $comp_str = userdate($resp_assignment->timecompleted, get_string('strftimedate', 'langconfig'));
                if (empty($resp_assignment->viewed)) {
                    $comp_str .= ' ' . html_writer::tag('strong', get_string('new'), array('float' => 'right'));
                    $view_str = html_writer::tag('strong', get_string('viewresponse', 'totara_feedback360'));
                } else {
                    $view_str = get_string('viewresponse', 'totara_feedback360');
                }

                $responseparam = array('myfeedback' => 1, 'requestertoken' => $resp_assignment->requestertoken);
                $responseurl = new moodle_url('/totara/feedback360/feedback.php', $responseparam);
                $responselink = html_writer::link($responseurl, $view_str);
            } else {
                $comp_str = get_string('notcompleted', 'totara_feedback360');
                $responselink = '';
            }

            $isselfeval = $user_assignment->userid == $resp_assignment->userid ? true : false;

            if ($anonymous && !$isselfeval) {
                $name_str = html_writer::tag('em', get_string('anonymoususer', 'totara_feedback360'));
            } else if (empty($resp_assignment->feedback360emailassignmentid)) {
                $name_str = fullname($resp_assignment);
            } else {
                if (empty($resp_assignment->email)) {
                    $name_str = get_string('emailmissing', 'totara_feedback360');
                } else {
                    $name_str = format_string($resp_assignment->email);
                }
            }

            $cells = array();
            $cells['name'] = new html_table_cell($name_str);
            $cells['completed'] = new html_table_cell($comp_str);
            $cells['response'] = new html_table_cell($responselink);

            $row = new html_table_row($cells);
            $request_infotable->data[] = $row;
        }

        $out .= $this->output->render($request_infotable);
        return $out;
    }

    /**
     * returns the html for a system user item with delete button.
     *
     * @param object $userid    A user record
     * @param int $userform     The id of the feedback user assignment
     * @param object $resp      The associated resp_assignment for the timecompleted field
     * @param boolean $anonymous Whether the form should enforce anonymity
     */
    public function system_user_record($user, $userform, $resp, $anonymous) {
        $out = '';

        $username = fullname($user);
        $completestr = get_string('alreadyreplied', 'totara_feedback360');

        $out .= html_writer::start_tag('div', array('id' => "system_user_{$user->id}", 'class' => 'user_record'));
        $out .= $username;
        if (!$anonymous || empty($resp)) {
            if (!empty($resp->timecompleted)) {
                $out .= $this->output->pix_icon('/t/delete_gray', $completestr);
            } else {
                $removestr = get_string('removeuserfromrequest', 'totara_feedback360', $username);
                $out .= $this->output->action_icon('', new pix_icon('/t/delete', $removestr), null,
                    array('class' => 'system_record_del', 'id' => $user->id));
            }
        }
        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * returns the html for a system user item with delete button.
     *
     * @param string $email             The email used in an email_assignment record.
     * @param int $userform             The id of the user_assignment record.
     * @param object $resp              The associated resp_assignment, for the timecompleted field.
     */
    public function external_user_record($email, $userform, $resp, $anonymous) {
        global $CFG;

        $out = '';

        $completestr = get_string('alreadyreplied', 'totara_feedback360');
        $deleteparams = array('respid' => $resp->id, 'email' => $email);
        $deleteurl = new moodle_url('/totara/feedback360/request/delete.php', $deleteparams);

        $out .= html_writer::start_tag('div', array('id' => "external_user_{$email}", 'class' => 'external_record'));
        $out .= $email;
        if (!$anonymous) {
            if (!empty($resp->timecompleted)) {
                $out .= $this->output->pix_icon('/t/delete_gray', $completestr);
            } else {
                $removestr = get_string('removeuserfromrequest', 'totara_feedback360', $email);
                $out .= $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $removestr), null,
                        array('class' => 'external_record_del', 'id' => $email));
            }
        }
        $out .= html_writer::end_tag('div');

        return $out;
    }


    /**
     * returns the html for the no-js version of the user
     * selector for feedback requests
     *
     * @param sequence $selected            A list of currently selected users
     * @param url $returnurl                The url to return to when submitting form
     * @param object $add_user_selector     User selector object with users to add
     * @param object $remove_user_selector  User selector object with users to remove
     */
    public function nojs_feedback_request_users($selected, $returnurl, $add_user_selector, $remove_user_selector, $anonymous = false) {
        $out = '';

        $out .= html_writer::start_tag('form', array('action' => $returnurl, 'method' => 'post', 'id' => 'assignform'));
        $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'selected', 'value' => $selected));

        $out .= html_writer::start_tag('div', array('class' => 'row-fluid user-multiselect'));
        $out .= html_writer::start_tag('div', array('class' => 'span5'));

        $out .= html_writer::tag('label',
                get_string('currentrequestees', 'totara_feedback360'),
                array('for' => 'removeselect'));
        $out .= $remove_user_selector->display(true);
        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_tag('div', array('class' => 'span2 controls'));
        $out .= html_writer::empty_tag('input', array(
                'type' => 'submit',
                'name' => 'add',
                'value' => $this->output->larrow() . get_string('add'),
                'title' => get_string('add')
            ));

        // Anonymous feedback can't have requests removed.
        if (!$anonymous) {
            $out .= html_writer::empty_tag('input', array(
                    'type' => 'submit',
                    'name' => 'remove',
                    'value' => $this->output->rarrow(). get_string('remove'),
                    'title' => get_string('remove')
                ));
        }
        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_tag('div', array('class' => 'span5'));
        $out .= html_writer::tag('label',
                get_string('potentialrequestees', 'totara_feedback360'),
                array('for' => 'addselect'));
        $out .= $add_user_selector->display(true);
        $out .= html_writer::end_tag('div');
        $out .= html_writer::end_tag('div');

        $out .= html_writer::empty_tag('input',
                array('type' => 'submit', 'name' => 'cancel',
                    'value' => get_string('backtofeedbackrequest', 'totara_feedback360')));

        $out .= html_writer::end_tag('form');

        return $out;
    }
}
