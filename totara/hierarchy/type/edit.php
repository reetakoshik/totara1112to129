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
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
require_once($CFG->dirroot.'/totara/cohort/lib.php');


// type id; 0 if creating a new type
$prefix = required_param('prefix', PARAM_ALPHA); // hierarchy prefix
$shortprefix = hierarchy::get_short_prefix($prefix);
$id = optional_param('id', 0, PARAM_INT);    // type id; 0 if creating a new type
$class = optional_param('class', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

if ($class == 'personal') {
    $shortprefix = 'goal_user';
}

if ($prefix == 'goal') {
    $returnurl = $CFG->wwwroot . '/totara/hierarchy/type/index.php?prefix='. $prefix . '&class=' . $class;
} else {
    $returnurl = $CFG->wwwroot . '/totara/hierarchy/type/index.php?prefix='. $prefix;
}

hierarchy::check_enable_hierarchy($prefix);
$hierarchy = hierarchy::load_hierarchy($prefix);

// If the hierarchy prefix has type editing files use them else use the generic files
if (file_exists($CFG->dirroot.'/totara/hierarchy/prefix/'.$prefix.'/type/edit.php')) {
    require_once($CFG->dirroot.'/totara/hierarchy/prefix/'.$prefix.'/type/edit_form.php');
    require_once($CFG->dirroot.'/totara/hierarchy/prefix/'.$prefix.'/type/edit.php');
    die;
} else {
    require_once($CFG->dirroot.'/totara/hierarchy/type/edit_form.php');
}

// Manage frameworks
if ($prefix == 'goal') {
    admin_externalpage_setup($class . $prefix . 'typemanage', null, array('prefix' => $prefix, 'class' => $class));
} else {
    admin_externalpage_setup($prefix.'typemanage', null, array('prefix' => $prefix));
}

$context = context_system::instance();

if ($id == 0) {
    // creating new type
    require_capability('totara/hierarchy:create'.$prefix.'type', $context);

    $type = new stdClass();
    $type->id = 0;
    $type->description = '';
} else {
    $typetable = false;

    // editing existing type
    if ($class == 'personal') {
        $typetable  = true;
    }
    require_capability('totara/hierarchy:update'.$prefix.'type', $context);
    if (!$type = $hierarchy->get_type_by_id($id, $typetable)) {
        print_error('incorrecttypeid', 'totara_hierarchy');
    }
}

$PAGE->requires->strings_for_js(array('choosecohort'), 'totara_hierarchy');

// Include JS for icon preview
local_js(array(
            TOTARA_JS_ICON_PREVIEW,
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW,
            TOTARA_JS_DATEPICKER
        ));

// Enrolled audiences for personal goals only.
if ($shortprefix == 'goal_user') {
    if (empty($type->id)) {
        $enrolledselected = '';
    } else {
        $enrolledselected = totara_cohort_get_goal_type_cohorts($type->id, 'c.id');
        $enrolledselected = !empty($enrolledselected) ? implode(',', array_keys($enrolledselected)) : '';
    }

    $jsmodule = array(
        'name' => 'totara_cohortdialog',
        'fullpath' => "/totara/cohort/dialog/{$prefix}cohort.js",
        'requires' => array('json'));
    $args = array("enrolledselected" => $enrolledselected);
    $PAGE->requires->js_init_call("M.totara_{$prefix}cohort.init", $args, true, $jsmodule);
    unset($enrolledselected);
}

// create form
$editoroptions = array(
    'subdirs' => 0,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => get_max_upload_file_size(),
    'trusttext' => false,
    'context' => $context,
    'collapsed' => true
);
$type->descriptionformat = FORMAT_HTML;
$type = file_prepare_standard_editor($type, 'description', $editoroptions, $context,
                                          'totara_hierarchy', $shortprefix.'_type', $type->id);
$datatosend = array('prefix' => $prefix, 'class' => $class, 'page' => $page, 'id' => $id);
$typeform  = new type_edit_form(null, $datatosend);
$typeform->set_data($type);

// cancelled
if ($typeform->is_cancelled()) {

    redirect($returnurl);

// update data
} else if ($typenew = $typeform->get_data()) {
    $typenew->timemodified = time();
    $typenew->usermodified = $USER->id;
    // Class to hold totara_set_notification info.
    $notification = new stdClass();
    $notification->url = $returnurl;

    // New type.
    if ($typenew->id == 0) {
        unset($typenew->id);
        $typenew->timecreated = time();

        $typenew->id = $DB->insert_record($shortprefix.'_type', $typenew);
        $typenew = file_postupdate_standard_editor($typenew, 'description', $editoroptions, $context, 'totara_hierarchy', $shortprefix.'_type', $typenew->id);
        $DB->set_field($shortprefix.'_type', 'description', $typenew->description, array('id' => $typenew->id));

        totara_hierarchy_save_cohorts_for_type($shortprefix, $prefix . 'id', $typenew);

        $typenew = $DB->get_record($shortprefix.'_type', array('id' => $typenew->id));
        $eventname = "\\hierarchy_{$prefix}\\event\\type_created";
        $eventname::create_from_instance($typenew)->trigger();

        $notification->text = $prefix . 'createtype';
        $notification->params = array('class' => 'notifysuccess');

    // Existing type.
    } else {
        $typenew = file_postupdate_standard_editor($typenew, 'description', $editoroptions,
            $context, 'totara_hierarchy', $shortprefix.'_type', $typenew->id);
        $DB->update_record($shortprefix.'_type', $typenew);

        totara_hierarchy_save_cohorts_for_type($shortprefix, $prefix . 'id', $typenew);

        $typenew = $DB->get_record($shortprefix.'_type', array('id' => $typenew->id));
        $eventname = "\\hierarchy_{$prefix}\\event\\type_updated";
        $eventname::create_from_instance($typenew)->trigger();

        $notification->text = $prefix . 'updatetype';
        $notification->params = array('class' => 'notifysuccess');
    }
    totara_set_notification(get_string($notification->text, 'totara_hierarchy', $typenew->fullname), $notification->url, $notification->params);
}


/// Display page header
$PAGE->navbar->add(get_string("{$prefix}types", 'totara_hierarchy'), $returnurl);

if ($id == 0) {
    $PAGE->navbar->add(get_string('addtype', 'totara_hierarchy'));
} else {
    $PAGE->navbar->add(get_string('editgeneric', 'totara_hierarchy', format_string($type->fullname)));
}

echo $OUTPUT->header();

if ($type->id == 0) {
    echo $OUTPUT->heading(get_string('addtype', 'totara_hierarchy'));
} else {
    echo $OUTPUT->heading(get_string('editgeneric', 'totara_hierarchy', format_string($type->fullname)));
}

/// Finally display the form
$typeform->display();

echo $OUTPUT->footer();
