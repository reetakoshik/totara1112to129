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
require_once("$CFG->dirroot/lib/adminlib.php");
require_once("$CFG->dirroot/repository/lib.php");
require_once("$CFG->dirroot/mod/scorm/lib.php");
require_once("$CFG->dirroot/course/modlib.php");
require_once("$CFG->dirroot/totara/core/js/lib/setup.php");

$pkgid = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('opensesamereport', '', null, '', array('pagelayout'=>'report'));

$opensesame = core_plugin_manager::instance()->get_plugin_info('repository_opensesame');
if (!$opensesame->is_enabled()) {
    redirect(new moodle_url('/'));
}

$package = $DB->get_record('repository_opensesame_pkgs', array('id' => $pkgid), '*', MUST_EXIST);

$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true);
$mform = new repository_opensesame_form_create_course(null, array('package' => $package, 'editoroptions' => $editoroptions));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/repository/opensesame/index.php'));

} else if ($data = $mform->get_data()) {

    // TODO: this is VERY NASTY, there must be a less hacky way in the future...

    $syscontext = context_system::instance();
    $categorycontext = context_coursecat::instance($data->category);
    require_capability('moodle/course:create', $categorycontext);

    $data->format = 'singleactivity';
    $data->activitytype = 'scorm';

    $courseconfig = get_config('moodlecourse');
    foreach ($courseconfig as $k => $v) {
        if (!isset($data->$k)) {
            $data->$k = $v;
        }
    }

    $course = create_course($data, $editoroptions);
    $course = $DB->get_record('course', array('id' => $course->id), '*', MUST_EXIST);

    $module = $DB->get_record('modules', array('name' => 'scorm'), '*', MUST_EXIST);

    $scormdata = new stdClass();
    $scormdata->modulename = 'scorm';
    $scormdata->module = $module->id;
    $scormdata->name = $course->fullname;
    $scormdata->cmidnumber = '';
    $scormdata->section = 0;
    $scormdata->intro = $course->summary;
    $scormdata->introformat = $course->summaryformat;
    $scormdata->visible = 1;
    $scormdata->scormtype = SCORM_TYPE_LOCAL;
    $scormdata->width = 100;
    $scormdata->height = 500;
    $scormdata->nav = 0;
    $scormdata->hidetoc = 3;
    $scormdata->reference = $package->zipfilename;

    $scormconfig = get_config('scorm');
    foreach ($scormconfig as $k => $v) {
        if (!isset($scormdata->$k)) {
            $scormdata->$k = $v;
        }
    }

    $scormdata = add_moduleinfo($scormdata, $course, null);
    $cm = get_coursemodule_from_instance('scorm', $scormdata->instance);
    $context = context_module::instance($cm->id);

    $fs = get_file_storage();
    $packagefile = $fs->get_file($syscontext->id, 'repository_opensesame', 'packages', $package->id, '/', $package->zipfilename);

    $file = array('contextid' => $context->id, 'component' => 'mod_scorm', 'filearea' => 'package', 'itemid' => 0);
    $fs->create_file_from_storedfile($file, $packagefile);

    $record = $DB->get_record('scorm', array('id' => $cm->instance), '*', MUST_EXIST);
    $record->cmid = $cm->id;

    scorm_parse($record, true);

    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Icon picker.
local_js(array(
    TOTARA_JS_UI,
    TOTARA_JS_ICON_PREVIEW,
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));
$PAGE->requires->string_for_js('chooseicon', 'totara_program');
$iconjsmodule = array(
    'name' => 'totara_iconpicker',
    'fullpath' => '/totara/core/js/icon.picker.js',
    'requires' => array('json'));
$iconargs = array('args' => '{"selected_icon":"default","type":"course"}');
$PAGE->requires->js_init_call('M.totara_iconpicker.init', $iconargs, false, $iconjsmodule);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('createcourse', 'repository_opensesame'));
$mform->display();
echo $OUTPUT->footer();
