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
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

use mod_facetoface\bulk_list;
use mod_facetoface\seminar_event;
use mod_facetoface\attendees_list_helper;
use mod_facetoface\form\attendees_remove_confirm;

// The number of users that should be shown per page.
define('USERS_PER_PAGE', 50);

$s      = required_param('s', PARAM_INT); // facetoface session ID
$listid = required_param('listid', PARAM_ALPHANUM); // Session key to list of users to add.
$page   = optional_param('page', 0, PARAM_INT); // Current page number.

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', ['id' => $seminar->get_course()]);
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

$returnurl  = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s, 'backtoallsessions' => 1));
$currenturl = new moodle_url('/mod/facetoface/attendees/list/removeconfirm.php', array('s' => $s, 'listid' => $listid, 'page' => $page));
// Check essential permissions.
require_login($course, false, $cm);
require_capability('mod/facetoface:removeattendees', $context);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

$list = new bulk_list($listid);
// Selected users.
$userlist = $list->get_user_ids();
if (empty($userlist)) {
    totara_set_notification(get_string('updateattendeesunsuccessful', 'facetoface'), $returnurl);
}

$isnotificationactive = facetoface_is_notification_active(MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION, $seminar->get_id(), true);
$mform = new attendees_remove_confirm(null, [
    's' => $s,
    'listid' => $listid,
    'enablecustomfields' => !$list->has_user_data(),
    'is_notification_active' => $isnotificationactive
]);
if ($mform->is_cancelled()) {
    $list->clean();
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    $fromform->users = $mform->get_user_list($userlist);
    attendees_list_helper::remove($fromform);
    redirect($returnurl);
}

$PAGE->set_title(format_string($seminar->get_name()));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('removeattendeestep2', 'facetoface'));

/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, false, false, true);

// Table.
$f2frenderer = $PAGE->get_renderer('mod_facetoface');
$f2frenderer->setcontext($context);

$users = $mform->get_user_list($userlist, $page, USERS_PER_PAGE);
$paging = new paging_bar(count($userlist), $page, USERS_PER_PAGE, $currenturl);

echo $f2frenderer->render($paging);
echo $f2frenderer->print_userlist_table($users);
echo $f2frenderer->render($paging);

$link = html_writer::link($list->get_returnurl(), get_string('changeselectedusers', 'facetoface'), [
    'class'=>'btn btn-default'
]);
echo html_writer::div($link,'form-group');

$mform->display();
echo $OUTPUT->footer();
