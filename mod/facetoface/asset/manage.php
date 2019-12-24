<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$delete = optional_param('delete', 0, PARAM_INT);
$show = optional_param('show', 0, PARAM_INT);
$hide = optional_param('hide', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$debug = optional_param('debug', 0, PARAM_INT);

// Check permissions.
admin_externalpage_setup('modfacetofaceassets');

$returnurl = new moodle_url('/admin/settings.php', array('section' => 'modsettingfacetoface'));

$report = reportbuilder_get_embedded_report('facetoface_assets', array(), false, 0);
$redirectto = new moodle_url('/mod/facetoface/asset/manage.php', $report->get_current_url_params());

// Handle actions.
if ($delete) {
    if (!$asset = $DB->get_record('facetoface_asset', array('id' => $delete, 'custom' => 0))) {
        return($returnurl);
    }

    $assetinuse = $DB->count_records('facetoface_asset_dates', array('assetid' => $delete));
    if ($assetinuse) {
        print_error('error:assetisinuse', 'facetoface', $returnurl);
    }

    if (!$confirm) {
        echo $OUTPUT->header();
        $confirmurl = new moodle_url($redirectto, array('delete' => $delete, 'confirm' => 1, 'sesskey' => sesskey()));
        echo $OUTPUT->confirm(get_string('deleteassetconfirm', 'facetoface', format_string($asset->name)), $confirmurl, $redirectto);
        echo $OUTPUT->footer();
        die;
    }

    require_sesskey();
    facetoface_delete_asset($delete);

    totara_set_notification(get_string('assetdeleted', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));

} else if ($show) {

    require_sesskey();
    if (!$asset = $DB->get_record('facetoface_asset', array('id' => $show, 'custom' => 0))) {
        print_error('error:assetdoesnotexist', 'facetoface', $returnurl);
    }

    $DB->update_record('facetoface_asset', array('id' => $show, 'hidden' => 0));

    totara_set_notification(get_string('assetshown', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));

} else if ($hide) {

    require_sesskey();
    if (!$asset = $DB->get_record('facetoface_asset', array('id' => $hide, 'custom' => 0))) {
        print_error('error:assetdoesnotexist', 'facetoface', $returnurl);
    }

    $DB->update_record('facetoface_asset', array('id' => $hide, 'hidden' => 1));

    totara_set_notification(get_string('assethidden', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));
}

$PAGE->set_button($report->edit_button());
/** @var totara_reportbuilder_renderer $reportrenderer */
$reportrenderer = $PAGE->get_renderer('totara_reportbuilder');

echo $OUTPUT->header();

$report->include_js();
$report->display_restrictions();

echo $OUTPUT->heading(get_string('manageassets', 'facetoface'));

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $reportrenderer->report_html($report, $debug);
echo $debughtml;
echo $reportrenderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();
echo $report->display_saved_search_options();
echo $reporthtml;

$addurl = new moodle_url('/mod/facetoface/asset/edit.php');

echo $OUTPUT->container_start('buttons');
echo $OUTPUT->single_button($addurl, get_string('addnewasset', 'facetoface'), 'get');
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
