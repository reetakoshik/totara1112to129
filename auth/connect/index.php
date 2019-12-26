<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package auth_connect
 */

use auth_connect\util;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$register = optional_param('register', 0, PARAM_BOOL);
$cancelregister = optional_param('cancelregister', 0, PARAM_BOOL);

admin_externalpage_setup('authconnectservers');

if ($register) {
    require_sesskey();
    util::enable_registration();
    redirect($PAGE->url);

} else if ($cancelregister) {
    require_sesskey();
    util::cancel_registration();
    redirect($PAGE->url);
}

$report = reportbuilder::create_embedded('connect_servers');

echo $OUTPUT->header();

$strheading = get_string('embeddedreportname', 'rb_source_connect_servers');
echo $OUTPUT->heading($strheading);
echo util::warn_if_not_https();

// No searching here, there are going to be very few servers registered.

$report->display_table();

$setupsecret = util::get_setup_secret();
if ($setupsecret) {
    $a = new stdClass();
    $a->url = $CFG->wwwroot;
    $a->secret = $setupsecret;
    echo $OUTPUT->notification(get_string('registerinfo', 'auth_connect', $a), 'notifymessage');
    $url = new moodle_url('/auth/connect/index.php', array('cancelregister' => 1, 'sesskey' => sesskey()));
    echo $OUTPUT->single_button($url, get_string('registercancel', 'auth_connect'));

} else {
    $url = new moodle_url('/auth/connect/index.php', array('register' => 1, 'sesskey' => sesskey()));
    echo $OUTPUT->single_button($url, get_string('registerrequest', 'auth_connect'));
}

echo $OUTPUT->footer();
