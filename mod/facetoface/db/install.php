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
 * @package mod
 * @subpackage facetoface
 */

function xmldb_facetoface_install() {
    global $DB;

    // We need to validate the content of these language strings to make sure that they are not too long for the database field
    // they are about to be written to.
    $titles = array(
        'setting:defaultconfirmationsubjectdefault' => get_string('setting:defaultconfirmationsubjectdefault_v9', 'facetoface'),
        'setting:defaultwaitlistedsubjectdefault' => get_string('setting:defaultwaitlistedsubjectdefault_v9', 'facetoface'),
        'setting:defaultcancellationsubjectdefault' => get_string('setting:defaultcancellationsubjectdefault_v9', 'facetoface'),
        'setting:defaultdeclinesubjectdefault' => get_string('setting:defaultdeclinesubjectdefault_v9', 'facetoface'),
        'setting:defaultremindersubjectdefault' => get_string('setting:defaultremindersubjectdefault_v9', 'facetoface'),
        'setting:defaultrequestsubjectdefault' => get_string('setting:defaultrequestsubjectdefault_v9', 'facetoface'),
        'setting:defaultrolerequestsubjectdefault' => get_string('setting:defaultrolerequestsubjectdefault', 'facetoface'),
        'setting:defaultadminrequestsubjectdefault' => get_string('setting:defaultadminrequestsubjectdefault', 'facetoface'),
        'setting:defaultdatetimechangesubjectdefault' => get_string('setting:defaultdatetimechangesubjectdefault_v9', 'facetoface'),
        'setting:defaulttrainerconfirmationsubjectdefault' => get_string('setting:defaulttrainerconfirmationsubjectdefault_v9', 'facetoface'),
        'setting:defaulttrainersessioncancellationsubjectdefault' => get_string('setting:defaulttrainersessioncancellationsubjectdefault_v9', 'facetoface'),
        'setting:defaulttrainersessionunassignedsubjectdefault' => get_string('setting:defaulttrainersessionunassignedsubjectdefault_v9', 'facetoface'),
        'setting:defaultcancelreservationsubjectdefault' => get_string('setting:defaultcancelreservationsubjectdefault_v9', 'facetoface'),
        'setting:defaultcancelallreservationssubjectdefault' => get_string('setting:defaultcancelallreservationssubjectdefault_v9', 'facetoface'),
        'setting:defaultsessioncancellationsubjectdefault' => get_string('setting:defaultsessioncancellationsubjectdefault', 'facetoface'),
        'setting:defaultregistrationexpiredsubjectdefault' => get_string('setting:defaultregistrationexpiredsubjectdefault', 'facetoface'),
        'setting:defaultpendingreqclosuresubjectdefault' => get_string('setting:defaultpendingreqclosuresubjectdefault', 'facetoface'),
        'setting:defaultwaitlistautocleansubjectdefault' => get_string('setting:defaultwaitlistautocleansubjectdefault', 'facetoface'),
        'setting:defaultundercapacitysubjectdefault' => get_string('setting:defaultundercapacitysubjectdefault', 'facetoface'),
    );

    foreach ($titles as $key => $title) {
        if (core_text::strlen($title) > 255) {
            // We choose to truncate here. If we throw an exception like we should then the user won't be able to add face to face
            // sessions and the user may not be able to edit the language pack to fix it. Thus we truncate and debug.
            $titles[$key] = core_text::substr($title, 0, 255);
            debugging('Error: A face to face notification title was truncated due to its length: ' . $key, DEBUG_NORMAL);
        }
    }

    //Create default notification templates
    $tpl_confirmation = new stdClass();
    $tpl_confirmation->status = 1;
    $tpl_confirmation->reference = 'confirmation';
    $tpl_confirmation->title = $titles['setting:defaultconfirmationsubjectdefault'];
    $tpl_confirmation->body = text_to_html(get_string('setting:defaultconfirmationmessagedefault_v9', 'facetoface'));
    $tpl_confirmation->ccmanager = 1;
    $tpl_confirmation->managerprefix = text_to_html(get_string('setting:defaultconfirmationinstrmngrdefault_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_confirmation);

    $tpl_cancellation = new stdClass();
    $tpl_cancellation->status = 1;
    $tpl_cancellation->reference = 'cancellation';
    $tpl_cancellation->title = $titles['setting:defaultcancellationsubjectdefault'];
    $tpl_cancellation->body = text_to_html(get_string('setting:defaultcancellationmessagedefault_v9', 'facetoface'));
    $tpl_cancellation->ccmanager = 1;
    $tpl_cancellation->managerprefix = text_to_html(get_string('setting:defaultcancellationinstrmngrdefault_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_cancellation);

    $tpl_waitlist = new stdClass();
    $tpl_waitlist->status = 1;
    $tpl_waitlist->reference = 'waitlist';
    $tpl_waitlist->title = $titles['setting:defaultwaitlistedsubjectdefault'];
    $tpl_waitlist->body = text_to_html(get_string('setting:defaultwaitlistedmessagedefault_v9', 'facetoface'));
    $tpl_waitlist->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_waitlist);

    $tpl_reminder = new stdClass();
    $tpl_reminder->status = 1;
    $tpl_reminder->reference = 'reminder';
    $tpl_reminder->title = $titles['setting:defaultremindersubjectdefault'];
    $tpl_reminder->body = text_to_html(get_string('setting:defaultremindermessagedefault_v9', 'facetoface'));
    $tpl_reminder->ccmanager = 1;
    $tpl_reminder->managerprefix = text_to_html(get_string('setting:defaultreminderinstrmngrdefault_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_reminder);

    $tpl_request = new stdClass();
    $tpl_request->status = 1;
    $tpl_request->reference = 'request';
    $tpl_request->title = $titles['setting:defaultrequestsubjectdefault'];
    $tpl_request->body = text_to_html(get_string('setting:defaultrequestmessagedefault_v9', 'facetoface'));
    $tpl_request->ccmanager = 1;
    $tpl_request->managerprefix = text_to_html(get_string('setting:defaultrequestinstrmngrdefault_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_request);

    $tpl_rolerequest = new stdClass();
    $tpl_rolerequest->status = 1;
    $tpl_rolerequest->reference = 'rolerequest';
    $tpl_rolerequest->title = $titles['setting:defaultrolerequestsubjectdefault'];
    $tpl_rolerequest->body = text_to_html(get_string('setting:defaultrolerequestmessagedefault_v9', 'facetoface'));
    $tpl_rolerequest->ccmanager = 0;
    $tpl_rolerequest->managerprefix = text_to_html(get_string('setting:defaultrolerequestinstrmngrdefault_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_rolerequest);

    $tpl_adminrequest = new stdClass();
    $tpl_adminrequest->status = 1;
    $tpl_adminrequest->reference = 'adminrequest';
    $tpl_adminrequest->title = $titles['setting:defaultadminrequestsubjectdefault'];
    $tpl_adminrequest->body = text_to_html(get_string('setting:defaultadminrequestmessagedefault_v9', 'facetoface'));
    $tpl_adminrequest->ccmanager = 1;
    $tpl_adminrequest->managerprefix = text_to_html(get_string('setting:defaultadminrequestinstrmngrdefault_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_adminrequest);

    $tpl_decline = new stdClass();
    $tpl_decline->status = 1;
    $tpl_decline->reference = 'decline';
    $tpl_decline->title = $titles['setting:defaultdeclinesubjectdefault'];
    $tpl_decline->body = text_to_html(get_string('setting:defaultdeclinemessagedefault_v9', 'facetoface'));
    $tpl_decline->ccmanager = 0;
    $tpl_decline->managerprefix = text_to_html(get_string('setting:defaultdeclineinstrmngrdefault_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_decline);

    $tpl_timechange = new stdClass();
    $tpl_timechange->status = 1;
    $tpl_timechange->reference = 'timechange';
    $tpl_timechange->title = $titles['setting:defaultdatetimechangesubjectdefault'];
    $tpl_timechange->body = text_to_html(get_string('setting:defaultdatetimechangemessagedefault_v9', 'facetoface'));
    $tpl_timechange->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_timechange);

    $tpl_trainercancel = new stdClass();
    $tpl_trainercancel->status = 1;
    $tpl_trainercancel->reference = 'trainercancel';
    $tpl_trainercancel->title = $titles['setting:defaulttrainersessioncancellationsubjectdefault'];
    $tpl_trainercancel->body = text_to_html(get_string('setting:defaulttrainersessioncancellationmessagedefault_v9', 'facetoface'));
    $tpl_trainercancel->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_trainercancel);

    $tpl_trainerunassign = new stdClass();
    $tpl_trainerunassign->status = 1;
    $tpl_trainerunassign->reference = 'trainerunassign';
    $tpl_trainerunassign->title = $titles['setting:defaulttrainersessionunassignedsubjectdefault'];
    $tpl_trainerunassign->body = text_to_html(get_string('setting:defaulttrainersessionunassignedmessagedefault_v9', 'facetoface'));
    $tpl_trainerunassign->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_trainerunassign);

    $tpl_trainerconfirm = new stdClass();
    $tpl_trainerconfirm->status = 1;
    $tpl_trainerconfirm->reference = 'trainerconfirm';
    $tpl_trainerconfirm->title = $titles['setting:defaulttrainerconfirmationsubjectdefault'];
    $tpl_trainerconfirm->body = text_to_html(get_string('setting:defaulttrainerconfirmationmessagedefault_v9', 'facetoface'));
    $tpl_trainerconfirm->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_trainerconfirm);

    $tpl_allreservationcancel = new stdClass();
    $tpl_allreservationcancel->status = 1;
    $tpl_allreservationcancel->reference = 'allreservationcancel';
    $tpl_allreservationcancel->title = $titles['setting:defaultcancelallreservationssubjectdefault'];
    $tpl_allreservationcancel->body = text_to_html(get_string('setting:defaultcancelallreservationsmessagedefault_v9', 'facetoface'));
    $tpl_allreservationcancel->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_allreservationcancel);

    $tpl_reservationcancel = new stdClass();
    $tpl_reservationcancel->status = 1;
    $tpl_reservationcancel->reference = 'reservationcancel';
    $tpl_reservationcancel->title = $titles['setting:defaultcancelreservationsubjectdefault'];
    $tpl_reservationcancel->body = text_to_html(get_string('setting:defaultcancelreservationmessagedefault_v9', 'facetoface'));
    $tpl_reservationcancel->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_reservationcancel);

    $tpl_sessioncancel = new stdClass();
    $tpl_sessioncancel->reference = 'sessioncancellation';
    $tpl_sessioncancel->status = 1;
    $tpl_sessioncancel->title = $titles['setting:defaultsessioncancellationsubjectdefault'];
    $tpl_sessioncancel->body = text_to_html(get_string('setting:defaultsessioncancellationmessagedefault_v9', 'facetoface'));
    $tpl_sessioncancel->ccmanager = 0;
    $tpl_sessioncancel->managerprefix = text_to_html(get_string('setting:defaultsessioncancellationinstrmngrcopybelow', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_sessioncancel);

    $tpl_expired = new stdClass();
    $tpl_expired->reference = 'registrationexpired';
    $tpl_expired->status = 1;
    $tpl_expired->title = get_string('setting:defaultregistrationexpiredsubjectdefault', 'facetoface');
    $tpl_expired->body = text_to_html(get_string('setting:defaultregistrationexpiredmessagedefault_v9', 'facetoface'));
    $tpl_expired->ccmanager = 0;
    $tpl_expired->managerprefix = text_to_html(get_string('setting:defaultregistrationexpiredinstrmngr_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_expired);

    $tpl_regclose = new stdClass();
    $tpl_regclose->reference = 'registrationclosure';
    $tpl_regclose->status = 1;
    $tpl_regclose->title = get_string('setting:defaultpendingreqclosuresubjectdefault', 'facetoface');
    $tpl_regclose->body = text_to_html(get_string('setting:defaultpendingreqclosuremessagedefault_v9', 'facetoface'));
    $tpl_regclose->ccmanager = 1;
    $tpl_regclose->managerprefix = text_to_html(get_string('setting:defaultpendingreqclosureinstrmngrcopybelow_v92', 'facetoface'));
    $DB->insert_record('facetoface_notification_tpl', $tpl_regclose);

    $tpl_waitlistautoclean = new stdClass();
    $tpl_waitlistautoclean->status = 1;
    $tpl_waitlistautoclean->reference = 'waitlistautoclean';
    $tpl_waitlistautoclean->title = $titles['setting:defaultwaitlistautocleansubjectdefault'];
    $tpl_waitlistautoclean->body = text_to_html(get_string('setting:defaultwaitlistautocleanmessagedefault', 'facetoface'));
    $tpl_waitlistautoclean->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_waitlistautoclean);

    $tpl_undercapacity = new stdClass();
    $tpl_undercapacity->status = 1;
    $tpl_undercapacity->reference = 'undercapacity';
    $tpl_undercapacity->title = $titles['setting:defaultundercapacitysubjectdefault'];
    $tpl_undercapacity->body = text_to_html(get_string('setting:defaultundercapacitymessagedefault', 'facetoface'));
    $tpl_undercapacity->ccmanager = 0;
    $DB->insert_record('facetoface_notification_tpl', $tpl_undercapacity);

    // Setting room, building, and address as default filters.
    set_config('facetoface_calendarfilters', 'room_1');

    facetoface_create_signup_cancellation_customfield_notes();

    // Make sure the upgrade from TL-6962 doesn't run on already fixed data.
    $configparams = array(
        'plugin' => 'facetoface',
        'name' => 'upgrade_customfieldmigration_signup',
        'value' => 'done',
    );
    $DB->insert_record('config_plugins', $configparams);

    $configparams = array(
        'plugin' => 'facetoface',
        'name' => 'upgrade_customfieldmigration_cancellation',
        'value' => 'done',
    );
    $DB->insert_record('config_plugins', $configparams);

    facetoface_create_room_customfields();
}

function facetoface_create_room_customfields() {
    global $DB;

    // Create new 'Building' custom field.
    $buildingfielddata = new stdClass();
    $buildingfielddata->datatype = "text";
    $buildingfielddata->shortname = "building";
    $buildingfielddata->description = "";
    $buildingfielddata->sortorder = $DB->get_field(
        'facetoface_room_info_field',
        '(CASE WHEN MAX(sortorder) IS NULL THEN 0 ELSE MAX(sortorder) END) + 1',
        array()
    );
    $buildingfielddata->hidden = false;
    $buildingfielddata->locked = false;
    $buildingfielddata->required = false;
    $buildingfielddata->forceunique = false;
    $buildingfielddata->defaultdata = null;
    $buildingfielddata->param1 = null;
    $buildingfielddata->param2 = null;
    $buildingfielddata->param3 = null;
    $buildingfielddata->param4 = null;
    $buildingfielddata->param5 = null;
    $buildingfielddata->fullname = "Building";

    $DB->insert_record('facetoface_room_info_field', $buildingfielddata);

    // Create new 'Location' custom field.
    $locationfielddata = new stdClass();
    $locationfielddata->shortname = "location";
    $locationfielddata->datatype = "location";
    $locationfielddata->description = "";
    $locationfielddata->sortorder = $DB->get_field(
        'facetoface_room_info_field',
        '(CASE WHEN MAX(sortorder) IS NULL THEN 0 ELSE MAX(sortorder) END) + 1',
        []
    );
    $locationfielddata->hidden = false;
    $locationfielddata->locked = false;
    $locationfielddata->required = false;
    $locationfielddata->forceunique = false;
    $locationfielddata->defaultdata = null;
    $locationfielddata->param1 = null;
    $locationfielddata->param2 = null;
    $locationfielddata->param3 = null;
    $locationfielddata->param4 = null;
    $locationfielddata->param5 = null;
    $locationfielddata->fullname = "Location";

    $DB->insert_record('facetoface_room_info_field', $locationfielddata);
}

/**
 * Create signup and cancellation default text notes.
 */
function facetoface_create_signup_cancellation_customfield_notes() {
    global $DB;

    // Clear data. This tables are new and should not contain any data.
    $DB->delete_records('facetoface_signup_info_field');
    $DB->delete_records('facetoface_cancellation_info_field');

    $data = new stdClass();
    $data->id = 0;
    $data->datatype = 'text';
    $data->shortname = 'signupnote';
    $data->fullname = 'Requests for session organiser';
    $data->description = '';
    $data->defaultdata = '';
    $data->forceunique = 0;
    $data->hidden = 0;
    $data->locked = 0;
    $data->required = 0;
    $data->sortorder = 1;
    $data->description_editor = array('text' => '', 'format' => 0);
    $signupinfofieldid = $DB->insert_record('facetoface_signup_info_field', $data);

    // Cancellation note default field.
    $data = new stdClass();
    $data->id = 0;
    $data->datatype = 'text';
    $data->shortname = 'cancellationnote';
    $data->fullname = 'Cancellation note';
    $data->description = '';
    $data->defaultdata = '';
    $data->forceunique = 0;
    $data->hidden = 0;
    $data->locked = 0;
    $data->required = 0;
    $data->sortorder = 1;
    $data->description_editor = array('text' => '', 'format' => 0);
    $cancellationinfofieldid = $DB->insert_record('facetoface_cancellation_info_field', $data);

    return array($signupinfofieldid,$cancellationinfofieldid);
}
