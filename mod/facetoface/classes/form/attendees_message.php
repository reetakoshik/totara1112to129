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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class attendees_message extends \moodleform {

    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('header', 'recipientgroupsheader', get_string('messagerecipientgroups', 'facetoface'));

        // Display select recipient by status
        $statuses = array(
            \mod_facetoface\signup\state\user_cancelled::get_code(),
            \mod_facetoface\signup\state\event_cancelled::get_code(),
            \mod_facetoface\signup\state\waitlisted::get_code(),
            \mod_facetoface\signup\state\booked::get_code(),
            \mod_facetoface\signup\state\no_show::get_code(),
            \mod_facetoface\signup\state\partially_attended::get_code(),
            \mod_facetoface\signup\state\fully_attended::get_code()
        );

        $json_users = array();
        $attendees = array();
        foreach ($statuses as $status) {
            // Get count of users with this status
            $count = facetoface_get_num_attendees($this->_customdata['s'], $status, '=');

            if (!$count) {
                continue;
            }

            $users = facetoface_get_users_by_status($this->_customdata['s'], $status);
            $json_users[$status] = $users;
            $attendees = array_merge($attendees, $users);

            $state = \mod_facetoface\signup\state\state::from_code($status);
            $mform->addElement('checkbox', 'recipient_group['.$status.']', $state::get_string() . ' - ' . get_string('xusers', 'facetoface', $count), null, array('id' => 'id_recipient_group_'.$status));
        }

        // Display individual recipient selectors
        $mform->addElement('header', 'recipientsheader', get_string('messagerecipients', 'facetoface'));

        $options = array();
        foreach ($attendees as $a) {
            $options[$a->id] = fullname($a);
        }
        $mform->addElement('select', 'recipients', get_string('individuals', 'facetoface'), $options,  array('size' => 5));
        $mform->addElement('hidden', 'recipients_selected');
        $mform->setType('recipients_selected', PARAM_SEQUENCE);
        $mform->addElement('button', 'recipient_custom', get_string('editmessagerecipientsindividually', 'facetoface'));
        $mform->addElement('checkbox', 'cc_managers', get_string('messagecc', 'facetoface'));

        $mform->addElement('header', 'messageheader', get_string('messageheader', 'facetoface'));

        $mform->addElement('text', 'subject', get_string('messagesubject', 'facetoface'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'body', get_string('messagebody', 'facetoface'));
        $mform->setType('body', PARAM_CLEANHTML);
        $mform->addRule('body', get_string('required'), 'required', null, 'client');

        $json_users = json_encode($json_users);
        $mform->addElement('html', '<script type="text/javascript">var recipient_groups = '.$json_users.'</script>');

        // Add action buttons
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('sendmessage', 'facetoface'));
        $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('discardmessage', 'facetoface'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * If the form submitted then send messages to recipients.
     */
    public function send_message() {
        global $DB;

        $s = $this->_customdata['s'];
        $data = $this->get_submitted_data();

        // Get recipients list
        $recipients = array();
        if (!empty($data->recipient_group)) {
            foreach ($data->recipient_group as $key => $value) {
                if (!$value) {
                    continue;
                }
                $recipients = $recipients + facetoface_get_users_by_status($s, $key, 'u.id, u.*, su.jobassignmentid');
            }
        }

        // Get indivdual recipients
        if (empty($recipients) && !empty($data->recipients_selected)) {
            // Strip , prefix
            $data->recipients_selected = substr($data->recipients_selected, 1);
            $recipients = explode(',', $data->recipients_selected);
            list($insql, $params) = $DB->get_in_or_equal($recipients);
            $recipients = $DB->get_records_sql('SELECT * FROM {user} WHERE id ' . $insql, $params);
            if (!$recipients) {
                $recipients = array();
            }
        }

        // Send messages.
        $facetofaceuser = \mod_facetoface\facetoface_user::get_facetoface_user();

        $emailcount = 0;
        $emailerrors = 0;
        foreach ($recipients as $recipient) {
            $body = $data->body['text'];
            $bodyplain = html_to_text($body);

            if (email_to_user($recipient, $facetofaceuser, $data->subject, $bodyplain, $body) === true) {
                $emailcount += 1;

                // Are sending to managers
                if (empty($data->cc_managers)) {
                    continue;
                }

                // User have a manager assigned for the job assignment they signedup with (or all managers otherwise).
                $managers = array();
                if (!empty($recipient->jobassignmentid)) {
                    $ja = \totara_job\job_assignment::get_with_id($recipient->jobassignmentid);
                    if (!empty($ja->managerid)) {
                        $managers[] = $ja->managerid;
                    }
                } else {
                    $managers = \totara_job\job_assignment::get_all_manager_userids($recipient->id);
                }
                if (!empty($managers)) {
                    // Append to message.
                    $body = get_string('messagesenttostaffmember', 'facetoface', fullname($recipient))."\n\n".$data->body['text'];
                    $bodyplain = html_to_text($body);

                    foreach ($managers as $managerid) {
                        $manager = \core_user::get_user($managerid, '*', MUST_EXIST);
                        if (email_to_user($manager, $facetofaceuser, $data->subject, $bodyplain, $body) === true) {
                            $emailcount += 1;
                        }
                    }
                }
            } else {
                $emailerrors += 1;
            }
        }

        if ($emailcount) {
            if (!empty($data->cc_managers)) {
                $message = get_string('xmessagessenttoattendeesandmanagers', 'facetoface', $emailcount);
            } else {
                $message = get_string('xmessagessenttoattendees', 'facetoface', $emailcount);
            }
            $returnurl = new \moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s));
            totara_set_notification($message, $returnurl, array('class' => 'notifysuccess'));
        }

        if ($emailerrors) {
            $message = get_string('xmessagesfailed', 'facetoface', $emailerrors);
            $baseurl = new \moodle_url('/mod/facetoface/attendees/messageusers.php', array('s' => $s));
            totara_set_notification($message, $baseurl);
        }
    }
}
