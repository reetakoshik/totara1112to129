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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');

$update = required_param('update', PARAM_INT);
$display = optional_param('display', '', PARAM_ALPHANUM);

if (!$cm = get_coursemodule_from_id('facetoface', $update)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

if (!$course = $DB->get_record("course", array('id' => $cm->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}

require_login($course, true, $cm); // needed to setup proper $COURSE
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);

if (!$facetoface = $DB->get_record('facetoface', array('id' => $cm->instance))) {
    print_error('error:incorrectcoursemodule', 'facetoface');
}

$url = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $cm->id));
$PAGE->set_url($url);

if ($display !== '' && !in_array($display, array(MDL_F2F_NOTIFICATION_MANUAL, MDL_F2F_NOTIFICATION_SCHEDULED, MDL_F2F_NOTIFICATION_AUTO))) {
    redirect($url);
    die();
}

$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('editinga', 'moodle', 'facetoface'));
$PAGE->set_heading(format_string($SITE->fullname));

$str_edit = get_string('edit', 'moodle');
$str_copy = get_string('copynotification', 'facetoface');
$str_delete = get_string('delete');
$str_active = get_string('setactive', 'facetoface');
$str_inactive  = get_string('setinactive', 'facetoface');
$str_warn_icon = $OUTPUT->pix_icon('i/warning', get_string('notificationduplicatesmessage', 'facetoface'), 'moodle');

$columns = array();
$headers = array();
$columns[] = 'title';
$headers[] = get_string('notificationtitle', 'facetoface');
$columns[] = 'recipients';
$headers[] = get_string('recipients', 'facetoface');
$columns[] = 'type';
$headers[] = get_string('type', 'facetoface');
$columns[] = 'status';
$headers[] = get_string('status', 'facetoface');
$columns[] = 'actions';
$headers[] = get_string('actions', 'facetoface');

$title = 'facetoface_notifications';
$table = new flexible_table($title);
$table->define_baseurl($url);
$table->define_columns($columns);
$table->define_headers($headers);
$table->set_attribute('class', 'generalbox mod-facetoface-notification-list');
$table->sortable(true, 'title');
$table->no_sorting('recipients');
$table->no_sorting('actions');
$table->setup();
$sort = $table->get_sql_sort();

// Get all notifications.
$notifications = $DB->get_records('facetoface_notification', array('facetofaceid' => $facetoface->id), $sort);
// Count the number of system notifications by type.
// We want to allow duplicate system notifications to be deleted.
$autonotifications = array();
$foundduplicates = false;
foreach ($notifications as $note) {
    if (!isset($autonotifications[$note->conditiontype])) {
        $autonotifications[$note->conditiontype] = 0;
    }
    if ($note->type != MDL_F2F_NOTIFICATION_AUTO) {
        continue;
    }
    $autonotifications[$note->conditiontype]++;
    if ($autonotifications[$note->conditiontype] > 1) {
        $foundduplicates = true;
    }
}
if ($foundduplicates) {
    totara_set_notification(get_string('notificationduplicatesfound', 'facetoface', $url->out()));
}
// Detect missing default notifications.
$defaultnotifications = facetoface_get_default_notifications($facetoface->id)[0];
foreach ($notifications as $note) {
    unset($defaultnotifications[$note->conditiontype]);
}

echo $OUTPUT->header();
$heading = get_string('notifications', 'mod_facetoface');
echo $OUTPUT->heading_with_help($heading, 'notifications', 'mod_facetoface');

if (!empty($defaultnotifications)) {
    $url1 = new moodle_url('/mod/facetoface/notification/restore.php', array('update' => $cm->id, 'sesskey' => sesskey()));
    $a['url1'] = $url1->out();
    $url2 = new moodle_url('/mod/facetoface/notification/template/index.php');
    $a['url2'] = $url2->out();
    $message = get_string('unavailablenotifications', 'facetoface', (object)$a);
    echo $OUTPUT->notification($message, \core\output\notification::NOTIFY_WARNING);
}

foreach ($notifications as $note) {
    $row = array();
    $buttons = array();

    // If its not an auto notification OR if it is but there are duplicates allow the notification to be deleted.
    $warn = ($note->type == MDL_F2F_NOTIFICATION_AUTO && $autonotifications[$note->conditiontype] > 1) ? $str_warn_icon : '';

    $row[] = $warn . format_string($note->title);

    // Create a notification object so we can figure out
    // the recipient string
    $notification = new facetoface_notification();
    $notification->booked = $note->booked;
    $notification->waitlisted = $note->waitlisted;
    $notification->cancelled = $note->cancelled;

    $row[] = $notification->get_recipient_description();

    //Type
    switch ($note->type) {
        case MDL_F2F_NOTIFICATION_MANUAL:
            $typestr = get_string('notificationtype_1', 'facetoface');
            break;

        case MDL_F2F_NOTIFICATION_SCHEDULED:
            $typestr = get_string('notificationtype_2', 'facetoface');
            break;

        case MDL_F2F_NOTIFICATION_AUTO:
            $typestr = get_string('notificationtype_4', 'facetoface');
            break;

        default:
            $typestr = '';
    }

    //Status
    if ($note->status == 1) {
        $statusstr = get_string('active');
    } else {
        $statusstr = get_string('inactive');
    }

    $row[] = $typestr;
    $row[] = $statusstr;

    $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/edit.php', array('f' => $facetoface->id, 'id' => $note->id)), new pix_icon('t/edit', $str_edit));

    if ($note->status == 1) {
        $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/status.php', array('update' => $update, 'id' => $note->id, 'status' => '0', 'sesskey' => sesskey())), new pix_icon('t/hide', $str_inactive));
    } else {
        $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/status.php', array('update' => $update, 'id' => $note->id, 'status' => '1', 'sesskey' => sesskey())), new pix_icon('t/show', $str_active));
    }

    if ($note->type != MDL_F2F_NOTIFICATION_AUTO) {
        $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/confirm.php', array('update' => $update, 'id' => $note->id, 'action' => 'copy', 'sesskey' => sesskey())), new pix_icon('t/copy', $str_copy));
    }

    // If its not an auto notification OR if it is but there are duplicates allow the notification to be deleted.
    if ($note->type != MDL_F2F_NOTIFICATION_AUTO || $autonotifications[$note->conditiontype] > 1) {
        $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/confirm.php', array('update' => $update, 'id' => $note->id, 'action' => 'delete', 'sesskey' => sesskey())), new pix_icon('t/delete', $str_delete));
    }

    $row[] = implode(' ', $buttons);

    $table->add_data($row);
}

$table->finish_html();

$addlink = new moodle_url('/mod/facetoface/notification/edit.php');

echo $OUTPUT->single_button(new moodle_url($addlink, array('f' => $cm->instance)), get_string('add'), 'get');
echo $OUTPUT->footer($course);
