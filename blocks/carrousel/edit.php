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
 * @author Dmitry Buriak <dmitry.buriak@kineo.co.il>
 * @package block_carrousel
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/blocks/carrousel/lib.php');
require_once($CFG->dirroot . '/blocks/carrousel/locallib.php');
require_once($CFG->dirroot . '/blocks/carrousel/carrousel_forms.php');
require_once($CFG->libdir.'/adminlib.php');

$action = optional_param('action', null, PARAM_ALPHANUMEXT);
$id = 0;

$blockid = required_param('blockid', PARAM_INT);
 
if ($action != 'new') {
    $slideid = required_param('id', PARAM_INT);
    $slide = block_carrousel_create_slide($blockid, $slideid);
} else {
    $slide = block_carrousel_create_slide($blockid);
}


//page settings
require_login();

$block = $DB->get_record_sql("SELECT * FROM {block_instances} WHERE id = $blockid");
$blockContext = context::instance_by_id($block->parentcontextid);
require_capability('block/carrousel:manage', $blockContext);

$PAGE->set_url(new moodle_url('/blocks/carrousel/edit.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_context($blockContext);

 // die((string)$block->parentcontextid);

// Set up JS.
local_js(array(
    TOTARA_JS_UI,
    TOTARA_JS_ICON_PREVIEW,
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));

define('COHORT_ASSN_ITEMTYPE_BLOCK', 100); // 40, 45, 50, 55, 65 - reselved by totara


// Assigned audiences.
block_carrousel_include_totara_cohortdialog($blockid, $slide);
block_carrousel_include_carrousel_js();

$context = CONTEXT_BLOCK::instance($slide->blockid);
$slideId = isset($slide->id) ? $slide->id : null;
file_prepare_standard_filemanager($slide, 'private', [
    'subdirs'        => 0, 
    'maxbytes'       => 50000000, 
    'maxfiles'       => 1,
    'accepted_types' => ['.png', '.jpg', '.gif'] 
], $context, 'block_carrousel', 'private', $slideId); 

$returnurl = new moodle_url('/blocks/carrousel/index.php', array('blockid' => $blockid));
$mform = new carrousel_edit_form(null, array('slide' => $slide));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        totara_set_notification(get_string('error:unknownbuttonclicked', 'block_carrousel'), $returnurl);
    }
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }
    $slide = block_carrousel_process_form_submition($slide, $fromform);
    totara_set_notification(
        get_string('slidesaved', 'block_carrousel'),
        $returnurl, 
        array('class' => 'notifysuccess')
    );
}


if ($id == 0) {
    $heading = get_string('add', 'block_carrousel');
    $name = get_string('add', 'block_carrousel');
} else {
    $heading = get_string('editslide', 'block_carrousel');
}

$title = get_string('settingupyour', 'block_carrousel') . ': ' . $heading;

$PAGE->set_title($title);
$PAGE->navbar->add($blockContext->get_context_name(), $blockContext->get_url());
$PAGE->navbar->add(
    get_string('pluginname','block_carrousel'), 
    new moodle_url('/blocks/carrousel/index.php',  array('blockid'=> $blockid))
);
$PAGE->navbar->add(get_string('editslide','block_carrousel'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editslide', 'block_carrousel'));

$PAGE->set_heading($heading);

$mform->display();

echo $OUTPUT->footer();