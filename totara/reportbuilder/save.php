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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Page containing save search form
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once('report_forms.php');

require_login();

$id = required_param('id', PARAM_INT); // Id for report to save.

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_totara_menu_selected('\totara_core\totara\menu\myreports');

$report = reportbuilder::create($id);
$returnurl = $report->report_url(true);

$PAGE->set_url('/totara/reportbuilder/save.php', array_merge($report->get_current_url_params(), array('id' => $id)));

if (isguestuser() or !reportbuilder::is_capable($id)) {
    // No saving for guests, sorry.
    print_error('nopermission', 'totara_reportbuilder');
}

$data = new stdClass();
$data->id = $id;
$data->sid = 0;
$data->ispublic = 0;
$data->action = 'edit';

$mform = new report_builder_save_form($PAGE->url, array('report' => $report, 'data' => $data));

// form results check
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'totara_reportbuilder', $returnurl);
    }

    $searchsettings = (isset($SESSION->reportbuilder[$report->get_uniqueid()])) ?
            serialize($SESSION->reportbuilder[$report->get_uniqueid()]) : null;

    // handle form submission
    $todb = new stdClass();
    $todb->reportid = $fromform->id;
    $todb->userid = $USER->id;
    $todb->search = $searchsettings;
    $todb->name = $fromform->name;
    $todb->ispublic = $fromform->ispublic;
    $todb->timemodified = time();
    $todb->id = $DB->insert_record('report_builder_saved', $todb);

    redirect($returnurl);
}

$fullname = format_string($report->fullname, true, ['context' => $context]);
$pagetitle = get_string('savesearch', 'totara_reportbuilder').': '.$fullname;

$PAGE->set_title($pagetitle);
$PAGE->navbar->add(get_string('report', 'totara_reportbuilder'));
$PAGE->navbar->add($fullname);
$PAGE->navbar->add(get_string('savesearch', 'totara_reportbuilder'));

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
