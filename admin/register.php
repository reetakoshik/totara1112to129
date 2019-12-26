<?php
/*
 * This file is part of Totara Learn
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
 * @package totara_core
 */

require_once('../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->dirroot/$CFG->admin/registerlib.php");
require_once("$CFG->dirroot/$CFG->admin/register_form.php");

$return = optional_param('return', '', PARAM_ALPHA);
$download = optional_param('download', 0, PARAM_INT);

// This page is hidden if registration is disabled via config.php.
admin_externalpage_setup('totararegistration', '', array('return' => $return));
require_capability('moodle/site:config', context_system::instance()); // Double check nobody changed the capability in settings.

if (!isset($CFG->registrationenabled)) {
    // Registration should have been enabled during install or upgrade!
    set_config('registrationenabled', 1);
}

if (!empty($CFG->sitetype) && $download) {
    $data = get_registration_data();
    $data['manualupdate'] = 1;
    $encrypted = encrypt_data(json_encode($data));
    send_file($encrypted, 'site_registration.ttr', null, 0, true, true);
}

// Init the form.
$data = get_registration_data();
$data['return'] = $return;
if (!isset($CFG->config_php_settings['registrationcode'])) {
    // Remove registration code if wwwroot changes.
    if (isset($CFG->registrationcodewwwhash) and $CFG->registrationcodewwwhash !== sha1($CFG->wwwroot)) {
        $data['registrationcode'] = '';
    }
}

if (!empty($CFG->sitetype)) {
    $PAGE->set_button($PAGE->button .
        $OUTPUT->single_button(
            new \moodle_url('/admin/register.php', ['download' => 1]),
            get_string('downloadregistrationdata', 'totara_core')
        )
    );
}

$mform = new register_form();
$mform->set_data($data);

if ($formdata = $mform->get_data()) {
    // Try to always finish this request without interruption.
    ignore_user_abort(true);

    if (isset($formdata->sitetype)) {
        set_config('sitetype', $formdata->sitetype);
    }
    if (isset($formdata->registrationcode)) {
        set_config('registrationcode', trim($formdata->registrationcode));
        set_config('registrationcodewwwhash', sha1($CFG->wwwroot));
    }
    // Send the registration if enabled.
    if ($CFG->registrationenabled) {
        $data = get_registration_data();
        $data['manualupdate'] = '1';
        send_registration_data($data);
    }
    if ($return === 'site') {
        $url = "$CFG->wwwroot/index.php";
    } else if ($return === 'admin') {
        $url = "$CFG->wwwroot/$CFG->admin/index.php";
    } else {
        $url = "$CFG->wwwroot/$CFG->admin/register.php";
    }
    totara_set_notification(get_string('totararegistrationsaved', 'totara_core'), $url, array('class' => 'notifysuccess'));
}

// Print headings.
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string("totararegistration", 'totara_core'));

echo $OUTPUT->box(get_string("totararegistration_desc", 'totara_core'));

$mform->display();

echo $OUTPUT->footer();


