<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package totara_program
 */

use totara_form\file_area;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/program/default_image_upload_form.php');

defined('MOODLE_INTERNAL') || die();

$iscertif = optional_param('iscertif', 0, PARAM_BOOL);
if ($iscertif) {
    check_certification_enabled();
} else {
    check_program_enabled();
}

require_login();

$context = context_system::instance();
require_capability('totara/certification:createcertification', $context);
require_capability('totara/certification:configurecertification', $context);

$PAGE->set_url('/totara/program/default_image_upload.php');
$PAGE->set_context($context);

$filearea = $iscertif ?
    'totara_certification_default_image' :
    'totara_program_default_image';

$formdata = new stdClass();
$formdata->defaultimage = new file_area(
    $context,
    'totara_core',
    $filearea,
    0
);

// Form definition.
$form = new default_image_upload_form($formdata, ['iscertif' => $iscertif]);


// Form saving.
if ($form->get_data()) {
    $fs = get_file_storage();
    $fs->delete_area_files(
        $context->id,
        'totara_core',
        $filearea,
        0
    );
}
if ($form->get_files() && count($form->get_files()->defaultimage) == 1) {
    $file = $form->get_files()->defaultimage[0];
    $form->save_stored_file(
        'defaultimage',
        $context->id,
        'totara_core',
        $filearea,
        0,
        '/',
        $file->get_filename(),
        true
    );
}

echo $OUTPUT->header();

echo $form->render();

echo $OUTPUT->footer();
