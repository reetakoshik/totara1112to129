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
 * Scheduled task admin pages.
 *
 * @package    tool_task
 * @copyright  2013 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');

$PAGE->set_url('/admin/tool/task/scheduledtasks.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$strheading = get_string('scheduledtasks', 'tool_task');
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

require_login();

require_capability('moodle/site:config', context_system::instance());

$renderer = $PAGE->get_renderer('tool_task');

$action = optional_param('action', '', PARAM_ALPHAEXT);
$taskname = optional_param('task', '', PARAM_RAW);
$nextcronall = optional_param('nextcronall', false, PARAM_ALPHANUMEXT);
$nextcron = optional_param('nextcron', false, PARAM_RAW);
$task = null;
$mform = null;

if (!empty($CFG->debugallowscheduledtaskoverride) && ($nextcronall || $nextcron) && confirm_sesskey()) {
    $updatesql = "UPDATE {task_scheduled}
                     SET nextruntime = 0,
                         lastruntime = CASE WHEN lastruntime < :now THEN lastruntime ELSE 0 END";
    $now = time();

    // Set all non-disabled scheduled tasks to run on the next cron run.
    if ($nextcronall) {
        $DB->execute($updatesql . " WHERE disabled = 0", array('now' => $now));
    }

    // Set the specified scheduled task to run on the next cron run.
    if ($nextcron) {
        $task = \core\task\manager::get_scheduled_task($nextcron);
        if (!$task) {
            print_error('invaliddata');
        }
        $DB->execute($updatesql . " WHERE classname = :classname",
            array('now' => $now, 'classname' => '\\' . get_class($task)));
    }

    redirect(new moodle_url('/admin/tool/task/scheduledtasks.php'));
}

if ($taskname) {
    $task = \core\task\manager::get_scheduled_task($taskname);
    if (!$task) {
        print_error('invaliddata');
    }
}

if ($action == 'edit') {
    $PAGE->navbar->add(get_string('edittaskschedule', 'tool_task', $task->get_name()));
}

if ($task) {
    $mform = new tool_task_edit_scheduled_task_form(null, $task);
}

if ($mform && ($mform->is_cancelled() || !empty($CFG->preventscheduledtaskchanges))) {
    redirect(new moodle_url('/admin/tool/task/scheduledtasks.php'));
} else if ($action == 'edit' && empty($CFG->preventscheduledtaskchanges)) {

    if ($data = $mform->get_data()) {


        if ($data->resettodefaults) {
            $defaulttask = \core\task\manager::get_default_scheduled_task($taskname);
            $task->set_minute($defaulttask->get_minute());
            $task->set_hour($defaulttask->get_hour());
            $task->set_month($defaulttask->get_month());
            $task->set_day_of_week($defaulttask->get_day_of_week());
            $task->set_day($defaulttask->get_day());
            $task->set_disabled($defaulttask->get_disabled());
            $task->set_customised(false);
        } else {
            $task->set_minute($data->minute);
            $task->set_hour($data->hour);
            $task->set_month($data->month);
            $task->set_day_of_week($data->dayofweek);
            $task->set_day($data->day);
            $task->set_disabled($data->disabled);
            $task->set_customised(true);
        }

        try {
            \core\task\manager::configure_scheduled_task($task);
            \tool_task\event\scheduled_task_updated::create_from_schedule($task)->trigger();
            redirect($PAGE->url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (Exception $e) {
            redirect($PAGE->url, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('edittaskschedule', 'tool_task', $task->get_name()));
        $mform->display();
        echo $OUTPUT->footer();
    }

} else {
    // TOTARA: add a button to add/remove the scheduledtasks externalpage to the users quick access menu.
    \totara_core\quickaccessmenu\helper::add_quickaction_page_button($PAGE, 'scheduledtasks');

    echo $OUTPUT->header();

    // Check if the cron has run recently, if not notify the admin about configuration recommendations.
    $lastrun = $DB->get_field_sql('SELECT MAX(lastruntime) FROM {task_scheduled}');
    if ($lastrun < time() - (MINSECS * 5)) {
        echo $OUTPUT->notification(get_string('cronscheduleregularity', 'totara_core'), 'notifynotice');
    }

    $tasks = core\task\manager::get_all_scheduled_tasks();
    echo $renderer->scheduled_tasks_table($tasks);
    echo $OUTPUT->footer();
}
