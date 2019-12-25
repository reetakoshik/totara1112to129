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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

use totara_userdata\local\export;
use totara_userdata\local\export_type;

require('../../config.php');

$action = optional_param('action', '', PARAM_ALPHANUM);

require_login();
$usercontext = context_user::instance($USER->id);
require_capability('totara/userdata:exportself', $usercontext);

if (!get_config('totara_userdata', 'selfexportenable')) {
    redirect(new moodle_url('/user/profile.php', array('id' => $USER->id)));
}

$returnurl = new moodle_url('/totara/user/profile.php', array('id' => $USER->id));

$PAGE->set_context($usercontext);
$PAGE->set_url('/totara/userdata/export_request.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_pagetype('user-preferences');
$PAGE->set_title(get_string('exportrequest', 'totara_userdata'));
$PAGE->set_heading(fullname($USER));

// We are looking at our own profile.
$myprofilenode = $PAGE->settingsnav->find('myprofile', null);
$requestnode = $myprofilenode->add(get_string('exportrequest', 'totara_userdata'));
$requestnode->make_active();

// NOTE: cleanup is cheap, do it here to simplify following code.
export::internal_cleanup();

$exporttypes = \totara_userdata\userdata\manager::get_export_types('self');
if (!$exporttypes) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('exportrequest', 'totara_userdata'));
    echo $OUTPUT->notification(get_string('errornoexporttypes', 'totara_userdata'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    die;
}

// We allow only one export at a time, so show only the last one.
$lastexport = export::get_my_last_export();
$fileavailable = false;
if ($lastexport) {
    $fileavailable = export::is_export_file_available($lastexport);
}

$canexport = (!$fileavailable and (!$lastexport or $lastexport->result !== null));

if ($canexport) {
    $exportform = new \totara_userdata\form\export_type_request();
    if ($exportform->is_cancelled()) {
        redirect(new moodle_url('/user/profile.php', array('id' => $USER->id)));
    }
    if ($data = $exportform->get_data()) {
        export_type::trigger_self_export($data->exporttypeid);
        redirect($PAGE->url);
    }
}

if ($action === 'deletefile' and $fileavailable and $lastexport) {
    require_sesskey();
    \totara_userdata\local\export::delete_result_file($lastexport->id);
    redirect($PAGE->url, get_string('exportfiledeleted', 'totara_userdata'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('exportrequest', 'totara_userdata'));

if ($lastexport and $lastexport->result === null) {
    echo $OUTPUT->notification(get_string('exportrequestpending', 'totara_userdata'), \core\output\notification::NOTIFY_INFO);
}

if ($fileavailable) {
    // NOTE: Do no use button for downloads here, it is not compatible with file urls!
    $fileurl = moodle_url::make_pluginfile_url(SYSCONTEXTID, 'totara_userdata', 'export', $lastexport->id, '/', 'export.tgz');
    $deleteurl = new moodle_url($PAGE->url, array('action' => 'deletefile', 'sesskey' => sesskey()));
    $a = new stdClass();
    if (\core\session\manager::is_loggedinas()) {
        // No download for privacy reasons!
        $a->file = 'export.tgz' . $OUTPUT->action_icon($deleteurl, new \core\output\flex_icon('delete', array('alt' => get_string('delete'))));
    } else {
        $a->file = html_writer::link($fileurl, 'export.tgz') . $OUTPUT->action_icon($deleteurl, new \core\output\flex_icon('delete', array('alt' => get_string('delete'))));
    }
    $a->until = userdate($lastexport->timefinished + export::MAX_FILE_AVAILABILITY_TIME);
    echo markdown_to_html(get_string('exportfileready', 'totara_userdata', $a));

} else if ($canexport) {
    echo $exportform->render();
}

echo $OUTPUT->footer();
