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
 * @author Andrew Hancox <andrewdchancox@googlemail.com> on behalf of Synergy Learning
 * @package totara
 * @subpackage enrol_totara_facetoface
 */

use \mod_facetoface\seminar;

require_once($CFG->dirroot . '/lib/formslib.php');

class enrol_totara_facetoface_signup_form extends moodleform {
    protected $instance;
    protected $toomany = false;

    /**
     * Overriding this function to get unique form id for multiple totara_facetoface enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formidprop = 'id_'.get_class($this);

        if (isset($this->_customdata->$formidprop)) {
            return $this->_customdata->$formidprop;
        } else {
            return parent::get_form_identifier();
        }
    }

    public function definition() {
        $mform = $this->_form;

        $plugin = enrol_get_plugin('totara_facetoface');

        $heading = $plugin->get_instance_name($this->_customdata);

        $mform->addElement('header', 'selfheader', $heading);

        $mform->addElement('hidden', 'instance', $this->_customdata->id);
        $mform->setType('instance', PARAM_INT);

        $mform->addElement('hidden', 'id', $this->_customdata->courseid);
        $mform->setType('id', PARAM_INT);

        $sessionsadded = self::add_signup_elements($mform, $this->_customdata, $plugin);

        if ($sessionsadded) {
            $this->add_action_buttons(true, get_string('signup', 'facetoface'));
        }
    }

    /**
     * @param $mform
     * @param $instance
     * @param enrol_totara_facetoface_plugin $totara_facetoface
     * @throws coding_exception
     * @throws dml_exception
     */
    private function add_signup_elements($mform, $instance, $totara_facetoface) {
        global $DB, $OUTPUT, $USER;

        $courseid = $instance->courseid;

        $settingautosignup = enrol_totara_facetoface_plugin::SETTING_AUTOSIGNUP;

        $sessions = $totara_facetoface->get_enrolable_sessions($courseid);

        if (empty($sessions)) {
            $mform->addElement('static', 'managermissing', get_string('managermissingallsessions', 'enrol_totara_facetoface'));
            return; // Shouldn't get here.
        }

        // Load facetofaces.
        $f2fids = array();
        foreach ($sessions as $session) {
            $f2fids[$session->facetoface] = $session->facetoface;
        }

        if (!empty($instance->$settingautosignup)) {
            $sessionsavailable = true;
        } else {// If autosignup then we don't need user to select a session.
            if ($totara_facetoface->sessions_require_manager()) {
                $mform->addElement('static', 'managermissing', get_string('managermissingsomesessions', 'enrol_totara_facetoface'));
            }

            $mform->addElement('static', 'signuptoenrol', get_string('signuptoenrol', 'enrol_totara_facetoface'));
            $mform->addElement('static', 'signuperrorcontainer', '');

            $sessrows = array();

            foreach ($sessions as $session) {
                if (empty($sessrows[$session->facetoface])) {
                    $sessrows[$session->facetoface] = array();
                }
                $sessrows[$session->facetoface][] = $session;
            }
            $mform->addElement('html', html_writer::start_div('', array('id' => 'f2fdirect-list')));

            $sessionsavailable = false;

            $force = count($sessrows) == 1 ? true : false;
            foreach ($sessrows as $facetofaceid => $sessions) {
                $seminar = new seminar($facetofaceid);

                $mform->addElement('html', html_writer::start_div('f2factivity', array('id' => 'f2factivity' . $facetofaceid)));
                $mform->addElement('html', $OUTPUT->heading($seminar->get_name(), 3));
                $activejobassigns = \totara_job\job_assignment::get_all($USER->id, $seminar->is_manager_required());
                if ($seminar->get_forceselectjobassignment() && empty($activejobassigns)) {
                    $msg = get_string('error:nojobassignmentselectedactivity', 'facetoface');
                    $mform->addElement('html', html_writer::tag('div', $msg));
                } else {
                    $this->enrol_totara_facetoface_addsessrows($mform, $sessions, $seminar, $force);
                    $sessionsavailable = true;
                }
                $mform->addElement('html', html_writer::end_div());
            }
            $mform->addElement('html', html_writer::end_div());

            if ($sessionsavailable && count($sessrows) == 1) {
                $mform->addRule("sid[$facetofaceid]", null, 'required', null, 'client');
            }
        }

        if ($sessionsavailable) {
            $notificationdisabled = get_config(null, 'facetoface_notificationdisable');
            if (empty($notificationdisabled)) {
                $options = array(MDL_F2F_BOTH => get_string('notificationboth', 'facetoface'),
                    MDL_F2F_TEXT => get_string('notificationemail', 'facetoface'),
                    MDL_F2F_NONE => get_string('notificationnone', 'facetoface'),
                );
                $mform->addElement('select', 'notificationtype', get_string('notificationtype', 'facetoface'), $options);
                $mform->addHelpButton('notificationtype', 'notificationtype', 'facetoface');
                $mform->addRule('notificationtype', null, 'required', null, 'client');
                $mform->setDefault('notificationtype', MDL_F2F_BOTH);
            } else {
                $mform->addElement('hidden', 'notificationtype', MDL_F2F_NONE);
            }
            $mform->setType('notificationtype', PARAM_INT);
        }

        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            global $CFG;
            $url = $CFG->wwwroot . '/theme/yui_combo.php?m/-1/mod_facetoface/signupform/signupform.js';
            $mform->addElement('hidden', 'eventhandlers', $url);
            $mform->setType('eventhandlers', PARAM_URL);

            $url = $CFG->wwwroot . '/enrol/totara_facetoface/js/signup_tsandcs.js';
            $mform->addElement('html', '<script src=' . $url . '></script>');
        } else {
            global $PAGE;
            $PAGE->requires->strings_for_js(array('selfapprovaltandc', 'close'), 'mod_facetoface');
            $PAGE->requires->yui_module('moodle-mod_facetoface-signupform', 'M.mod_facetoface.signupform.init');
        }

        return $sessionsavailable;
    }

    /*
     * Add session rows to signup form
     * @param moodleform $mform
     * @param array $sessrows
     * @param seminar $seminar
     * @param bool $force Option "Do not sign up" will not be displayed if true
     */
    private function enrol_totara_facetoface_addsessrows($mform, $sessions, seminar $seminar, $force = false) {
        global $DB, $PAGE;

        $mform->addElement('html', html_writer::start_tag('table'));
        $mform->addElement('html', html_writer::start_tag('thead'));
        $mform->addElement('html', html_writer::start_tag('tr'));
        $mform->addElement('html', html_writer::tag('th', get_string('selectsession', 'enrol_totara_facetoface'))); // No title for radio button col.
        $mform->addElement('html', html_writer::tag('th', get_string('sessiondatetime', 'facetoface')));
        $mform->addElement('html', html_writer::tag('th', get_string('room', 'facetoface')));
        $mform->addElement('html', html_writer::tag('th', get_string('additionalinformation', 'enrol_totara_facetoface')));
        $mform->addElement('html', html_writer::end_tag('tr'));
        $mform->addElement('html', html_writer::end_tag('thead'));
        $mform->addElement('html', html_writer::start_tag('tbody'));

        // "Do not sign up" option.
        if (!$force) {
            $mform->addElement('html', html_writer::start_tag('tr') . html_writer::start_tag('td', array('class' => 'session-select')));
            $mform->addElement('radio', "sid[{$seminar->get_id()}]", '', '', 0);
            $mform->addElement('html', html_writer::end_tag('td') . html_writer::start_tag('td', array('class' => 'session-dates')));
            $mform->addElement('html', get_string('donotsignup', 'enrol_totara_facetoface'));
            $mform->addElement('html', html_writer::end_tag('td') . html_writer::end_tag('tr'));
        }

        foreach ($sessions as $session) {
            $sid = $session->id;

            $mform->addElement('html', html_writer::start_tag('tr'));

            $mform->addElement('html', html_writer::start_tag('td', array('class' => 'session-select')));
            $mform->addElement('radio', "sid[{$seminar->get_id()}]", '', '', $sid);
            $mform->addElement('html', html_writer::end_tag('td'));

            // Dates/times.
            if ($session->cntdates) {
                $allsessiondates = html_writer::start_tag('ul', array('class' => 'unlist'));
                foreach ($session->sessiondates as $date) {
                    $allsessiondates .= html_writer::start_tag('li');

                    $sessionobj = facetoface_format_session_times($date->timestart, $date->timefinish, $date->sessiontimezone);
                    if ($sessionobj->startdate == $sessionobj->enddate) {
                        $allsessiondates .= $sessionobj->startdate;
                    } else {
                        $allsessiondates .= $sessionobj->startdate . ' - ' . $sessionobj->enddate;
                    }
                    $allsessiondates .= ', ' . $sessionobj->starttime . ' - ' . $sessionobj->endtime . ' ' . $sessionobj->timezone;

                    $allsessiondates .= html_writer::end_tag('li');
                }

                $allsessiondates .= html_writer::end_tag('ul');
            } else {
                $allsessiondates = get_string('wait-listed', 'facetoface');
            }
            $mform->addElement('html', html_writer::tag('td', $allsessiondates, array('class' => 'session-dates')));

            // Room.
            if (isset($session->room)) {
                $roomhtml = facetoface_room_html($session->room, $PAGE->url);
            } else {
                $roomhtml = '';
            }
            $mform->addElement('html', html_writer::tag('td', $roomhtml, array('class' => 'session-room')));

            // Signup information.
            $mform->addElement('html', html_writer::start_tag('td', array('class' => 'session-signupinfo')));

            $elementid = 'discountcode' . $session->id;
            if (!get_config(null, 'facetoface_hidediscount') && $session->discountcost > 0) {
                $mform->addElement('text', $elementid, get_string('discountcode', 'facetoface'), 'size="6"');
                $mform->addHelpButton($elementid, 'discountcodelearner', 'facetoface');
            } else {
                $mform->addElement('hidden', $elementid, '');
            }
            $mform->setType($elementid, PARAM_TEXT);

            // Signup note.

            // Get list of signup customfields.
            $signupfields = $DB->get_records('facetoface_signup_info_field');

            foreach ($signupfields as $signupfield) {
                // Currently we only support signup note.
                if ($signupfield->shortname == 'signupnote') {
                    $elementid = $signupfield->shortname . $session->id;

                    $mform->addElement('text', $elementid, $signupfield->fullname);
                    $mform->setType($elementid, PARAM_TEXT);
                }
            }

            // Display T&Cs for self approval.
            if ($seminar->get_approvaltype() == seminar::APPROVAL_SELF) {
                $elementname = 'selfapprovaltandc_' . $seminar->get_id();
                $selfapprovaljsparams[$elementname] = $seminar->get_approvalterms();

                $url = new moodle_url('/enrol/totara_facetoface/ajax/signup_tsandcs.php', array('s' => $session->id));
                $attributes = array("class" => "tsandcs ajax-action");
                $tandcurl = html_writer::link($url, get_string('selfapprovalsoughtbrief', 'mod_facetoface'), $attributes);
                $elementid = 'selfapprovaltc' . $session->id;
                $mform->addElement('checkbox', $elementid, $tandcurl);
            }

            mod_facetoface_signup_form::add_jobassignment_selector($mform, new seminar($seminar->get_id()));

            $mform->addElement('html', html_writer::end_tag('td'));

            $mform->addElement('html', html_writer::end_tag('tr'));
        }
        $mform->addElement('html', html_writer::end_tag('tbody'));
        $mform->addElement('html', html_writer::end_tag('table'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $mform = $this->_form;

        if (!empty($data['sid'])) {
            // User must submit to at least one session.
            $nosid = true;
            foreach ($data['sid'] as $sid) {
                if ($sid > 0) {
                    $nosid = false;
                }
                $elementid = 'selfapprovaltc' . $sid;
                if ($mform->elementExists($elementid)) {
                    if (empty($data[$elementid])) {
                        $errors[$elementid] = get_string('required');
                    }
                }
            }
            if ($nosid) {
                $errors['signuperrorcontainer'] = get_string('error:choosession', 'enrol_totara_facetoface');
            }
        }
        return $errors;
    }
}
