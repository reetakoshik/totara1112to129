<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

require('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/scorm/locallib.php');

use contentmarketplace_goone\contentmarketplace;
use contentmarketplace_goone\form\create_course_form;
use contentmarketplace_goone\form\create_course_controller;

$category = optional_param('category', 0, PARAM_INT);
$selection = required_param_array('selection', PARAM_ALPHANUMEXT);
$create = optional_param('create', create_course_form::CREATE_COURSE_MULTI_ACTIVITY, PARAM_INT);
$category = optional_param('category', 0, PARAM_INT);
$mode = optional_param('mode', \totara_contentmarketplace\explorer::MODE_CREATE_COURSE, PARAM_ALPHAEXT);

if ($category === 0) {
    $context = context_system::instance();
    $pageparams = [];
} else {
    $context = context_coursecat::instance($category);
    $pageparams = ['category' => $category];
}
$PAGE->set_context($context);
$PAGE->set_url(new \moodle_url('/totara/contentmarketplace/contentmarketplaces/goone/coursecreate.php', $pageparams));

require_login();
require_capability('totara/contentmarketplace:add', $context);

// Check marketplaces are enabled.
\totara_contentmarketplace\local::require_contentmarketplace();

// Check Go1 marketplace plugin is enabled.
/** @var \totara_contentmarketplace\plugininfo\contentmarketplace $plugin */
$plugin = \core_plugin_manager::instance()->get_plugin_info("contentmarketplace_goone");
if ($plugin === null) {
    throw new coding_exception('The contentmarketplace_goone plugin is not yet installed.');
}
if (!$plugin->is_enabled()) {
    throw new \moodle_exception('error:disabledmarketplace', 'totara_contentmarketplace', '', $plugin->displayname);
}

$PAGE->set_title(get_string('addcourse', 'contentmarketplace_goone'));
$PAGE->set_pagelayout('noblocks');

/**
 * Check this GO1 account can access the learning object.
 * (Used to guard against unexpected learning objects being used to create content.)
 *
 * @param array $ids
 * @param context $context
 * @throws moodle_exception
 * @return bool always true
 */
function check_availability_of_learning_objects($ids, $context) {
    $api = new \contentmarketplace_goone\api();
    foreach ($ids as $id) {
        // Throws API exception if learning object $id does not exist or is not available.
        $api->get_learning_object($id);
    }
    return true;
}


function storedfile($name, $packageid, $scorm) {
    global $USER;

    $fs = get_file_storage();

    $itemid = file_get_unused_draft_itemid();
    $usercontext = context_user::instance($USER->id);
    $now = time();

    /** @var totara_contentmarketplace\plugininfo\contentmarketplace $plugin */
    $plugin = core_plugin_manager::instance()->get_plugin_info("contentmarketplace_goone");
    $marketplace = $plugin->contentmarketplace();

    // Prepare file record.
    $record = new stdClass();
    $record->filepath = "/";
    $record->filename = clean_filename($name . ".zip");
    $record->component = 'user';
    $record->filearea = 'draft';
    $record->itemid = $itemid;
    $record->license = "allrightsreserved";
    $record->author = "Content Marketplace";
    $record->contextid = $usercontext->id;
    $record->timecreated = $now;
    $record->timemodified = $now;
    $record->userid = $USER->id;
    $record->sortorder = 0;
    $record->source = $marketplace->get_source($packageid, $name);

    return $fs->create_file_from_string($record, $scorm);
}


function add_scorm_module($course, $name, $itemid, $descriptionhtml, $assessable, $section = 0) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/scorm/lib.php');

    $moduleinfo = new \stdClass();
    $moduleinfo->name = $name;
    $moduleinfo->modulename = 'scorm';
    $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'scorm'], MUST_EXIST);
    $moduleinfo->cmidnumber = "";

    $moduleinfo->visible = 1;
    $moduleinfo->section = $section;

    $moduleinfo->intro = $descriptionhtml;
    $moduleinfo->introformat = FORMAT_HTML;

    $moduleinfo->popup = 1;
    $moduleinfo->width = 100;
    $moduleinfo->height = 100;
    $moduleinfo->skipview = 2;
    $moduleinfo->hidebrowse = 1;
    $moduleinfo->displaycoursestructure = 0;
    $moduleinfo->hidetoc = 3;
    $moduleinfo->nav = 1;
    $moduleinfo->displayactivityname = false;
    $moduleinfo->displayattemptstatus = 1;
    $moduleinfo->forcenewattempt = 1;
    $moduleinfo->maxattempt = 0;

    $moduleinfo->scormtype = SCORM_TYPE_LOCAL;

    $api = new \contentmarketplace_goone\api();
    $scormzip = $api->get_scorm($itemid);
    $moduleinfo->packagefile = storedfile($name, $itemid, $scormzip)->get_itemid();

    if ($assessable) {
        $moduleinfo->grademethod = GRADEHIGHEST;
        $moduleinfo->maxgrade = 100;
        $moduleinfo->completion = COMPLETION_TRACKING_AUTOMATIC;
        $moduleinfo->completionscoredisabled = 1;
        $moduleinfo->completionstatusrequired = get_completionstatusrequired('passed');
    } else {
        $moduleinfo->grademethod = GRADESCOES;
        $moduleinfo->completion = COMPLETION_TRACKING_AUTOMATIC;
        $moduleinfo->completionscoredisabled = 1;
        $moduleinfo->completionstatusrequired = get_completionstatusrequired('completed');
    }

    return add_moduleinfo($moduleinfo, $course);
}

function get_completionstatusrequired($option) {
    foreach (scorm_status_options() as $key => $value) {
        if ($value == $option) {
            return $key;
        }
    }
    throw new \coding_exception('Unknown completionstatus option: ' . $option);
}

function enrol_course_creator($course) {
    global $CFG, $USER;
    $context = context_course::instance($course->id, MUST_EXIST);
    if (!empty($CFG->creatornewroleid) and !is_viewing($context, NULL, 'moodle/role:assign') and !is_enrolled($context, NULL, 'moodle/role:assign')) {
        // Deal with course creators - enrol them internally with default role.
        enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);
    }
}

list($currentdata, $params) = create_course_controller::get_current_data_and_params($selection, $create, $category, $mode);
$form = new create_course_form($currentdata, $params);

if ($form->is_cancelled()) {
    $url = new moodle_url('/totara/contentmarketplace/explorer.php', ['marketplace' => 'goone', 'mode' => $mode]);
    if (!empty($category)) {
        $url->param('category', $category);
    }
    redirect($url);
} else if ($data = $form->get_data()) {
    require_once($CFG->dirroot.'/course/modlib.php');

    $selection = $data->selection;
    check_availability_of_learning_objects($selection, $context);

    if (count($selection) == 1 || $data->create == create_course_form::CREATE_COURSE_MULTI_ACTIVITY) {
        $coursedata = new \stdClass();
        $suffix = count($selection) == 1 ? '_' .$selection[0] : '';
        $coursedata->category = $data->{'category' . $suffix};
        $coursedata->fullname = $data->{'fullname' . $suffix};
        $coursedata->shortname = $data->{'shortname' . $suffix};
        $coursedata->visible = true;

        $coursedata->enablecompletion = COMPLETION_ENABLED;
        $coursedata->completionstartonenrol = 1;

        if ($data->create == create_course_form::CREATE_COURSE_SINGLE_ACTIVITY) {
            $coursedata->format = 'singleactivity';
            $coursedata->activitytype = 'scorm';
            $section = 0;
        } else {
            $section = 1;
        }

        $course = \create_course($coursedata);
        enrol_course_creator($course);

        $api = new \contentmarketplace_goone\api();
        foreach ($selection as $id) {
            $learningobject = $api->get_learning_object($id);
            $title = clean_param($learningobject->title, !empty($CFG->formatstringstriptags) ? PARAM_TEXT : PARAM_CLEANHTML);
            $descriptionhtml = clean_text($learningobject->description);
            add_scorm_module($course, $title, $id, $descriptionhtml, $learningobject->assessable, $section);
        }

        \core\notification::success(get_string('coursecreated', 'contentmarketplace_goone'));

        $coursecontext = context_course::instance($course->id, MUST_EXIST);
        $isviewing = is_viewing($coursecontext, NULL, 'moodle/role:assign');
        $isenrolled = is_enrolled($coursecontext, NULL, 'moodle/role:assign');
        if ($isviewing || $isenrolled) {
            $url = new \moodle_url('/course/view.php', ['id' => $course->id]);
        } else {
            $url = new \moodle_url('/course/index.php', ['categoryid' => $coursedata->category]);
        }
        redirect($url);

    } else {
        $courselinkshtml = [];
        foreach ($selection as $id) {
            $coursedata = new \stdClass();
            $coursedata->category = $data->{'category_' . $id};
            $coursedata->fullname = $data->{'fullname_' . $id};
            $coursedata->shortname = $data->{'shortname_' . $id};
            $coursedata->visible = true;

            $coursedata->enablecompletion = COMPLETION_ENABLED;
            $coursedata->completionstartonenrol = 1;

            $coursedata->format = 'singleactivity';
            $coursedata->activitytype = 'scorm';
            $course = \create_course($coursedata);
            enrol_course_creator($course);

            $api = new \contentmarketplace_goone\api();
            $learningobject = $api->get_learning_object($id);
            $title = clean_param($learningobject->title, !empty($CFG->formatstringstriptags) ? PARAM_TEXT : PARAM_CLEANHTML);
            $descriptionhtml = clean_text($learningobject->description);
            add_scorm_module($course, $title, $id, $descriptionhtml, $learningobject->assessable);

            $courselinkshtml[] = s($coursedata->fullname);
        }

        $messagehtml = html_writer::tag('p', get_string('coursecreatedx', 'contentmarketplace_goone', count($selection)));
        $messagehtml .= html_writer::alist($courselinkshtml);
        \core\notification::success($messagehtml);
        $category = $data->{'category_' . $selection[0]};
        redirect(new \moodle_url('/course/index.php', ['categoryid' => $category]));
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('addcourse', 'contentmarketplace_goone'));

echo $form->render();

echo $OUTPUT->footer();
