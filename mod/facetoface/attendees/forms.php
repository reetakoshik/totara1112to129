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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Add user confirmation form
 */
class addconfirm_form extends moodleform {
    public function definition() {
        $mform = & $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'ignoreconflicts', $this->_customdata['ignoreconflicts']);
        $mform->setType('ignoreconflicts', PARAM_BOOL);

        // Only display notification checkboxes if they're active.
        if ($this->_customdata['is_notification_active']) {
            $mform->addElement('header', 'notifications', get_string('notifications', 'mod_facetoface'));

            $mform->addElement('advcheckbox', 'notifyuser', '', get_string('notifynewuser', 'mod_facetoface'));
            $mform->setDefault('notifyuser', 1);

            $mform->addElement('advcheckbox', 'notifymanager', '', get_string('notifynewusermanager', 'mod_facetoface'));
            $mform->setDefault('notifymanager', 1);
        }

        if ($this->_customdata['isapprovalrequired']) {
            $mform->addElement('header', 'bookingoptions', get_string('bookingoptions', 'mod_facetoface'));
            $mform->addElement('advcheckbox', 'ignoreapproval', '', get_string('ignoreapprovalwhenaddingattendees', 'mod_facetoface'));

            // Disabling suppress notification if approval required and not ignored.
            $mform->disabledIf('notifyuser', 'ignoreapproval', 'notchecked', 1);
            $mform->disabledIf('notifymanager', 'ignoreapproval', 'notchecked', 1);
        }

        // Custom fields.
        if ($this->_customdata['enablecustomfields']) {
            $mform->addElement('header', 'signupfields', get_string('signupfields', 'mod_facetoface'));
            $fileurl = new moodle_url('/mod/facetoface/attendees/addfile.php', array('s' => $this->_customdata['s']));
            $mform->addElement('static', 'signupfieldslimitation', '', get_string('signupfieldslimitation', 'mod_facetoface', $fileurl->out()));
            $signup = new stdClass();
            $signup->id = 0;
            customfield_definition($mform, $signup, 'facetofacesignup', 0, 'facetoface_signup', true);
        }

        $this->add_action_buttons(true, get_string('confirm'));
    }

    public function validation($data, $files) {
        $data['id'] = 0;
        return customfield_validation((object)$data, 'facetofacesignup', 'facetoface_signup');
    }

    public function get_user_list($userlist, $offset = 0, $limit = 0) {
        global $DB;

        $usernamefields = get_all_user_name_fields(true, 'u');
        list($idsql, $params) = $DB->get_in_or_equal($userlist, SQL_PARAMS_NAMED);
        $users = $DB->get_records_sql("
                    SELECT id, $usernamefields, email, idnumber, username
                      FROM {user} u
                     WHERE id " . $idsql . "
                  ORDER BY u.firstname, u.lastname", $params, $offset*$limit, $limit);

        return $users;
    }
}

/**
 * Remove users confirmation form
 */
class removeconfirm_form extends moodleform {
    public function definition() {
        $mform = & $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        // Only display notification checkboxes if they're active
        if ($this->_customdata['is_notification_active']) {
            $mform->addElement('header', 'notifications', get_string('notifications', 'mod_facetoface'));

            $mform->addElement('advcheckbox', 'notifyuser', '', get_string('notifycancelleduser', 'mod_facetoface'));
            $mform->setDefault('notifyuser', 1);

            $mform->addElement('advcheckbox', 'notifymanager', '', get_string('notifycancelledusermanager', 'mod_facetoface'));
            $mform->setDefault('notifymanager', 1);
        }

        // Custom fields.
        if ($this->_customdata['enablecustomfields']) {
            $mform->addElement('header', 'cancellationfields', get_string('cancellationfields', 'mod_facetoface'));
            $mform->addElement('static', 'cancellationfieldslimitation', '', get_string('cancellationfieldslimitation', 'mod_facetoface'));
            $signup = new stdClass();
            $signup->id = 0;
            customfield_definition($mform, $signup, 'facetofacecancellation', 0, 'facetoface_cancellation', true);
        }

        $this->add_action_buttons(true, get_string('confirm'));
    }

    public function validation($data, $files) {
        $data['id'] = 0;
        return customfield_validation((object)$data, 'facetofacecancellation', 'facetoface_cancellation');
    }

    public function get_user_list($userlist, $offset = 0, $limit = 0) {
        global $DB;

        $usernamefields = get_all_user_name_fields(true, 'u');
        list($idsql, $params) = $DB->get_in_or_equal($userlist, SQL_PARAMS_NAMED);
        $params['sessionid'] = $this->_customdata['s'];
        $users = $DB->get_records_sql("
                    SELECT u.id, $usernamefields, u.email, u.idnumber, u.username, count(fsid.id) as cntcfdata
                      FROM {user} u
                 LEFT JOIN {facetoface_signups} fs ON (fs.userid = u.id AND fs.sessionid = :sessionid)
                 LEFT JOIN {facetoface_signup_info_data} fsid ON (fsid.facetofacesignupid = fs.id)
                     WHERE u.id {$idsql}
                  GROUP BY u.id, $usernamefields, u.email, u.idnumber, u.username", $params, $offset*$limit, $limit);

        return $users;
    }
}

/**
 * Add users to facetoface session via input
 */
class facetoface_bulkadd_input_form extends moodleform {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $mform->addElement('header', 'addattendees', get_string('addattendees', 'mod_facetoface'));

        $options = array(
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
            'username' => get_string('username')
            );
        $mform->addElement('select', 'idfield', get_string('useridentifier', 'mod_facetoface'), $options);
        $mform->addelement('static', 'useraddcomment', get_string('userstoadd', 'mod_facetoface'), get_string('userstoaddcomment', 'mod_facetoface'));
        $mform->addElement('textarea', 'csvinput', '');

        $mform->addElement('advcheckbox', 'ignoreconflicts', get_string('allowscheduleconflicts', 'mod_facetoface'));
        $mform->setType('ignoreconflicts', PARAM_BOOL);

        $this->add_action_buttons(true, get_string('continue'));
    }
}

class facetoface_bulkadd_file_form extends moodleform {
    function definition() {
        $mform = $this->_form;

        // $customfieldinfo is used as $a in get_string().
        $customfieldinfo = new stdClass();
        $customfieldinfo->customfields = '';
        $customfieldinfo->requiredcustomfields = '';

        $dataoptional = get_string('dataoptional', 'mod_facetoface');

        $customfields = $this->_customdata['customfields'];
        $requiredcustomfields = $this->_customdata['requiredcustomfields'];
        $optionalfields = array_diff($customfields, $requiredcustomfields);

        if (!empty($requiredcustomfields)) {
            foreach ($requiredcustomfields as $item) {
                $customfieldinfo->customfields .= "* '{$item}'\n";
                $customfieldinfo->requiredcustomfields .= "* '{$item}'\n";
            }
        }

        if (!empty($optionalfields)) {
            foreach ($optionalfields as $item) {
                $customfieldinfo->customfields .= "* '{$item}' ({$dataoptional})\n";
            }
        }

        $extrafields = $this->_customdata['extrafields'];
        if (!empty($extrafields)) {
            foreach ($extrafields as $item) {
                $customfieldinfo->customfields .= "* '{$item}' ({$dataoptional})\n";
            }
        }

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $mform->addElement('header', 'addattendees', get_string('addattendees', 'mod_facetoface'));

        $fileoptions = array('accepted_types' => array('.csv'));
        $mform->addElement('filepicker', 'userfile', get_string('csvtextfile', 'mod_facetoface'), null, $fileoptions);
        $mform->setType('userfile', PARAM_FILE);
        $mform->addRule('userfile', null, 'required');

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'grades'), $encodings);

        $mform->addElement('advcheckbox', 'ignoreconflicts', get_string('allowscheduleconflicts', 'mod_facetoface'));
        $mform->setType('ignoreconflicts', PARAM_BOOL);

        $mform->addelement('html', format_text(get_string('csvtextfile_help', 'mod_facetoface', $customfieldinfo), FORMAT_MARKDOWN));

        $this->add_action_buttons(true, get_string('continue'));
    }
}
