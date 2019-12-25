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

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('editvalue_form.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/scale/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');


///
/// Setup / loading data
///

// Scale value id; 0 if inserting
$id = optional_param('id', 0, PARAM_INT);
$prefix = required_param('prefix', PARAM_ALPHA);
// Competency scale id
$scaleid = optional_param('scaleid', 0, PARAM_INT);

$confirmupdate = optional_param('confirmupdate', false, PARAM_BOOL);

// Cache user capabilities.
$sitecontext = context_system::instance();

// Check if Competencies are enabled.
competency::check_feature_enabled();

// Set up the page.
admin_externalpage_setup($prefix.'manage');

// Make sure we have at least one or the other
if (!$id && !$scaleid) {
    print_error('incorrectparameters', 'totara_hierarchy');
}

if ($id == 0) {
    // Creating new scale value
    require_capability('totara/hierarchy:createcompetencyscale', $sitecontext);

    $value = new stdClass();
    $value->id = 0;
    $value->description = '';
    $value->scaleid = $scaleid;
    $value->sortorder = $DB->get_field('comp_scale_values', 'MAX(sortorder) + 1', array('scaleid' => $value->scaleid));
    if (!$value->sortorder) {
        $value->sortorder = 1;
    }

} else {
    // Editing scale value
    require_capability('totara/hierarchy:updatecompetencyscale', $sitecontext);

    if (!$value = $DB->get_record('comp_scale_values', array('id' => $id))) {
        print_error('incorrectcompetencyscalevalueid', 'totara_hierarchy');
    }
}

if (!$scale = $DB->get_record('comp_scale', array('id' => $value->scaleid))) {
        print_error('incorrectcompetencyscaleid', 'totara_hierarchy');
}

$scale_used = competency_scale_is_used($scale->id);

// Save scale name for display in the form
$value->scalename = format_string($scale->name);

// check scale isn't being used when adding new scale values
if ($value->id == 0 && $scale_used) {
    print_error('usedscale', 'totara_hierarchy');
}

///
/// Display page
///

// Create form
$value->descriptionformat = FORMAT_HTML;
$value = file_prepare_standard_editor($value, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                          'totara_hierarchy', 'comp_scale_values', $value->id);
$value->numericscore = isset($value->numericscore) ? format_float($value->numericscore, 5, true, true) : '';
$valueform = new competencyscalevalue_edit_form(null, array('scaleid' => $scale->id, 'id' => $id));
$valueform->set_data($value);

// cancelled
if ($valueform->is_cancelled()) {

    redirect("$CFG->wwwroot/totara/hierarchy/prefix/competency/scale/view.php?id={$value->scaleid}&amp;prefix=competency");

// Update data
} else if (($valuenew = $valueform->get_data()) || $confirmupdate) {

    if ($confirmupdate) {
        require_sesskey();

        $valuenew = new stdClass();
        $valuenew->id = required_param('id', PARAM_INT);
        if ($valuenew->id == 0) {
            // In theory, this should already have been checked and the error just like below thrown.
            print_error('usedscale', 'totara_hierarchy');
        }
        $valuenew->scaleid = required_param('scaleid', PARAM_INT);
        $valuenew->name = required_param('name', PARAM_TEXT);
        $valuenew->idnumber = required_param('idnumber', PARAM_TEXT);
        $valuenew->numericscore = required_param('numericscore', PARAM_RAW);
        $valuenew->proficient = required_param('proficient', PARAM_INT);
        $valuenew->description_editor = required_param_array('description_editor', PARAM_RAW);

    } else {
        if (competency_scale_is_used($valuenew->scaleid)) {
            $current_record = $DB->get_record('comp_scale_values', ['id' => $valuenew->id], '*', MUST_EXIST);

            if ($current_record->proficient != $valuenew->proficient) {

                echo $OUTPUT->header();

                $title = get_string('competencyscalevalueconfirmtitle', 'totara_hierarchy');
                $message = nl2br(get_string('competencyscalevalueconfirmproficient', 'totara_hierarchy'));

                // This will be going into a single_button with method set to post, which means params will be post params.
                $continue_url = new moodle_url(
                    $CFG->wwwroot . '/totara/hierarchy/prefix/competency/scale/editvalue.php',
                    array (
                        'id' => $valuenew->id,
                        'scaleid' => $valuenew->scaleid,
                        'name' => $valuenew->name,
                        'idnumber' => $valuenew->idnumber,
                        'numericscore' => $valuenew->numericscore,
                        'proficient' => $valuenew->proficient,
                        'description_editor[text]' => $valuenew->description_editor['text'],
                        'description_editor[format]' => $valuenew->description_editor['format'],
                        'description_editor[itemid]' => $valuenew->description_editor['itemid'],
                        'prefix' => 'competency',
                        'confirmupdate' => 1
                    )
                );
                $continue = new single_button($continue_url, get_string('yes'), 'post', true);

                $cancel_url = new moodle_url(
                    $CFG->wwwroot . '/totara/hierarchy/prefix/competency/scale/view.php',
                    array (
                        'id' => $valuenew->scaleid,
                        'prefix' => 'competency'
                    )
                );
                $cancel = new single_button($cancel_url, get_string('no'), 'get');

                echo $OUTPUT->confirm($message, $continue, $cancel, $title);

                echo $OUTPUT->footer();
                exit;
            }
        }
    }

    $valuenew->timemodified = time();
    $valuenew->usermodified = $USER->id;
    $valuenew->numericscore = unformat_float($valuenew->numericscore);

    if (!strlen($valuenew->numericscore)) {
        $valuenew->numericscore = null;
    }

    // Save
    //class to hold totara_set_notification info
    $notification = new stdClass();
    // New scale value
    if ($valuenew->id == 0) {
        unset($valuenew->id);

        $valuenew->id = $DB->insert_record('comp_scale_values', $valuenew);
        $valuenew = file_postupdate_standard_editor($valuenew, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_hierarchy', 'comp_scale_values', $valuenew->id);
        $DB->set_field('comp_scale_values', 'description', $valuenew->description, array('id' => $valuenew->id));

        $valuenew = $DB->get_record('comp_scale_values', array('id' => $valuenew->id));
        \hierarchy_competency\event\scale_value_created::create_from_instance($valuenew)->trigger();

        $notification->text = 'scalevalueadded';
        $notification->url = "$CFG->wwwroot/totara/hierarchy/prefix/competency/scale/view.php?id={$valuenew->scaleid}&amp;prefix=competency";
        $notification->params = array('class' => 'notifysuccess');

    // Updating scale value
    } else {
        $valuenew = file_postupdate_standard_editor($valuenew, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_hierarchy', 'comp_scale_values', $valuenew->id);
        $DB->update_record('comp_scale_values', $valuenew);
        $valuenew = $DB->get_record('comp_scale_values', array('id' => $valuenew->id));
        \hierarchy_competency\event\scale_value_updated::create_from_instance($valuenew)->trigger();

        $notification->text = 'scalevalueupdated';
        $notification->url = "$CFG->wwwroot/totara/hierarchy/prefix/competency/scale/view.php?id={$valuenew->scaleid}&amp;prefix=competency";
        $notification->params = array('class' => 'notifysuccess');
    }
    totara_set_notification(get_string($notification->text, 'totara_hierarchy', format_string($valuenew->name)),
                        $notification->url, $notification->params);
}

// Display page header
echo $OUTPUT->header();

if ($id == 0) {
    echo $OUTPUT->heading(get_string('addnewscalevalue', 'totara_hierarchy'));
} else {
    echo $OUTPUT->heading(get_string('editscalevalue', 'totara_hierarchy'));
}

// Display warning if scale is in use
if ($scale_used) {
    echo $OUTPUT->container(get_string('competencyscaleinuse', 'totara_hierarchy'), 'notifysuccess');
}

$valueform->display();

/// and proper footer
echo $OUTPUT->footer();
