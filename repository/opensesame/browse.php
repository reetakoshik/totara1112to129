<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

require(__DIR__ . '/../../config.php');
require_once("$CFG->dirroot/lib/adminlib.php");
require_once("$CFG->dirroot/repository/lib.php");

admin_externalpage_setup('opensesamebrowse');

$opensesame = core_plugin_manager::instance()->get_plugin_info('repository_opensesame');
if (!$opensesame->is_enabled()) {
    redirect(new moodle_url('/'));
}

$config = get_config('repository_opensesame');
if (empty($config->tenantkey)) {
    redirect(new moodle_url('/'));
}

if ($config->tenantkey !== get_user_preferences('opensesameconfirmbrowse', '')) {
    $confirmform = new repository_opensesame_form_confirm_browse();

    if ($confirmform->is_cancelled()) {
        redirect(new moodle_url('/'));
    } else if ($confirmform->get_data()) {
        set_user_preference('opensesameconfirmbrowse', $config->tenantkey);
        redirect($PAGE->url);
    }

    echo $OUTPUT->header();
    $confirmform = new repository_opensesame_form_confirm_browse(null, $config);
    $confirmform->display();
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();

$result = \repository_opensesame\local\opensesame_com::provision_user($USER);

if ($result['status'] !== 'success') {
    echo $OUTPUT->notification(get_string('erroropensesameconnection', 'repository_opensesame'));
    if (!empty($result['message'])) {
        if (!debugging($result['message'], DEBUG_DEVELOPER)) {
            error_log('error opening opensesame catalogue: ' . $result['message']);
        }
    }
    echo $OUTPUT->footer();
    die;
}

$a = new stdClass();
$a->firstname = $result['data']['FirstName'];
$a->lastname = $result['data']['LastName'];
$a->email = $result['data']['Email'];
$a->userid = $result['data']['UserId'];

$userlaunchurl = $result['data']['UserLaunchUrl'];

$certificate = \repository_opensesame\local\opensesame_com::get_catalogue_certificate();
$callback = \repository_opensesame\local\opensesame_com::get_catalogue_callback_url();

$url = new moodle_url($userlaunchurl, array('Certificate' => $certificate, 'PullContentSyncUrl' => $callback, 'version' => 2));
\repository_opensesame\event\catalogue_accessed::create()->trigger();

echo $OUTPUT->heading(get_string('catalogueheading', 'repository_opensesame', $a));

echo html_writer::tag('iframe', '', array('src' => $url, 'id' => 'opensesamebrowse', 'style' => 'width: 100%; height: 600px'));
$PAGE->requires->js_init_call('M.util.init_maximised_embed', array('opensesamebrowse'), true);

echo $OUTPUT->footer();
