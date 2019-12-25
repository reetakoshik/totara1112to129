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
 * This script is used to configure and execute the restore proccess.
 *
 * @package    core_backup
 * @copyright  Moodle
 * @author     Petr Skoda <petr.skoda@totaralearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once('../config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

$contextid = required_param('contextid', PARAM_INT);
$stage     = optional_param('stage', restore_ui::STAGE_CONFIRM, PARAM_INT);
$restoreid = optional_param('restore', false, PARAM_ALPHANUM);
$cancel    = optional_param('cancel', false, PARAM_RAW); // Cancel button in real forms or single button url.

list($context, $course, $cm) = get_context_info_array($contextid);

navigation_node::override_active_url(new moodle_url('/backup/restorefile.php', array('contextid' => $contextid)));
$PAGE->set_url('/backup/restore.php', array('contextid' => $contextid));
$PAGE->set_context($context);

require_login($course, null, $cm);
require_capability('moodle/restore:restorefile', $context);
require_sesskey();

// Totara: This has to come after require_login(), which sets page layout to course.
if (!$stage) {
    $PAGE->set_pagelayout('admin');
} else {
    // Better not show block with navigation, they should use buttons at the bottom!
    $PAGE->set_pagelayout('noblocks');
}

if ($restoreid) {
    $rc = restore_ui::load_controller($restoreid);
    if (!$rc) {
        // Somebody is probably hitting Back and Forward buttons
        redirect(new moodle_url('/backup/restorefile.php', array('contextid' => $contextid)));
    }
    if ($rc->get_userid() != $USER->id) {
        throw new invalid_parameter_exception('Restore id does not belong to this user');
    }
    if ($rc->get_status() == backup::STATUS_FINISHED_OK) {
        redirect(new moodle_url('/course/view.php', array('id' => $rc->get_courseid())));
    }
    if ($rc->get_status() == backup::STATUS_FINISHED_ERR) {
        redirect(new moodle_url('/backup/restorefile.php', array('contextid' => $contextid)));
    }
} else {
    $rc = false;
}

if (!empty($cancel)) {
    $PAGE->set_cacheable(false);
    ignore_user_abort(true);
    if ($rc) {
        $rc->cancel_restore(true);
    }
    redirect(new moodle_url('/backup/restorefile.php', array('contextid' => $contextid)));
}

if (is_null($course)) {
    $coursefullname = $SITE->fullname;
    $courseshortname = $SITE->shortname;
} else {
    $coursefullname = $course->fullname;
    $courseshortname = $course->shortname;
}

$PAGE->set_title($courseshortname . ': ' . get_string('restore'));
$PAGE->set_heading($coursefullname);

/** @var core_backup_renderer $renderer */
$renderer = $PAGE->get_renderer('core','backup');

/** @var core_renderer $OUTPUT */
echo $OUTPUT->header();

// Prepare a progress bar which can display optionally during long-running
// operations while setting up the UI.
$slowprogress = new \core\progress\display_if_slow(get_string('preparingui', 'backup'));

// Overall, allow 10 units of progress.
$slowprogress->start_progress('', 10);

// This progress section counts for loading the restore controller.
$slowprogress->start_progress('', 1, 1);

// Restore of large courses requires extra memory. Use the amount configured
// in admin settings.
raise_memory_limit(MEMORY_EXTRA);

if ($rc) {
    // Check if the format conversion must happen first.
    if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
        $rc->convert();
    }
    $restore = new restore_ui($rc, array('contextid' => $context->id));

} else {
    $destination = optional_param('destination', null, PARAM_ALPHA);
    $searchcourses = optional_param('searchcourses', 0, PARAM_BOOL);
    $targetid = optional_param('targetid', 0, PARAM_INT);
    $continue = optional_param('continue', 0, PARAM_BOOL);
    $missingdata = false;

    if (!$stage or !$destination) {
        $stage = restore_ui::STAGE_CONFIRM;
    }
    if ($stage == restore_ui::STAGE_SETTINGS and (!$targetid or $searchcourses or !$continue)) {
        $stage = restore_ui::STAGE_DESTINATION;
    }

    if ($stage == restore_ui::STAGE_CONFIRM) {
        $restore = new restore_ui_stage_confirm($contextid);
    } else if ($stage == restore_ui::STAGE_DESTINATION) {
        $restore = new restore_ui_stage_destination($contextid);
    } else if ($stage == restore_ui::STAGE_SETTINGS) {
        // Finally extract the archive into temp directory and create a new controller.
        $restore = new restore_ui_stage_destination($contextid);
        $restore->set_progress_reporter($slowprogress);
        $rc = $restore->create_restore_controller();
        // Check if the format conversion must happen first.
        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
            $rc->convert();
        }
        $restore = new restore_ui($rc, array('contextid' => $context->id));
    } else {
        throw new restore_ui_exception('unknownuistage');
    }
}

// End progress section for loading restore controller.
$slowprogress->end_progress();

// This progress section is for the 'process' function below.
$slowprogress->start_progress('', 1, 9);

// Depending on the code branch above, $restore may be a restore_ui or it may
// be a restore_ui_independent_stage. Either way, this function exists.
$restore->set_progress_reporter($slowprogress);
$outcome = $restore->process();

if (!$restore->is_independent() && $restore->enforce_changed_dependencies()) {
    debugging('Your settings have been altered due to unmet dependencies', DEBUG_DEVELOPER);
}

$loghtml = '';
// Finish the 'process' progress reporting section, and the overall count.
$slowprogress->end_progress();
$slowprogress->end_progress();

if (!$restore->is_independent()) {
    // Use a temporary (disappearing) progress bar to show the precheck progress if any.
    $precheckprogress = new \core\progress\display_if_slow(get_string('preparingdata', 'backup'));
    $restore->get_controller()->set_progress($precheckprogress);
    if ($restore->get_stage() == restore_ui::STAGE_PROCESS && !$restore->requires_substage()) {
        try {
            // Div used to hide the 'progress' step once the page gets onto 'finished'.
            echo html_writer::start_div('', array('id' => 'executionprogress'));
            // Show the current restore state (header with bolded item).
            echo $renderer->progress_bar($restore->get_progress_bar());
            // Start displaying the actual progress bar percentage.
            $restore->get_controller()->set_progress(new \core\progress\display());
            // Prepare logger.
            $logger = new core_backup_html_logger($CFG->debugdeveloper ? backup::LOG_DEBUG : backup::LOG_INFO);
            $restore->get_controller()->add_logger($logger);
            // Do actual restore.
            $restore->execute();
            // Get HTML from logger.
            if ($CFG->debugdisplay) {
                $loghtml = $logger->get_html();
            }
            // Hide this section because we are now going to make the page show 'finished'.
            echo html_writer::end_div();
            echo html_writer::script('document.getElementById("executionprogress").style.display = "none";');
        } catch(Exception $e) {
            // Better reload the controller from database again.
            $restore->get_controller()->destroy();
            if ($rc = restore_ui::load_controller($restoreid)) {
                $rc->cancel_restore(false);
            }
            throw $e;
        }
    } else {
        $restore->save_controller();
    }
}

echo $renderer->progress_bar($restore->get_progress_bar());
echo $restore->display($renderer);
$restore->destroy();
unset($restore);

// Display log data if there was any.
if ($loghtml != '') {
    echo $renderer->log_display($loghtml);
}

echo $OUTPUT->footer();
