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
 * @author  Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package core_user
 */

use totara_core\quickaccessmenu\helper;
use totara_core\output\quickaccesssettings;

require_once(__DIR__ . '/../config.php');

$reset = optional_param('reset', 0, PARAM_INT);
$confirm  = optional_param('confirm',  0, PARAM_BOOL);

$url = new moodle_url('/user/quickaccessmenu.php');
$PAGE->set_url($url, ['id' => $USER->id]);

if (!isloggedin()) {
    if (empty($SESSION->wantsurl)) {
        $SESSION->wantsurl = $CFG->wwwroot . '/user/preferences.php';
    }
    redirect(get_login_url());
} else if (isguestuser()) {
    // Guests can not edit menu.
    redirect($CFG->wwwroot);
}

$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->navbar->includesettingsbase = true;

$systemcontext = context_system::instance();
require_capability('totara/core:editownquickaccessmenu', $systemcontext);

// Display page header.
$settingsheading = get_string('quickaccessmenu:settingsheading', 'totara_core');
$userfullname = fullname($USER, true);
$PAGE->set_title("{$SITE->shortname}: {$settingsheading}");
$PAGE->set_heading($userfullname);

if (!empty($reset)) {
    if (empty($confirm)) {
        $message  = get_string('quickaccessmenu:resetconfirm', 'totara_core');
        $continue = new moodle_url('/user/quickaccessmenu.php', ['confirm' => 1, 'reset' => 1, 'sesskey' => sesskey()]);
        echo $OUTPUT->header();
        echo $OUTPUT->confirm($message, $continue, $url);
        echo $OUTPUT->footer();
        exit;
    } else {
        require_sesskey();
        helper::reset_to_default($USER->id);
        redirect($url, get_string('quickaccessmenu:resetcomplete', 'totara_core'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$adminmenu = helper::get_user_menu();
$quickaccessmenu = quickaccesssettings::create_from_menu($adminmenu);

// Reset button.
$reseturl = new moodle_url('/user/quickaccessmenu.php', ['reset' => 1]);
$resetbutton = $OUTPUT->single_button($reseturl, get_string('quickaccessmenu:reset', 'totara_core'), 'get');

$PAGE->set_button($resetbutton);

echo $OUTPUT->header();
echo $OUTPUT->heading($settingsheading);
echo $OUTPUT->render($quickaccessmenu);
echo $OUTPUT->footer();
