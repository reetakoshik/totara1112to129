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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

use mod_facetoface\room;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$debug = optional_param('debug', 0, PARAM_INT);

// Check permissions.
admin_externalpage_setup('modfacetofacerooms');

$returnurl = new moodle_url('/admin/settings.php', array('section' => 'modsettingfacetoface'));

$report = reportbuilder::create_embedded('facetoface_rooms');
$redirectto = new moodle_url('/mod/facetoface/room/manage.php', $report->get_current_url_params());

// Handle actions.
if ($action === 'delete') {
    if (empty($id)) {
        print_error('error:roomdoesnotexist', 'facetoface', $returnurl);
    }

    $room = new room($id);
    if ($room->get_custom()) {
        print_error('error:roomnotpublished', 'facetoface', $returnurl);
    }

    $roominuse = $DB->count_records('facetoface_sessions_dates', array('roomid' => $id));
    if ($roominuse) {
        print_error('error:roomisinuse', 'facetoface', $returnurl);
    }

    if (!$confirm) {
        echo $OUTPUT->header();
        $confirmurl = new moodle_url($redirectto, array('action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()));
        echo $OUTPUT->confirm(get_string('deleteroomconfirm', 'facetoface', format_string($room->get_name())), $confirmurl, $redirectto);
        echo $OUTPUT->footer();
        die;
    }

    require_sesskey();
    $room->delete();
    unset($room);

    totara_set_notification(get_string('roomdeleted', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));

} else if ($action === 'show') {
    if (empty($id)) {
        print_error('error:roomdoesnotexist', 'facetoface', $returnurl);
    }

    require_sesskey();
    $room = new room($id);
    if ($room->get_custom()) {
        print_error('error:roomnotpublished', 'facetoface', $returnurl);
    }

    $room->show();
    $room->save();

    totara_set_notification(get_string('roomshown', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));

} else if ($action === 'hide') {
    if (empty($id)) {
        print_error('error:roomdoesnotexist', 'facetoface', $returnurl);
    }

    require_sesskey();
    $room = new room($id);
    if ($room->get_custom()) {
        print_error('error:roomnotpublished', 'facetoface', $returnurl);
    }

    $room->hide();
    $room->save();

    totara_set_notification(get_string('roomhidden', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));
}

$PAGE->set_button($report->edit_button() . $PAGE->button);
/** @var totara_reportbuilder_renderer $reportrenderer */
$reportrenderer = $PAGE->get_renderer('totara_reportbuilder');

echo $OUTPUT->header();

$report->include_js();
$report->display_restrictions();

echo $OUTPUT->heading(get_string('managerooms', 'facetoface'));

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $reportrenderer->report_html($report, $debug);
echo $debughtml;
echo $reportrenderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();
echo $report->display_saved_search_options();
echo $reporthtml;

$addurl = new moodle_url('/mod/facetoface/room/edit.php');

echo $OUTPUT->container_start('buttons');
echo $OUTPUT->single_button($addurl, get_string('addnewroom', 'facetoface'), 'get');
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
