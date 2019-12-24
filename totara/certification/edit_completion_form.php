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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_certification
 */

require_once($CFG->libdir . "/formslib.php");

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

class certif_edit_completion_form extends moodleform {

    public function definition() {
        global $CERTIFSTATUS, $CERTIFRENEWALSTATUS, $CERTIFPATH;

        $mform =& $this->_form;

        $id = $this->_customdata['id'];
        $userid = $this->_customdata['userid'];
        $showinitialstateinvalid = $this->_customdata['showinitialstateinvalid'];
        $certification = $this->_customdata['certification'];
        $originalstate = $this->_customdata['originalstate'];
        $showconfirm = !empty($this->_customdata['showconfirm']);
        $status = $this->_customdata['status'];
        $solution = $this->_customdata['solution'];

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'originalstate', $originalstate);
        $mform->setType('originalstate', PARAM_INT);
        $mform->addElement('hidden', 'recertifydatetype', $certification->recertifydatetype);
        $mform->setType('recertifydatetype', PARAM_INT);
        $mform->addElement('hidden', 'showinitialstateinvalid', $showinitialstateinvalid);
        $mform->setType('showinitialstateinvalid', PARAM_INT);
        $mform->addElement('hidden', 'timeduenotset');
        $mform->setType('timeduenotset', PARAM_ALPHA);

        // Current completion.
        $mform->addElement('header', 'currentcompletionrecord', get_string('currentcompletionrecord', 'totara_program'));

        $yesnooptions = array();
        $yesnooptions[0] = get_string('no');
        $yesnooptions[1] = get_string('yes');

        if (!empty($solution) && !$showconfirm) {
            $mform->addElement('html', html_writer::div(html_writer::span($solution), 'notifyproblem problemsolution'));
        }

        $stateoptions = array();
        $stateoptions[CERTIFCOMPLETIONSTATE_INVALID] = get_string('stateinvalid', 'totara_certification');
        $stateoptions[CERTIFCOMPLETIONSTATE_ASSIGNED] = get_string('stateassigned', 'totara_certification');
        $stateoptions[CERTIFCOMPLETIONSTATE_CERTIFIED] = get_string('statecertified', 'totara_certification');
        $stateoptions[CERTIFCOMPLETIONSTATE_WINDOWOPEN] = get_string('statewindowopen', 'totara_certification');
        $stateoptions[CERTIFCOMPLETIONSTATE_EXPIRED] = get_string('stateexpired', 'totara_certification');
        $mform->addElement('select', 'state',
            get_string('completionstate', 'totara_certification'), $stateoptions);
        $mform->addHelpButton('state', 'completionstate', 'totara_certification');

        $mform->addElement('select', 'inprogress',
            get_string('completioninprogress', 'totara_certification'), $yesnooptions);
        $mform->setType('inprogress', PARAM_INT);
        $mform->addHelpButton('inprogress', 'completioninprogress', 'totara_certification');
        $mform->disabledIf('inprogress', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_INVALID);
        $mform->disabledIf('inprogress', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_CERTIFIED);

        $mform->addElement('static', 'inprogressnotapplicable',
            get_string('completioninprogress', 'totara_certification'),
            get_string('completioninprogressnotapplicable', 'totara_certification'));
        $mform->addHelpButton('inprogressnotapplicable', 'completioninprogress', 'totara_certification');

        $statusoptions = array();
        foreach ($CERTIFSTATUS as $key => $value) {
            $statusoptions[$key] = get_string($value, 'totara_certification');
        }
        $mform->addElement('select', 'status',
            get_string('completioncertstatus', 'totara_certification'), $statusoptions);
        $mform->disabledIf('status', null);
        $mform->addHelpButton('status', 'completioncertstatus', 'totara_certification');

        $renewalstatusoptions = array();
        foreach ($CERTIFRENEWALSTATUS as $key => $value) {
            $renewalstatusoptions[$key] = get_string($value, 'totara_certification');
        }
        $mform->addElement('select', 'renewalstatus',
            get_string('completionrenewalstatus', 'totara_certification'), $renewalstatusoptions);
        $mform->disabledIf('renewalstatus', null);

        $certificationpathoptions = array();
        foreach ($CERTIFPATH as $key => $value) {
            $certificationpathoptions[$key] = get_string($value, 'totara_certification');
        }
        $mform->addElement('select', 'certifpath',
            get_string('completioncertificationpath', 'totara_certification'), $certificationpathoptions);
        $mform->disabledIf('certifpath', null);

        $mform->addElement('date_time_selector', 'timedue',
            get_string('completiontimedue', 'totara_program'), array('optional' => true));
        $mform->disabledIf('timedue', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);
        $mform->disabledIf('timedue', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_CERTIFIED);
        $mform->disabledIf('timedue', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_WINDOWOPEN);

        $mform->addElement('static', 'timeduesameasexpiry',
            get_string('completiontimedue', 'totara_program'),
            get_string('completiontimeduesameasexpiry', 'totara_certification'));

        $mform->addElement('date_time_selector', 'timecompleted',
            get_string('completiontimecompleted', 'totara_program'), array('optional' => true));
        $mform->disabledIf('timecompleted', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);
        $mform->disabledIf('timecompleted', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_ASSIGNED);
        $mform->disabledIf('timecompleted', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_EXPIRED);

        $mform->addElement('static', 'timecompletednotapplicable',
            get_string('completiontimecompleted', 'totara_program'),
            get_string('completiondatenotapplicable', 'totara_program'));

        $mform->addElement('date_time_selector', 'timewindowopens',
            get_string('completiontimewindowopens', 'totara_certification'), array('optional' => true));
        $mform->disabledIf('timewindowopens', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);
        $mform->disabledIf('timewindowopens', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_ASSIGNED);
        $mform->disabledIf('timewindowopens', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_EXPIRED);

        $mform->addElement('static', 'timewindowopensnotapplicable',
            get_string('completiontimewindowopens', 'totara_certification'),
            get_string('completiondatenotapplicable', 'totara_program'));

        $mform->addElement('date_time_selector', 'timeexpires',
            get_string('completiontimeexpires', 'totara_certification'), array('optional' => true));
        $mform->disabledIf('timeexpires', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);
        $mform->disabledIf('timeexpires', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_ASSIGNED);
        $mform->disabledIf('timeexpires', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_EXPIRED);

        $mform->addElement('static', 'timeexpiresnotapplicable',
            get_string('completiontimeexpires', 'totara_certification'),
            get_string('completiondatenotapplicable', 'totara_program'));

        $mform->addElement('date_time_selector', 'baselinetimeexpires',
            get_string('completionbaselinetimeexpires', 'totara_certification'), array('optional' => true));
        $mform->disabledIf('baselinetimeexpires', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);
        $mform->disabledIf('baselinetimeexpires',  'state', 'eqhide', CERTIFCOMPLETIONSTATE_ASSIGNED);
        $mform->disabledIf('baselinetimeexpires', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_EXPIRED);
        $mform->addHelpButton('baselinetimeexpires', 'completionbaselinetimeexpires', 'totara_certification');

        $mform->addElement('static', 'baselinetimeexpiresnotapplicable',
            get_string('completionbaselinetimeexpires', 'totara_certification'),
            get_string('completiondatenotapplicable', 'totara_program'));

        $activeperiod = explode(' ', $certification->activeperiod);
        $mform->addElement('static', 'certificationactiveperiod',
            get_string('completioncertificationactiveperiod', 'totara_certification'),
            get_string('period' . $activeperiod[1] . 's', 'totara_certification', $activeperiod[0]));

        $windowperiod = explode(' ', $certification->windowperiod);
        $mform->addElement('static', 'certificationwindowperiod',
            get_string('completioncertificationwindowperiod', 'totara_certification'),
            get_string('period' . $windowperiod[1] . 's', 'totara_certification', $windowperiod[0]));

        $mform->addElement('html',
            html_writer::tag('div', '', array('id' => 'preapparentactiveperiod', 'class' => 'hidden'))
        );

        $mform->addElement('static', 'apparentactiveperiod',
            get_string('completionapparentactiveperiod', 'totara_certification'),
            $certification->activeperiod);
        $mform->addHelpButton('apparentactiveperiod', 'completionapparentactiveperiod', 'totara_certification');

        $mform->addElement('html',
            html_writer::tag('div', '', array('id' => 'preapparentwindowperiod', 'class' => 'hidden'))
        );

        $mform->addElement('static', 'apparentwindowperiod',
            get_string('completionapparentwindowperiod', 'totara_certification'),
            $certification->windowperiod);
        $mform->addHelpButton('apparentwindowperiod', 'completionapparentwindowperiod', 'totara_certification');

        $progstatusoptions = array();
        if ($status == STATUS_COURSESET_INCOMPLETE) {
            $progstatusoptions[STATUS_COURSESET_INCOMPLETE] = get_string('statuscoursesetincomplete', 'totara_program');
        } else if ($status == STATUS_COURSESET_COMPLETE) {
            $progstatusoptions[STATUS_COURSESET_COMPLETE] = get_string('statuscoursesetcomplete', 'totara_program');
        }
        $progstatusoptions[STATUS_PROGRAM_COMPLETE] = get_string('statusprogramcomplete', 'totara_program');
        $progstatusoptions[STATUS_PROGRAM_INCOMPLETE] = get_string('statusprogramincomplete', 'totara_program');
        $mform->addElement('select', 'progstatus',
            get_string('completionprogstatus', 'totara_certification'), $progstatusoptions);
        $mform->disabledIf('progstatus', null);
        $mform->addHelpButton('progstatus', 'completionprogstatus', 'totara_certification');

        $mform->addElement('date_time_selector', 'progtimecompleted',
            get_string('completionprogtimecompleted', 'totara_certification'), array('optional' => true));
        $mform->disabledIf('progtimecompleted', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);
        $mform->disabledIf('progtimecompleted', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_ASSIGNED);
        $mform->disabledIf('progtimecompleted', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_WINDOWOPEN);
        $mform->disabledIf('progtimecompleted', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_CERTIFIED);
        $mform->disabledIf('progtimecompleted', 'state', 'eqhide', CERTIFCOMPLETIONSTATE_EXPIRED);
        $mform->addHelpButton('progtimecompleted', 'completionprogtimecompleted', 'totara_certification');

        $mform->addElement('static', 'progtimecompletednotapplicable',
            get_string('completionprogtimecompleted', 'totara_certification'),
            get_string('completiondatenotapplicable', 'totara_program'));
        $mform->addHelpButton('progtimecompletednotapplicable', 'completionprogtimecompleted', 'totara_certification');

        $mform->addElement('static', 'progtimecompletedsameascert',
            get_string('completionprogtimecompleted', 'totara_certification'),
            get_string('completionprogtimecompletedsameascert', 'totara_certification'));
        $mform->addHelpButton('progtimecompletedsameascert', 'completionprogtimecompleted', 'totara_certification');

        if (!$showconfirm) {
            // Standard first-time view.
            $mform->addElement('static', 'datewarning', '', get_string('completionchangedatewarning', 'totara_program'));

            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'savechanges', get_string('savechanges'),
                array('class' => 'savecompletionchangesbutton'));
            $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('cancel'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->disabledIf('savechanges', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);

            $mform->addElement('hidden', 'confirmsave', 0);
            $mform->setType('confirmsave', PARAM_TEXT);

        } else {
            // User clicked the save button. Show the confirmation controls. Lock the form data.
            $mform->freeze('state');
            $mform->freeze('inprogress');
            $mform->freeze('status');
            $mform->freeze('renewalstatus');
            $mform->freeze('certifpath');
            $mform->freeze('timedue');
            $mform->freeze('timecompleted');
            $mform->freeze('timewindowopens');
            $mform->freeze('timeexpires');
            $mform->freeze('baselinetimeexpires');
            $mform->freeze('progstatus');

            $mform->setExpanded('currentcompletionrecord', false); // Doesn't work, because session overrides (I think).

            $mform->addElement('header', 'break', get_string('confirm'));
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'confirmsave', get_string('savechanges'));
            $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('cancel'));
            $mform->addGroup($buttonarray, 'confirmarray', '', array(' '), false);
        }
    }

    /**
     * Carries out validation of submitted form values
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['timeduenotset'] == 'yes') {
            $data['timedue'] = COMPLETION_TIME_NOT_SET;
        }

        list($certcompletion, $progcompletion) = certif_process_submitted_edit_completion((object)$data);
        $state = certif_get_completion_state($certcompletion);
        $rawerrors = certif_get_completion_errors($certcompletion, $progcompletion);
        $completionerrors = certif_get_completion_form_errors($rawerrors);

        // Verify that the submitted $data['state'] matches the calculated $state (ignore if it's already in invalid state).
        if ($state != CERTIFCOMPLETIONSTATE_INVALID && $state != $data['state']) {
            $errors['state'] = get_string('error:impossibledatasubmitted', 'totara_program');
        }

        return array_merge($errors, $completionerrors);
    }
}

class certif_edit_completion_history_and_transactions_form extends moodleform {

    public function definition() {
        global $OUTPUT;

        $mform =& $this->_form;

        $id = $this->_customdata['id'];
        $userid = $this->_customdata['userid'];
        $history = $this->_customdata['history'];
        $transactions = $this->_customdata['transactions'];

        // Completion history.
        $mform->addElement('header', 'completionhistory', get_string('completionhistory', 'totara_program'));
        $mform->setExpanded('completionhistory', true);

        if (empty($history)) {
            $mform->addElement('html', html_writer::span(get_string('userhasnocompletionhistory', 'totara_program')));
        } else {

            $mform->addElement('html',
                html_writer::start_tag('table') .
                html_writer::start_tag('tr') .
                html_writer::tag('th', get_string('completionid', 'totara_program')) .
                html_writer::tag('th', get_string('completionhistorystate', 'totara_certification') .
                    $OUTPUT->help_icon('completionhistorystate', 'totara_certification', null)) .
                html_writer::tag('th', get_string('completiontimecompleted', 'totara_program')) .
                html_writer::tag('th', get_string('completiontimeexpires', 'totara_certification')) .
                html_writer::tag('th', get_string('completionunassigned', 'totara_certification') .
                    $OUTPUT->help_icon('completionunassigned', 'totara_certification', null)) .
                html_writer::tag('th', get_string('completionhasproblem', 'totara_program')) .
                html_writer::tag('th', get_string('edit')) .
                html_writer::tag('th', get_string('delete')) .
                html_writer::end_tag('tr')
            );

            $stredit = get_string('edit');
            $strdelete = get_string('delete');

            $stateoptions = array();
            $stateoptions[CERTIFCOMPLETIONSTATE_INVALID] = get_string('stateinvalid', 'totara_certification');
            $stateoptions[CERTIFCOMPLETIONSTATE_ASSIGNED] = get_string('stateassigned', 'totara_certification');
            $stateoptions[CERTIFCOMPLETIONSTATE_CERTIFIED] = get_string('statecertified', 'totara_certification');
            $stateoptions[CERTIFCOMPLETIONSTATE_WINDOWOPEN] = get_string('statewindowopen', 'totara_certification');
            $stateoptions[CERTIFCOMPLETIONSTATE_EXPIRED] = get_string('stateexpired', 'totara_certification');

            foreach ($history as $record) {
                $state = $stateoptions[$record->state];

                $timecompleted = empty($record->timecompleted) ? get_string('completiondatenotapplicable', 'totara_program') : userdate($record->timecompleted);
                $timeexpires = empty($record->timeexpires) ? get_string('completiondatenotapplicable', 'totara_program') : userdate($record->timeexpires);

                $editurl = new moodle_url('/totara/certification/edit_completion_history.php',
                    array('id' => $id, 'userid' => $userid, 'chid' => $record->id));
                $editlink = html_writer::link($editurl, $OUTPUT->pix_icon('/t/edit', $stredit),
                    array('title' => $stredit, 'class' => 'editcompletionhistorybutton'));

                $deleteurl = new moodle_url('/totara/certification/edit_completion.php',
                    array('id' => $id, 'userid' => $userid, 'chid' => $record->id, 'deletehistory' => '1'));
                $deletelink = html_writer::link($deleteurl, $OUTPUT->pix_icon('/t/delete', $strdelete),
                    array('title' => $strdelete, 'class' => 'deletecompletionhistorybutton'));

                $unassigned = $record->unassigned ? get_string('yes') : get_string('no');
                $haserrors = empty($record->errors) ? get_string('no') : get_string('yes');

                $mform->addElement('html',
                    html_writer::start_tag('tr') .
                    html_writer::tag('td', $record->id) .
                    html_writer::tag('td', $state) .
                    html_writer::tag('td', $timecompleted) .
                    html_writer::tag('td', $timeexpires) .
                    html_writer::tag('td', $unassigned) .
                    html_writer::tag('td', $haserrors) .
                    html_writer::tag('td', $editlink) .
                    html_writer::tag('td', $deletelink) .
                    html_writer::end_tag('tr')
                );
            }

            $mform->addElement('html',
                html_writer::end_tag('table')
            );
        }

        $mform->addElement('submit', 'addhistory', get_string('completionaddhistory', 'totara_program'));

        // Transactions.
        $mform->addElement('header', 'completiontransactions', get_string('completiontransactions', 'totara_program'));
        $mform->setExpanded('completiontransactions', true);

        if (empty($transactions)) {
            $mform->addElement('html', html_writer::span(get_string('transactionuserhasnone', 'totara_program')));
        } else {
            $mform->addElement('html',
                html_writer::start_tag('table') .
                html_writer::start_tag('tr') .
                html_writer::tag('th', get_string('transactiondatetime', 'totara_program')) .
                html_writer::tag('th', get_string('transactionuser', 'totara_program')) .
                html_writer::tag('th', get_string('description')) .
                html_writer::end_tag('tr')
            );

            foreach ($transactions as $record) {
                if ($record->changeuserid) {
                    $changeby = fullname($record);
                } else {
                    $changeby = get_string('cronautomatic', 'totara_program');
                }
                $mform->addElement('html',
                    html_writer::start_tag('tr') .
                    html_writer::tag('td', userdate($record->timemodified, get_string('strftimedateseconds', 'langconfig')) .
                        " ({$record->timemodified})") .
                    html_writer::tag('td', $changeby) .
                    html_writer::tag('td', $record->description) .
                    html_writer::end_tag('tr')
                );
            }

            $mform->addElement('html',
                html_writer::end_tag('table')
            );
        }
    }
}