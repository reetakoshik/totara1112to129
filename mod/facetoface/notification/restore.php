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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara
 * @subpackage mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');

$update  = required_param('update', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

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

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'facetoface');
}

$url = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $cm->id));
if ($confirm) {
    // Get all current notifications.
    $currentnotifications = $DB->get_records('facetoface_notification', array('facetofaceid' => $facetoface->id));

    // Recreate all default notifications.
    $defaultnotifications = facetoface_get_default_notifications($facetoface->id)[0];

    // Remove all defaults that exist already.
    foreach ($currentnotifications as $current) {
        unset($defaultnotifications[$current->conditiontype]);
    }

    // Create missing defaults.
    foreach ($defaultnotifications as $default) {
        $default->save();
    }
    totara_set_notification(get_string('notificationssuccessfullyreset', 'facetoface'), $url, array('class' => 'notifysuccess'));
}

$heading   = get_string('restoremissingdefaultnotifications', 'facetoface');
$actionurl = new moodle_url('/mod/facetoface/notification/restore.php', array('update' => $cm->id, 'confirm' => 1, 'sesskey' => sesskey()));
$actionstr = get_string('restoremissingdefaultnotificationsconfirm', 'facetoface', format_string($facetoface->name));

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('editinga', 'moodle', 'facetoface'));
$PAGE->navbar->add($heading);
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $OUTPUT->confirm($actionstr, $actionurl, $url);
echo $OUTPUT->footer($course);

