<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

use gradereport_singleview\local\ui\empty_element;
use mod_facetoface\seminar_event;

defined('MOODLE_INTERNAL') || die();

/**
 * Remove users confirmation form
 */
class attendees_remove_confirm extends \moodleform {

    protected function definition() {
        $mform = & $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        // Only display notification checkboxes if they're active
        if ($this->_customdata['is_notification_active']) {
            $mform->addElement('header', 'notifications', get_string('notifications', 'facetoface'));

            $mform->addElement('advcheckbox', 'notifyuser', '', get_string('notifycancelleduser', 'facetoface'));
            $mform->setDefault('notifyuser', 1);

            $mform->addElement('advcheckbox', 'notifymanager', '', get_string('notifycancelledusermanager', 'facetoface'));
            $mform->setDefault('notifymanager', 1);
        }

        // Custom fields.
        if ($this->_customdata['enablecustomfields']) {
            $mform->addElement('header', 'cancellationfields', get_string('cancellationfields', 'facetoface'));
            $mform->addElement('static', 'cancellationfieldslimitation', '', get_string('cancellationfieldslimitation', 'facetoface'));
            $signup = new \stdClass();
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