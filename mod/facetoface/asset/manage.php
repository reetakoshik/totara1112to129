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

use mod_facetoface\asset;

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$debug = optional_param('debug', 0, PARAM_INT);

// Check permissions.
admin_externalpage_setup('modfacetofaceassets');

$returnurl = new moodle_url('/admin/settings.php', array('section' => 'modsettingfacetoface'));

$report = reportbuilder::create_embedded('facetoface_assets');
$redirectto = new moodle_url('/mod/facetoface/asset/manage.php', $report->get_current_url_params());

// Handle actions.
if ($action === 'delete') {
    if (empty($id)) {
        print_error('error:assetdoesnotexist', 'facetoface', $returnurl);
    }

    $asset = new asset($id);

    if ($asset->is_used()) {
        print_error('error:assetisinuse', 'facetoface', $returnurl);
    }

    if (!$confirm) {
        echo $OUTPUT->header();
        $confirmurl = new moodle_url($redirectto, array('action' => $action, 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()));
        echo $OUTPUT->confirm(get_string('deleteassetconfirm', 'facetoface', format_string($asset->get_name())), $confirmurl, $redirectto);
        echo $OUTPUT->footer();
        die;
    }

    require_sesskey();
    $asset->delete();
    unset($asset);

    totara_set_notification(get_string('assetdeleted', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));

} else if ($action === 'show') {
    if (empty($id)) {
        print_error('error:assetdoesnotexist', 'facetoface', $returnurl);
    }

    require_sesskey();
    $asset = new asset($id);
    $asset->show();
    $asset->save();

    totara_set_notification(get_string('assetshown', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));

} else if ($action === 'hide') {
    if (empty($id)) {
        print_error('error:assetdoesnotexist', 'facetoface', $returnurl);
    }

    require_sesskey();
    $asset = new asset($id);
    $asset->hide();
    $asset->save();

    totara_set_notification(get_string('assethidden', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));
}

$PAGE->set_button($report->edit_button() . $PAGE->button);
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
