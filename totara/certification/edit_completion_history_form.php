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

class certif_edit_completion_history_form extends moodleform {

    public function definition() {
        global $CERTIFSTATUS, $CERTIFRENEWALSTATUS, $CERTIFPATH;

        $mform =& $this->_form;

        $id = $this->_customdata['id'];
        $userid = $this->_customdata['userid'];
        $showinitialstateinvalid = $this->_customdata['showinitialstateinvalid'];
        $certification = $this->_customdata['certification'];
        $chid = $this->_customdata['chid'];
        $currentlyassigned = $this->_customdata['assigned'];

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'chid', $chid);
        $mform->setType('chid', PARAM_INT);
        $mform->addElement('hidden', 'recertifydatetype', $certification->recertifydatetype);
        $mform->setType('recertifydatetype', PARAM_INT);
        $mform->addElement('hidden', 'showinitialstateinvalid', $showinitialstateinvalid);
        $mform->setType('showinitialstateinvalid', PARAM_INT);
        $mform->addElement('hidden', 'currentlyassigned', $currentlyassigned);
        $mform->setType('currentlyassigned', PARAM_INT);

        // Current completion.
        $mform->addElement('header', 'currentcompletionrecord', get_string('historycompletionrecord', 'totara_program'));

        $yesnooptions = array();
        $yesnooptions[0] = get_string('no');
        $yesnooptions[1] = get_string('yes');

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

        $mform->addElement('static', 'certificationactiveperiod',
            get_string('completioncertificationactiveperiod', 'totara_certification'),
            $certification->activeperiod);

        $mform->addElement('static', 'certificationwindowperiod',
            get_string('completioncertificationwindowperiod', 'totara_certification'),
            $certification->windowperiod);

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

        $mform->addElement('select', 'unassigned',
            get_string('completionunassigned', 'totara_certification'), $yesnooptions);
        $mform->setType('unassigned', PARAM_INT);
        $mform->addHelpButton('unassigned', 'completionunassigned', 'totara_certification');
        $mform->disabledIf('unassigned', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'savechanges', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->disabledIf('savechanges', 'state', 'eq', CERTIFCOMPLETIONSTATE_INVALID);
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
        global $DB;

        $errors = parent::validation($data, $files);

        $certcompletion = certif_process_submitted_edit_completion_history((object)$data);
        $state = certif_get_completion_state($certcompletion);
        $rawerrors = certif_get_completion_errors($certcompletion, null);
        $completionerrors = certif_get_completion_form_errors($rawerrors);

        // Verify that the submitted $data['state'] matches the calculated $state (ignore if it's already in invalid state).
        if ($state != CERTIFCOMPLETIONSTATE_INVALID && $state != $data['state']) {
            $errors['state'] = get_string('error:impossibledatasubmitted', 'totara_program');
        }

        return array_merge($errors, $completionerrors);
    }
}
