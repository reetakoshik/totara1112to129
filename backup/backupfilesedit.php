<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage backup files
 * @package   moodlecore
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once(__DIR__ . '/backupfilesedit_form.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/repository/lib.php');

$contextid = required_param('contextid', PARAM_INT);
$component  = required_param('component', PARAM_COMPONENT);
$filearea   = required_param('filearea', PARAM_AREA);

list($currentcontext, $course, $cm) = get_context_info_array($contextid);
$context = context::instance_by_id($contextid);
require_login($course, false, $cm);

// Use the same access control as restorefile.php!
if (!has_capability('moodle/backup:downloadfile', $context)) {
    require_capability('moodle/restore:restorefile', $context);
}

$returnurl = new moodle_url('/backup/restorefile.php', array('contextid' => $contextid));
$url = new moodle_url('/backup/backupfilesedit.php', array('contextid' => $contextid, 'component' => $component, 'filearea' => $filearea));

if ($component === 'backup') {
    require_capability('moodle/backup:managebackupfiles', $context);
    $filecontext = $context;
    if ($filearea !== 'course' and $filearea !== 'activity') {
        throw new invalid_parameter_exception('invalid filearea parameter');
    }
} else if ($component === 'user') {
    $filecontext = context_user::instance($USER->id);
    if ($filearea !== 'backup') {
        throw new invalid_parameter_exception('invalid filearea parameter');
    }
} else {
    throw new invalid_parameter_exception('invalid component parameter');
}

navigation_node::override_active_url(new moodle_url('/backup/restorefile.php', array('contextid' => $contextid)));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('managefiles', 'backup'));
$PAGE->set_heading(get_string('managefiles', 'backup'));
$PAGE->set_pagelayout('admin');

if ($component === 'backup' and $filearea === 'course') {
    $PAGE->navbar->add(get_string('choosefilefromcoursebackup', 'backup'));
} else if ($component === 'backup' and $filearea === 'activity') {
    $PAGE->navbar->add(get_string('choosefilefromactivitybacku', 'backup'));
} else if ($component === 'user' and $filearea === 'backup') {
    $PAGE->navbar->add(get_string('choosefilefromuserbackup', 'backup'));
}

$browser = get_file_browser();

$currentdata = (object)array('contextid' => $contextid, 'filearea' => $filearea, 'component' => $component);
$options = array('subdirs'=>0, 'maxfiles'=>-1, 'accepted_types'=>'*', 'return_types'=>FILE_INTERNAL);
if ($component === 'user') {
    $options['subdirs'] = 1;
}
file_prepare_standard_filemanager($currentdata, 'files', $options, $filecontext, $component, $filearea, 0);
$form = new backup_files_edit_form(null, array('currentdata' => $currentdata, 'options' => $options));

if ($form->is_cancelled()) {
    redirect($returnurl);
}

$data = $form->get_data();
if ($data) {
    $formdata = file_postupdate_standard_filemanager($data, 'files', $options, $filecontext, $component, $filearea, 0);
    redirect($returnurl);
}

echo $OUTPUT->header();

if ($component === 'backup' and $filearea === 'course') {
    echo $OUTPUT->heading_with_help(get_string('choosefilefromcoursebackup', 'backup'), 'choosefilefromcoursebackup', 'backup');
} else if ($component === 'backup' and $filearea === 'activity') {
    echo $OUTPUT->heading_with_help(get_string('choosefilefromactivitybackup', 'backup'), 'choosefilefromactivitybackup', 'backup');
} else if ($component === 'user' and $filearea === 'backup') {
    echo $OUTPUT->heading_with_help(get_string('choosefilefromuserbackup', 'backup'), 'choosefilefromuserbackup', 'backup');
}

echo $OUTPUT->container_start();
$form->display();
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
