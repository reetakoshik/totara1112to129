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

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/core/dialogs/dialog_content.class.php');

$facetofaceid = required_param('facetofaceid', PARAM_INT); // Necessary when creating new sessions.
$sessionid = required_param('sessionid', PARAM_INT);       // Empty when adding new session.
$timestart = required_param('timestart', PARAM_INT);
$timefinish = required_param('timefinish', PARAM_INT);
$offset = optional_param('offset', 0, PARAM_INT);
$search = optional_param('search', 0, PARAM_INT);
$selected = required_param('selected', PARAM_SEQUENCE);

if (!$facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}

if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}

if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

if ($sessionid) {
    if (!$session = facetoface_get_session($sessionid)) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
    if ($session->facetoface != $facetoface->id) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_sesskey();
require_capability('mod/facetoface:editevents', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/asset/ajax/sessionassets.php', array(
    'facetofaceid' => $facetofaceid,
    'sessionid' => $sessionid,
    'timestart' => $timestart,
    'timefinish' => $timefinish
));

// Include the same strings as mod/facetoface/sessions.php, because we override the lang string cache with this ugly hack.
$PAGE->requires->strings_for_js(array('save', 'delete'), 'totara_core');
$PAGE->requires->strings_for_js(array('cancel', 'ok', 'edit', 'loadinghelp'), 'moodle');
$PAGE->requires->strings_for_js(array('chooseassets', 'chooseroom', 'dateselect', 'useroomcapacity', 'nodatesyet',
    'createnewasset', 'editasset', 'createnewroom', 'editroom'), 'facetoface');

if (empty($timestart) || empty($timefinish)) {
    print_error('notimeslotsspecified', 'facetoface');
}

// Legacy Totara HTML ajax, this should be converted to json + AJAX_SCRIPT.
send_headers('text/html; charset=utf-8', false);

// Setup / loading data
$allassets = facetoface_get_available_assets(0, 0 , 'fa.*', $sessionid, $facetofaceid);
$availableassets = facetoface_get_available_assets($timestart, $timefinish, 'fa.id', $sessionid, $facetofaceid);
$selectedids = explode(',', $selected);
$selectedassets = array();
$unavailableassets = array();
foreach ($allassets as $asset) {
    customfield_load_data($asset, "facetofaceasset", "facetoface_asset");
    $asset->fullname = $asset->name;
    if (!isset($availableassets[$asset->id])) {
        $unavailableassets[$asset->id] = $asset->id;
        $asset->fullname .= get_string('assetalreadybooked', 'facetoface');
    }
    if ($asset->custom) {
        $asset->fullname .= ' (' . get_string('facetoface', 'facetoface') . ': ' . format_string($facetoface->name) . ')';
    }
    if (in_array($asset->id, $selectedids)) {
        $selectedassets[$asset->id] = $asset;
    }
}

// Display page.
$dialog = new totara_dialog_content();
$dialog->searchtype = 'facetoface_asset';
$dialog->proxy_dom_data(array('id', 'custom'));
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->items = $allassets;
$dialog->disabled_items = $unavailableassets;
$dialog->selected_items = $selectedassets;
$dialog->selected_title = 'itemstoadd';
$dialog->lang_file = 'facetoface';
$dialog->customdata['facetofaceid'] = $facetofaceid;
$dialog->customdata['timestart'] = $timestart;
$dialog->customdata['timefinish'] = $timefinish;
$dialog->customdata['sessionid'] = $sessionid;
$dialog->customdata['selected'] = $selected;
$dialog->customdata['offset'] = $offset;
$dialog->string_nothingtodisplay = 'error:nopredefinedassets';

echo $dialog->generate_markup();

// May be it's better to dynamically generate create new asset link during dialog every_load.
// This will allow to remove offset parameter from url.
if (!$search) {
    $addassetlinkhtml =  html_writer::link('#', get_string('createnewasset', 'facetoface'),
        array('id' => 'show-editcustomasset' . $offset . '-dialog'));
    echo html_writer::div($addassetlinkhtml, 'dialog-nobind dialog-footer');
}
