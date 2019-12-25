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
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/notification/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/notification/edit_form.php');

// Parameters
$f = required_param('f', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

if (!$facetoface = $DB->get_record('facetoface', array('id' => $f))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}

if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

require_login($course, false, $cm); // needed to setup proper $COURSE
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);

$redirectto = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $cm->id));
$formurl = new moodle_url('/mod/facetoface/notification/edit.php', array('f' => $f, 'id' => $id));

// Load templates.
$templates = $DB->get_records('facetoface_notification_tpl', array('status' => 1));
$json_templates = json_encode($templates);
$args = array('args' => '{"templates":'.$json_templates.'}');

$jsmodule = array(
    'name' => 'totara_f2f_notification_template',
    'fullpath' => '/mod/facetoface/notification/get_template.js',
    'requires' => array('json', 'totara_core'));

$PAGE->requires->js_init_call('M.totara_f2f_notification_template.init', $args, false, $jsmodule);
// Setup page.
$PAGE->set_url($redirectto);

// Load data.
if ($id) {
    $notification = new facetoface_notification(array('id' => $id));
    if (!$notification) {
        print_error('error:notificationcouldnotbefound', 'facetoface');
    }
} else {
    $notification = new facetoface_notification();
}

// Setup editors
$editoroptions = array(
    'trusttext'=> 1,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $CFG->maxbytes,
    'context'  => $context,
);

$notification->bodyformat = FORMAT_HTML;
$notification->bodytrust  = 1;
$notification->managerprefixformat = FORMAT_HTML;
$notification->managerprefixtrust  = 1;
$notification = file_prepare_standard_editor($notification, 'body', $editoroptions, $context, 'mod_facetoface', 'notification', $id);
$notification = file_prepare_standard_editor($notification, 'managerprefix', $editoroptions, $context, 'mod_facetoface', 'notification', $id);

// Create form
$customdata = array(
    'templates'    => $templates,
    'notification' => $notification,
    'editoroptions'=> $editoroptions
);
$form = new mod_facetoface_notification_form($formurl, $customdata);
$form->set_data($notification);

// Process data
if ($form->is_cancelled()) {
    redirect($redirectto);
} else if ($data = $form->get_data()) {

    $data = file_postupdate_standard_editor($data, 'body', $editoroptions, $context, 'mod_facetoface', 'notification', $id);
    $data = file_postupdate_standard_editor($data, 'managerprefix', $editoroptions, $context, 'mod_facetoface', 'notification', $id);

    facetoface_notification::set_from_form($notification, $data);

    if ($notification->type != MDL_F2F_NOTIFICATION_AUTO) {
        if (!empty($data->booked)) {
            // If one of the booked radio boxes are selected then the value
            // will be taken from booked_type instead of booked (checkbox).
            $notification->booked = $data->booked_type;
        } else {
            $notification->booked = 0;
        }
    }

    $notification->courseid = $course->id;
    $notification->facetofaceid = $facetoface->id;
    $notification->ccmanager = (isset($data->ccmanager) ? 1 : 0);
    $notification->status = (isset($data->status) ? 1 : 0);
    $notification->templateid = $data->templateid;

    $notification->save();

    if ($data->templateid != 0) {
        // Double-check that the content is the same as the template - if customised then set template to 0.
        $default = $templates[$data->templateid];
        // Prepare default notification template.
        $default->bodytrust  = 1;
        $default->bodyformat = FORMAT_HTML;
        $default->managerprefixformat = FORMAT_HTML;
        $default->managerprefixtrust  = 1;
        $default = file_prepare_standard_editor($default, 'body', $editoroptions, $context, 'mod_facetoface', 'notification', $id);
        $default = file_prepare_standard_editor($default, 'managerprefix', $editoroptions, $context, 'mod_facetoface', 'notification', $id);

        // At this point, we have to filter the $data's text as well, since it is required to have the same formatted
        // filter as the default, so that the contents are the same when compared.
        // Importantly we also clone $data as this will lead to modifications.
        $clonedata = clone($data);
        $clonedata = file_prepare_standard_editor($clonedata, 'body', $editoroptions, $context, 'mod_facetoface', $id);
        $clonedata = file_prepare_standard_editor($clonedata, 'managerprefix', $editoroptions, $context, 'mod_facetoface', $id);

        // Double-check that the content is the same as the template - if customised then set template to 0.
        if (!facetoface_notification_match($clonedata, $default)) {
            $DB->set_field('facetoface_notification', 'templateid', 0, array('id' => $notification->id));
        }
        // Explicitly remove the clone, we don't want anyone to use it after this.
        unset($clonedata);
    }
    totara_set_notification(get_string('notificationsaved', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));
}

$pagetitle = format_string($facetoface->name);

if ($id) {
    $PAGE->navbar->add(get_string('edit', 'moodle'));
} else {
    $PAGE->navbar->add(get_string('add', 'moodle'));
}

$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);
echo $OUTPUT->header();

if ($id) {
    $notification_title = format_string($notification->title);
    echo $OUTPUT->heading(get_string('editnotificationx', 'facetoface', $notification_title));
} else {
    echo $OUTPUT->heading(get_string('addnotification', 'facetoface'));
}

// Check if form frozen, mention why
$isfrozen = $notification->is_frozen();
if ($isfrozen) {
    echo $OUTPUT->notification(get_string('notificationalreadysent', 'facetoface'));
}

$form->display();

if ($isfrozen) {
    echo $OUTPUT->container_start('continuebutton');
    $continueurl = clone($formurl);
    $continueurl->param('duplicate', 1);
    echo $OUTPUT->single_button($continueurl, get_string('duplicate'), 'get');
    echo $OUTPUT->single_button($redirectto, get_string('return', 'facetoface'), 'get');
    echo $OUTPUT->container_end();
}

echo $OUTPUT->footer($course);