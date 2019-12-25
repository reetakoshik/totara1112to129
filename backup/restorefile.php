<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Import backup file or select existing backup file from moodle
 * @package   core_backup
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @author    Petr Skoda <petr.skoda@totaralearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once(__DIR__ . '/restorefile_form.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

// current context
$contextid = required_param('contextid', PARAM_INT);
list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);

$syscontext = context_system::instance();
$usercontext = context_user::instance($USER->id);
if (!has_capability('moodle/backup:downloadfile', $context)) {
    require_capability('moodle/restore:restorefile', $context);
}

// NOTE: the access control logic must match \restore_ui_stage_confirm::get_backup_file(),
//       /lib/filelib.php and /lib/filebrowser/* files.

switch ($context->contextlevel) {
    case CONTEXT_MODULE:
        $heading = get_string('restoreactivity', 'backup');
        break;
    case CONTEXT_COURSE:
    default:
        $heading = get_string('restorecourse', 'backup');
}

if (is_null($course)) {
    $coursefullname = $SITE->fullname;
} else {
    $coursefullname = $course->fullname;
}

$config = get_config('backup');

$PAGE->set_url('/backup/restorefile.php', array('contextid' => $contextid));
$PAGE->set_context($context);
$PAGE->set_title(get_string('course') . ': ' . $coursefullname);
$PAGE->set_heading($heading);
$PAGE->set_pagelayout('admin');

// Delete file if requested, the confirmation is via JS dialog.
if (data_submitted()) {
    $deletefileid = optional_param('deletefileid', false, PARAM_INT);
    if ($deletefileid and confirm_sesskey()) {
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($deletefileid);
        if ($file and !$file->is_directory()) {
            if ($file->get_contextid() == $context->id and $file->get_component() === 'backup') {
                if ($file->get_filearea() === 'automated') {
                    // No need for testing if automated backup enabled here.
                    require_capability('moodle/restore:viewautomatedfilearea', $context);
                    require_capability('moodle/site:config', $context);
                    require_capability('moodle/backup:deletebackupfiles', $context);
                } else if ($file->get_filearea() === 'course' or $file->get_filearea() === 'activity') {
                    if (!has_capability('moodle/backup:managebackupfiles', $context)) {
                        require_capability('moodle/backup:deletebackupfiles', $context);
                    }
                } else {
                    throw new invalid_parameter_exception('unknown file area to delete');
                }
                $file->delete();
            } if ($file->get_contextid() == $usercontext->id and $file->get_component() === 'user') {
                if ($file->get_filearea() === 'backup') {
                    $file->delete();
                }
            }
        }
        redirect($PAGE->url);
    }
}

// Require upload file cap to use file picker.
if (has_capability('moodle/restore:restorefile', $context) and has_capability('moodle/restore:uploadfile', $context)) {
    $form = new course_restore_form(null, array('contextid'=>$contextid));
    if ($form->get_data()) {
        $fs = get_file_storage();
        $draftid = file_get_submitted_draft_itemid('backupfile');
        $files = $fs->get_area_files($usercontext->id, 'user' ,'draft', $draftid, 'id DESC', false);
        if ($files) {
            $file = reset($files);
            // NOTE: sesskey in URL is not good, luckily we now have the referrer turned off in decent browsers,
            //       otherwise we would have to reimplement the form in ajaxy Totara forms...
            $restoreurl = new moodle_url('/backup/restore.php', array('contextid' => $contextid, 'backupfileid' => $file->get_id(), 'sesskey' => sesskey()));
            redirect($restoreurl);
        }
    }
}

/** @var core_backup_renderer $renderer */
$renderer = $PAGE->get_renderer('core', 'backup');

// TOTARA: Add button to add/remove for quickaccess menu
if ($context->contextlevel == CONTEXT_SYSTEM) {
    \totara_core\quickaccessmenu\helper::add_quickaction_page_button($PAGE, 'restorecourse');
}

/** @var core_renderer $OUTPUT */
echo $OUTPUT->header();

if (has_capability('moodle/restore:restorefile', $context) and has_capability('moodle/restore:uploadfile', $context)) {
    echo $OUTPUT->heading(get_string('importfile', 'backup'));
    echo $OUTPUT->container_start();
    $form->display();
    echo $OUTPUT->container_end();
}

if ($context->contextlevel == CONTEXT_MODULE) {
    echo $OUTPUT->heading_with_help(get_string('choosefilefromactivitybackup', 'backup'), 'choosefilefromactivitybackup', 'backup');
    echo $OUTPUT->container_start();
    $treeview_options = array();
    $treeview_options['filecontext'] = $context;
    $treeview_options['currentcontext'] = $context;
    $treeview_options['component']   = 'backup';
    $treeview_options['filearea']    = 'activity';
    $treeview_options['candownload'] = (has_capability('moodle/backup:downloadfile', $context) or has_capability('moodle/backup:managebackupfiles', $context));
    $treeview_options['canrestore']  = has_capability('moodle/restore:restorefile', $context);
    $treeview_options['allowuntrusted'] = has_capability('moodle/restore:restoreuntrusted', $context);
    $treeview_options['canmanage']   = has_capability('moodle/backup:managebackupfiles', $context);
    $treeview_options['candelete']   = has_capability('moodle/backup:deletebackupfiles', $context) or has_capability('moodle/backup:managebackupfiles', $context);
    echo $renderer->backup_files_viewer($treeview_options);
    echo $OUTPUT->container_end();
}

if ($context->contextlevel == CONTEXT_COURSE) {
    echo $OUTPUT->heading_with_help(get_string('choosefilefromcoursebackup', 'backup'), 'choosefilefromcoursebackup', 'backup');
    echo $OUTPUT->container_start();
    $treeview_options = array();
    $treeview_options['filecontext'] = $context;
    $treeview_options['currentcontext'] = $context;
    $treeview_options['component']   = 'backup';
    $treeview_options['filearea']    = 'course';
    $treeview_options['candownload'] = (has_capability('moodle/backup:downloadfile', $context) or has_capability('moodle/backup:managebackupfiles', $context));
    $treeview_options['canrestore']  = has_capability('moodle/restore:restorefile', $context);
    $treeview_options['allowuntrusted'] = has_capability('moodle/restore:restoreuntrusted', $context);
    $treeview_options['canmanage']   = has_capability('moodle/backup:managebackupfiles', $context);
    $treeview_options['candelete']   = has_capability('moodle/backup:deletebackupfiles', $context) or has_capability('moodle/backup:managebackupfiles', $context);
    echo $renderer->backup_files_viewer($treeview_options);
    echo $OUTPUT->container_end();
}

echo $OUTPUT->heading_with_help(get_string('choosefilefromuserbackup', 'backup'), 'choosefilefromuserbackup', 'backup');
echo $OUTPUT->container_start();
$treeview_options = array();
$treeview_options['filecontext'] = $usercontext;
$treeview_options['currentcontext'] = $context;
$treeview_options['component']   = 'user';
$treeview_options['filearea']    = 'backup';
$treeview_options['candownload'] = true; // There is no way to restrict this, sorry.
$treeview_options['canrestore']  = has_capability('moodle/restore:restorefile', $context);
$treeview_options['allowuntrusted'] = has_capability('moodle/restore:restoreuntrusted', $context);
$treeview_options['canmanage']   = true; // There is no way to restrict this, sorry.
$treeview_options['candelete']   = true;
echo $renderer->backup_files_viewer($treeview_options);
echo $OUTPUT->container_end();

if ($config->backup_auto_active != 0 and $context->contextlevel == CONTEXT_COURSE and has_capability('moodle/restore:viewautomatedfilearea', $context)) {
    if ($config->backup_auto_storage == backup_cron_automated_helper::STORAGE_DIRECTORY) {
        if ($config->backup_auto_destination and file_exists($config->backup_auto_destination)) {
            echo $OUTPUT->heading_with_help(get_string('choosefilefromautomatedbackup', 'backup'), 'choosefilefromautomatedbackup', 'backup');
            echo $OUTPUT->container_start();
            $options = array();
            $options['currentcontext'] = $context;
            $options['canrestore']  = has_capability('moodle/restore:restorefile', $context);
            $options['allowuntrusted'] = has_capability('moodle/restore:restoreuntrusted', $context);
            $options['autodestination'] = $config->backup_auto_destination;
            echo $renderer->backup_external_files_viewer($options);
            echo $OUTPUT->container_end();
        }
    } else {
        echo $OUTPUT->heading_with_help(get_string('choosefilefromautomatedbackup', 'backup'), 'choosefilefromautomatedbackup', 'backup');
        echo $OUTPUT->container_start();
        $treeview_options = array();
        $treeview_options['filecontext'] = $context;
        $treeview_options['currentcontext'] = $context;
        $treeview_options['component']   = 'backup';
        $treeview_options['filearea']    = 'automated';
        $treeview_options['candownload'] = has_capability('moodle/backup:downloadfile', $context);
        $treeview_options['canrestore']  = has_capability('moodle/restore:restorefile', $context);
        $treeview_options['allowuntrusted'] = has_capability('moodle/restore:restoreuntrusted', $context);
        $treeview_options['canmanage']   = false; // Users cannot manage automated backups, because it would break the automatic cleanups!
        $treeview_options['candelete']   = (has_capability('moodle/site:config', $syscontext) and has_capability('moodle/backup:deletebackupfiles', $context));
        echo $renderer->backup_files_viewer($treeview_options);
        echo $OUTPUT->container_end();
    }
}

echo $OUTPUT->footer();
