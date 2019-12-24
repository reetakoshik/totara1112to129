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
 * @package totara_program
 */

require_once($CFG->libdir . "/formslib.php");

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

class prog_edit_completion_form extends moodleform {

    public function definition() {
        global $OUTPUT;

        $mform =& $this->_form;

        $id = $this->_customdata['id'];
        $userid = $this->_customdata['userid'];
        $showinitialstateinvalid = $this->_customdata['showinitialstateinvalid'];
        $solution = $this->_customdata['solution'];

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'showinitialstateinvalid', $showinitialstateinvalid);
        $mform->setType('showinitialstateinvalid', PARAM_INT);
        $mform->addElement('hidden', 'timeduenotset');
        $mform->setType('timeduenotset', PARAM_ALPHA);

        // Current completion.
        $mform->addElement('header', 'currentcompletionrecord', get_string('currentcompletionrecord', 'totara_program'));

        if (!empty($solution)) {
            $mform->addElement('html', html_writer::div(html_writer::span($solution), 'notifyproblem problemsolution'));
        }

        $progstatusoptions = array();
        $progstatusoptions[-1] = get_string('error:stateinvalid', 'totara_program');
        $progstatusoptions[STATUS_PROGRAM_COMPLETE] = get_string('statusprogramcomplete', 'totara_program');
        $progstatusoptions[STATUS_PROGRAM_INCOMPLETE] = get_string('statusprogramincomplete', 'totara_program');
        $mform->addElement('select', 'status',
            get_string('completionprogstatus', 'totara_program'), $progstatusoptions);
        $mform->addHelpButton('status', 'completionprogstatus', 'totara_program');

        $mform->addElement('date_time_selector', 'timedue',
            get_string('completiontimedue', 'totara_program'), array('optional' => true));
        $mform->disabledIf('timedue', 'status', 'eq', -1);

        $mform->addElement('date_time_selector', 'timecompleted',
            get_string('completiontimecompleted', 'totara_program'), array('optional' => true));
        $mform->disabledIf('timecompleted', 'status', 'eq', -1);
        $mform->disabledIf('timecompleted', 'status', 'eqhide', STATUS_PROGRAM_INCOMPLETE);

        $mform->addElement('static', 'timecompletednotapplicable',
            get_string('completiontimecompleted', 'totara_program'),
            get_string('completiondatenotapplicable', 'totara_program'));

        $mform->addElement('static', 'datewarning', '', get_string('completionchangedatewarning', 'totara_program'));

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'savechanges', get_string('savechanges'),
            array('class' => 'savecompletionchangesbutton'));
        $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->disabledIf('savechanges', 'status', 'eq', -1);
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

        $rawerrors = prog_get_completion_errors((object)$data);
        $completionerrors = prog_get_completion_form_errors($rawerrors);

        return array_merge($errors, $completionerrors);
    }
}

class prog_edit_completion_history_and_transactions_form extends moodleform {

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
            $mform->addElement('html',
                html_writer::start_tag('p') .
                get_string('userhasnocompletionhistory', 'totara_program') .
                html_writer::end_tag('p')
            );
        } else {

            $mform->addElement('html',
                html_writer::start_tag('table') .
                html_writer::start_tag('tr') .
                html_writer::tag('th', get_string('completionid', 'totara_program')) .
                html_writer::tag('th', get_string('completiontimecompleted', 'totara_program')) .
                html_writer::tag('th', get_string('edit')) .
                html_writer::tag('th', get_string('delete')) .
                html_writer::end_tag('tr')
            );

            $stredit = get_string('edit');
            $strdelete = get_string('delete');

            foreach ($history as $record) {
                $editurl = new moodle_url('/totara/program/edit_completion_history.php',
                    array('id' => $id, 'userid' => $userid, 'chid' => $record->id));
                $editlink = html_writer::link($editurl, $OUTPUT->pix_icon('/t/edit', $stredit),
                    array('title' => $stredit, 'class' => 'editcompletionhistorybutton'));

                $deleteurl = new moodle_url('/totara/program/edit_completion.php',
                    array('id' => $id, 'userid' => $userid, 'chid' => $record->id, 'deletehistory' => '1'));
                $deletelink = html_writer::link($deleteurl, $OUTPUT->pix_icon('/t/delete', $strdelete),
                    array('title' => $strdelete, 'class' => 'deletecompletionhistorybutton'));

                $mform->addElement('html',
                    html_writer::start_tag('tr') .
                    html_writer::tag('td', $record->id) .
                    html_writer::tag('td', userdate($record->timecompleted)) .
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
            $mform->addElement('html',
                html_writer::start_tag('p') .
                get_string('transactionuserhasnone', 'totara_program') .
                html_writer::end_tag('p')
            );
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