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
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

use mod_facetoface\asset;

admin_externalpage_setup('modfacetofaceassets');

$id = optional_param('id', 0, PARAM_INT);
$asset = new asset($id);

if ($asset->get_custom()) {
    print_error(get_string('error:incorrectassetid', 'facetoface'));
}

$assetlisturl = new moodle_url('/mod/facetoface/asset/manage.php');

$customdata = ['asset' => $asset, 'editoroptions' => $TEXTAREA_OPTIONS];
$form = new \mod_facetoface\form\asset_edit(null, $customdata, 'post', '', array('class' => 'dialog-nobind'), true, null, 'mform_modal');

if ($form->is_cancelled()) {
    redirect($assetlisturl);
}

if ($data = $form->get_data()) {
    $asset = \mod_facetoface\asset_helper::save($data);
    $message = $id ? get_string('assetcreatesuccess', 'facetoface') : get_string('assetupdatesuccess', 'facetoface');
    totara_set_notification($message, $assetlisturl, array('class' => 'notifysuccess'));
}

$url = new moodle_url('/admin/settings.php', array('section' => 'modsettingfacetoface'));

if ($id == 0) {
    $pageheading = get_string('addasset', 'facetoface');
} else {
    $pageheading = get_string('editasset', 'facetoface');
}

echo $OUTPUT->header();

echo $OUTPUT->heading($pageheading);

$form->display();

echo $OUTPUT->footer();
