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
 * @package repository_opensesame
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$visible = required_param('visible', PARAM_BOOL);
$reportid = required_param('reportid', PARAM_INT);

require_login();
$syscontext = context_system::instance();
require_capability('repository/opensesame:managepackages', $syscontext);

require_sesskey();

if ($reportid) {
    $returnurl = new moodle_url('/totara/reportbuilder/report.php', array('id' => $reportid));
} else {
    $returnurl = new moodle_url('/repository/opensesame/index.php');
}

$PAGE->set_context($syscontext);
$PAGE->set_url($returnurl);
$PAGE->set_totara_menu_selected('myreports');
$PAGE->set_pagelayout('noblocks');

$prevpkg = $DB->get_record('repository_opensesame_pkgs', array('id' => $id));
if (!$prevpkg or $prevpkg->visible == $visible) {
    redirect($returnurl);
}

$DB->set_field('repository_opensesame_pkgs', 'visible', $visible, array('id' => $id));
$pkg = $DB->get_record('repository_opensesame_pkgs', array('id' => $id), '*', MUST_EXIST);

if ($visible) {
    \repository_opensesame\event\package_unhid::create_from_package($pkg)->trigger();
} else {
    \repository_opensesame\event\package_hid::create_from_package($pkg)->trigger();
}

redirect($returnurl);
