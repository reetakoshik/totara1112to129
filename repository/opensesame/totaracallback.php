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
require_once("$CFG->dirroot/repository/lib.php");

$PAGE->set_url('/repository/opensesame/totaracallback.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('popup');

require_login();
$syscontext = context_system::instance();
require_capability('repository/opensesame:managepackages', $syscontext);

echo $OUTPUT->header();

// Do not interrupt this process, let user continue to other pages.
ignore_user_abort(true);
raise_memory_limit(MEMORY_HUGE);
\core\session\manager::write_close();
core_php_time_limit::raise(60 * 20);

// There is an option to get only 'new' courses, but there seems to be problems with it,
// let's get all courses to make sure we have everything.
$count = \repository_opensesame\local\util::fetch_packages('full');

if ($count === false) {
    echo $OUTPUT->notification(get_string('coursefetcherror', 'repository_opensesame'), 'notifyproblem');
} else if ($count > 0) {
    echo $OUTPUT->notification(get_string('coursefetchsuccess', 'repository_opensesame', $count), 'notifysuccess');
} else {
    echo $OUTPUT->notification(get_string('coursefetchsuccessnocourse', 'repository_opensesame'), 'notifysuccess');
}

$backurl = new moodle_url('/repository/opensesame/index.php');
$backurl = $backurl->out(false);
$continue = get_string('continue');

echo '<div class="continuebutton"><button onclick="window.parent.location.replace(\'' . $backurl . '\')">' . $continue . '</button><div/>';

echo $OUTPUT->footer();
