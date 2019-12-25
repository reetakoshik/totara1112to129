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
 * Strings for component 'tool_task', language 'en'
 *
 * @package    tool_task
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['asap'] = 'ASAP';
$string['blocking'] = 'Blocking';
$string['component'] = 'Component';
$string['corecomponent'] = 'Core';
$string['default'] = 'Default';
$string['disabled'] = 'Disabled';
$string['disabled_help'] = 'Disabled scheduled tasks are not executed from cron, however they can still be executed manually via the CLI tool.';
$string['edittaskschedule'] = 'Edit task schedule: {$a}';
$string['eventscheduledtaskupdated'] = 'Scheduled task updated';
$string['faildelay'] = 'Fail delay';
$string['lastruntime'] = 'Last run';
$string['nextruntime'] = 'Next run';
$string['plugindisabled'] = 'Plugin disabled';
$string['pluginname'] = 'Scheduled task configuration';
$string['resettasktodefaults'] = 'Reset task schedule to defaults';
$string['resettasktodefaults_help'] = 'This will discard any local changes and revert the schedule for this task back to its original settings.';
$string['nextcron'] = 'Run next cron';
$string['nextcronall'] = 'Set all enabled tasks to run on next cron';
$string['nextcrontask'] = 'Run task \'{$a}\' on the next cron run';
$string['scheduledtasks'] = 'Scheduled tasks';
$string['scheduledtaskchangesdisabled'] = 'Modifications to the list of scheduled tasks have been prevented in Totara configuration';
$string['taskdisabled'] = 'Task disabled';
$string['taskscheduleday'] = 'Day';
$string['taskscheduleday_help'] = 'Day of month field for task schedule. The field uses the same format as unix cron. Some examples are:

* **&#42;** - Every day
* ***/2** - Every 2nd day
* **1** - The first of every month
* **1,15** - The first and fifteenth of every month';
$string['taskscheduledayofweek'] = 'Day of week';
$string['taskscheduledayofweek_help'] = 'Day of week field for task schedule. The field uses the same format as unix cron. Some examples are:

* **&#42;** - Every day
* **0** - Every Sunday
* **6** - Every Saturday
* **1,5** - Every Monday and Friday';
$string['taskschedulehour'] = 'Hour';
$string['taskschedulehour_help'] = 'Hour field for task schedule. The field uses the same format as unix cron. Some examples are:

* **&#42;** - Every hour
* ***/2** - Every 2 hours</li>
* **2-10** - Every hour from 2am until 10am (inclusive)
* **2,6,9** - 2am, 6am and 9am';
$string['taskscheduleminute'] = 'Minute';
$string['taskscheduleminute_help'] = 'Minute field for task schedule. The field uses the same format as unix cron. Some examples are:

* **&#42;** - Every minute
* ***/5** - Every 5 minutes
* **2-10** - Every minute between 2 and 10 past the hour (inclusive)
* **2,6,9** - 2 6 and 9 minutes past the hour';
$string['taskschedulemonth'] = 'Month';
$string['taskschedulemonth_help'] = 'Month field for task schedule. The field uses the same format as unix cron. Some examples are:

* **&#42;** - Every month
* ***/2** - Every second month
* **1** - Every January
* **1,5** - Every January and May';