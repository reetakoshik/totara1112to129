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
 * @package mod_facetoface
 */

  //------------------------------------------------------------------
  // This is the "graphical" structure of the Facet-to-face module:
  //
  //                          facetoface_notifications
  //               +-------(CL, pk->id, fk->facetofaceid)
  //               |
  //               |
  //          facetoface                  facetoface_sessions
  //         (CL, pk->id)-------------(CL, pk->id, fk->facetoface)
  //                                          |  |  |  |
  //                                          |  |  |  |
  //            facetoface_signups------------+  |  |  |
  //        (UL, pk->id, fk->sessionid)          |  |  |
  //                     |                       |  |  |
  //         facetoface_signups_status           |  |  |
  //         (UL, pk->id, fk->signupid)          |  |  |
  //                                             |  |  |
  //                                             |  |  |
  //         facetoface_session_roles------------+  |  |
  //        (UL, pk->id, fk->sessionid)             |  |
  //                                                |  |
  //                                                |  |
  //    facetoface_session_info_field               |  |
  //          (SL, pk->id)  |                       |  |
  //                        |                       |  |
  //     facetoface_session_info_data---------------+  |
  //    (CL, pk->id, fk->sessionid, fk->fieldid)       |
  //                                                   |
  //                                    facetoface_sessions_dates
  //                                    (CL, pk->id, fk->session)
  //
  // Meaning: pk->primary key field of the table
  //          fk->foreign key to link with parent
  //          SL->system level info
  //          CL->course level info
  //          UL->user level info
  //
//------------------------------------------------------------------

class backup_facetoface_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $facetoface = new backup_nested_element('facetoface', array('id'), array(
            'name', 'intro', 'introformat', 'thirdparty', 'thirdpartywaitlist', 'display',
            'timecreated', 'timemodified', 'shortname', 'showoncalendar', 'usercalentry',
            'multiplesessions', 'completionstatusrequired', 'managerreserve', 'maxmanagerreserves',
            'reservecanceldays', 'reservedays', 'declareinterest', 'interestonlyiffull',
            'allowcancellationsdefault', 'cancellationscutoffdefault', 'selectjobassignmentonsignup',
            'forceselectjobassignment', 'approvaltype', 'approvalrole', 'approvalterms', 'approvaladmins',
            'multisignupfully', 'multisignuppartly', 'multisignupnoshow', 'multisignupmaximum', 'waitlistautoclean'
        ));

        $notifications = new backup_nested_element('notifications');
        $notification = new backup_nested_element('notification', array('id'), array(
            'type', 'conditiontype', 'scheduleunit', 'scheduleamount', 'scheduletime', 'ccmanager', 'managerprefix',
            'title', 'body', 'booked', 'waitlisted', 'cancelled', 'requested', 'templateid', 'status',
            'issent', 'timemodified', 'usermodified'));

        $sessions = new backup_nested_element('sessions');
        $session = new backup_nested_element('session', array('id'), array(
            'capacity', 'allowoverbook', 'waitlisteveryone',  'details', 'normalcost',
            'discountcost', 'allowcancellations', 'cancellationcutoff', 'timecreated', 'timemodified',
            'usermodified', 'selfapproval', 'mincapacity', 'cutoff', 'sendcapacityemail',
            'registrationtimestart', 'registrationtimefinish', 'cancelledstatus'));

        $session_roles = new backup_nested_element('session_roles');
        $session_role = new backup_nested_element('session_role', array('id'), array(
            'roleid', 'userid'));

        $customfields = new backup_nested_element('custom_fields');
        $customfield = new backup_nested_element('custom_field', array('id'), array(
            'field_name', 'field_type', 'field_data', 'paramdatavalue'));

        $sessioncancelfields = new backup_nested_element('sessioncancel_fields');
        $sessioncancelfield = new backup_nested_element('sessioncancel_field', array('id'), array(
            'field_name', 'field_type', 'field_data', 'paramdatavalue'));

        $signups = new backup_nested_element('signups');
        $signup = new backup_nested_element('signup', array('id'), array(
            'userid', 'discountcode', 'notificationtype', 'archived', 'bookedby',
            'jobassignmentid', 'managerid'));

        $signups_status = new backup_nested_element('signups_status');
        $signup_status = new backup_nested_element('signup_status', array('id'), array(
            'statuscode', 'superceded', 'grade', 'createdby', 'timecreated'));

        $signup_fields = new backup_nested_element('signup_fields');
        $signup_field  = new backup_nested_element('signup_field', array('id'), array(
            'field_name', 'field_type', 'field_data', 'paramdatavalue'));

        $cancellation_fields = new backup_nested_element('cancellation_fields');
        $cancellation_field  = new backup_nested_element('cancellation_field', array('id'), array(
            'field_name', 'field_type', 'field_data', 'paramdatavalue'));

        $sessions_dates = new backup_nested_element('sessions_dates');
        $sessions_date = new backup_nested_element('sessions_date', array('id'), array(
            'sessiontimezone', 'timestart', 'timefinish'));

        $room = new backup_nested_element('room', array('id'), array(
            'name', 'description', 'capacity', 'allowconflicts', 'custom', 'hidden', 'usercreated', 'usermodified', 'timecreated', 'timemodified'));
        // NOTE: we need to use different element names for custom fields because each type needs different SQL query.
        $room_fields = new backup_nested_element('room_fields');
        $room_field = new backup_nested_element('room_field', array('id'), array(
            'field_name', 'field_type', 'field_data', 'paramdatavalue'));

        $assets = new backup_nested_element('assets');
        $asset =  new backup_nested_element('asset', array('id'), array(
            'name', 'description', 'allowconflicts', 'custom', 'hidden', 'usercreated', 'usermodified', 'timecreated', 'timemodified'));
        $asset_fields = new backup_nested_element('asset_fields');
        $asset_field = new backup_nested_element('asset_field', array('id'), array(
            'field_name', 'field_type', 'field_data', 'paramdatavalue'));

        $interests = new backup_nested_element('interests');
        $interest = new backup_nested_element('interest', array('id'), array(
            'userid', 'timedeclared', 'reason'));

        // Build the tree (in the same order as above).
        $facetoface->add_child($notifications);
        $notifications->add_child($notification);

        $facetoface->add_child($sessions);
        $sessions->add_child($session);

        $session->add_child($session_roles);
        $session_roles->add_child($session_role);

        $session->add_child($customfields);
        $customfields->add_child($customfield);

        $session->add_child($sessioncancelfields);
        $sessioncancelfields->add_child($sessioncancelfield);

        $session->add_child($signups);
        $signups->add_child($signup);

        $signup->add_child($signups_status);
        $signups_status->add_child($signup_status);

        $signup->add_child($signup_fields);
        $signup_fields->add_child($signup_field);

        $signup->add_child($cancellation_fields);
        $cancellation_fields->add_child($cancellation_field);

        $session->add_child($sessions_dates);
        $sessions_dates->add_child($sessions_date);

        $sessions_date->add_child($room);
        $room->add_child($room_fields);
        $room_fields->add_child($room_field);

        $sessions_date->add_child($assets);
        $assets->add_child($asset);
        $asset->add_child($asset_fields);
        $asset_fields->add_child($asset_field);

        $facetoface->add_child($interests);
        $interests->add_child($interest);

        // Define sources (in the same order as above).
        $facetoface->set_source_table('facetoface', array('id' => backup::VAR_ACTIVITYID));

        $notification->set_source_table('facetoface_notification', array('facetofaceid' => backup::VAR_PARENTID));

        $session->set_source_table('facetoface_sessions', array('facetoface' => backup::VAR_PARENTID));

        if ($userinfo) {
            $session_role->set_source_table('facetoface_session_roles', array('sessionid' => backup::VAR_PARENTID));
        }

        $this->add_customfield_set_source($customfield, 'facetoface_session', 'facetofacesessionid');

        $this->add_customfield_set_source($sessioncancelfield, 'facetoface_sessioncancel', 'facetofacesessioncancelid');

        if ($userinfo) {
            $signup->set_source_table('facetoface_signups', array('sessionid' => backup::VAR_PARENTID));

            $signup_status->set_source_table('facetoface_signups_status', array('signupid' => backup::VAR_PARENTID));

            $this->add_customfield_set_source($signup_field, 'facetoface_signup', 'facetofacesignupid');

            $this->add_customfield_set_source($cancellation_field, 'facetoface_cancellation', 'facetofacecancellationid');
        }

        $sessions_date->set_source_table('facetoface_sessions_dates', array('sessionid' => backup::VAR_PARENTID));

        $room->set_source_sql("SELECT fr.*
                                 FROM {facetoface_room} fr
                                 JOIN {facetoface_sessions_dates} fsd  ON (fsd.roomid = fr.id)
                                WHERE fsd.id = :sessionsdateid",
            array('sessionsdateid' => backup::VAR_PARENTID));
        $this->add_customfield_set_source($room_field, 'facetoface_room', 'facetofaceroomid');

        $asset->set_source_sql("SELECT fa.*
                                  FROM {facetoface_asset} fa
                                  JOIN {facetoface_asset_dates} fad  ON (fad.assetid = fa.id)
                                 WHERE fad.sessionsdateid = :sessionsdateid",
            array('sessionsdateid' => backup::VAR_PARENTID));
        $this->add_customfield_set_source($asset_field, 'facetoface_asset', 'facetofaceassetid');

        if ($userinfo) {
            $interest->set_source_table('facetoface_interest', array('facetoface' => backup::VAR_PARENTID));
        }

        // Define id annotations (in the same order as above).
        $facetoface->annotate_ids('role', 'approvalrole');
        // File facetoface.approvaladmins is a comma separated list, we cannot annotate it automatically.

        $notification->annotate_ids('user', 'usermodified');

        $session_role->annotate_ids('role', 'roleid');
        $session_role->annotate_ids('user', 'userid');

        $session->annotate_ids('user', 'usermodified');

        $signup->annotate_ids('user', 'userid');
        $signup->annotate_ids('user', 'bookedby');
        $signup->annotate_ids('user', 'managerid');

        $signup_status->annotate_ids('user', 'createdby');

        $room->annotate_ids('user', 'usercreated');
        $room->annotate_ids('user', 'usermodified');

        $asset->annotate_ids('user', 'usercreated');
        $asset->annotate_ids('user', 'usermodified');

        $interest->annotate_ids('user', 'userid');

        // Define file annotations.
        $facetoface->annotate_files('mod_facetoface', 'intro', null); // Intro file area hasn't itemid.
        $session->annotate_files('mod_facetoface', 'session', 'id');

        $syscontext = context_system::instance();
        $room->annotate_files('mod_facetoface', 'room', 'id', $syscontext->id);
        $asset->annotate_files('mod_facetoface', 'asset', 'id', $syscontext->id);

        // Return the root element (facetoface), wrapped into standard activity structure
        return $this->prepare_activity_structure($facetoface);
    }

    /**
     * Add custom field source data to element.
     *
     * @param backup_nested_element $fieldelement
     * @param string $tableprefix
     * @param string $parentfield
     * @throws base_element_struct_exception
     */
    private function add_customfield_set_source(backup_nested_element $fieldelement, $tableprefix, $parentfield) {
        $fieldelement->set_source_sql(
            "SELECT d.id, f.shortname AS field_name, f.datatype AS field_type, d.data AS field_data, dp.value AS paramdatavalue
               FROM {{$tableprefix}_info_field} f
               JOIN {{$tableprefix}_info_data} d ON d.fieldid = f.id
          LEFT JOIN {{$tableprefix}_info_data_param} dp ON dp.dataid = d.id
              WHERE d.{$parentfield} = ?",
            array(backup::VAR_PARENTID));
    }
}
