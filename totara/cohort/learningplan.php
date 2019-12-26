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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara
 * @subpackage cohort
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/cohort/cohort_forms.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/totara/plan/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

$context = context_system::instance();
require_capability('moodle/cohort:view', $context);
require_capability('totara/plan:cancreateplancohort', $context);

// Raise timelimit as this could take a while for big cohorts
core_php_time_limit::raise(0);
raise_memory_limit(MEMORY_HUGE);

define('COHORT_HISTORY_PER_PAGE', 50);

$id = required_param('id', PARAM_INT);

$url = new moodle_url('/totara/cohort/learningplan.php', array('id' => $id));
admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout'=>'report'));

$cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url('/totara/cohort/learningplan.php', array('id' => $id));

$currenturl = qualified_me();

// Javascript include
local_js(array(TOTARA_JS_DIALOG));

$PAGE->requires->strings_for_js(array('confirmcreateplans'), 'totara_plan');
$PAGE->requires->strings_for_js(array('taskplanswillbecreated'), 'totara_cohort');
$PAGE->requires->strings_for_js(array('continue', 'cancel'), 'moodle');
$args = array('args' => '{"id":"'.$cohort->id.'"}');
$jsmodule = array(
        'name' => 'totara_cohortplans',
        'fullpath' => '/totara/cohort/dialog/learningplan.js',
        'requires' => array('json', 'totara_core'));
$PAGE->requires->js_init_call('M.totara_cohortplans.init', $args, false, $jsmodule);

$form = new cohort_learning_plan_settings_form($currenturl, array('data' => $cohort));

// Get a learning plan config object.
$planconfig = \totara_cohort\learning_plan_config::get_config($id);

$form->set_data($planconfig->get_record());

if ($data = $form->get_data()) {
    if (isset($data->submitbutton)) {

        // Update the plan configuration.
        $planconfig->excludecreatedauto = (bool)$data->excludecreatedauto;
        $planconfig->excludecreatedmanual = (bool)$data->excludecreatedmanual;
        $planconfig->excludecompleted = (bool)$data->excludecompleted;
        $planconfig->autocreatenew = (bool)$data->autocreatenew;
        $planconfig->planstatus = $data->planstatus;
        $planconfig->plantemplateid = $data->plantemplateid;
        $planconfig->save();

        // Create the plans on an adhoc scheduled task.
        $adhoctask = new \totara_cohort\task\create_learning_plans_task();
        $adhoctask->set_custom_data(array('config' => $planconfig, 'userid' => $USER->id));
        $adhoctask->set_component('totara_cohort');
        \core\task\manager::queue_adhoc_task($adhoctask);

        totara_set_notification(get_string('saved', 'totara_cohort'), null, array('class' => 'notifysuccess'));
        totara_set_notification(get_string('taskplanswillbecreated', 'totara_cohort'), null, array('class' => 'notifysuccess'));
    }
}

$strheading = get_string('learningplan', 'totara_cohort');
totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($cohort->name));
echo cohort_print_tabs('plans', $cohort->id, $cohort->cohorttype, $cohort);

echo $OUTPUT->heading(get_string('createlpforaudience', 'totara_cohort'));

echo get_string('createlpforaudienceblurb', 'totara_cohort');

echo $form->display();

$tableheaders = array(
    get_string('template', 'totara_core'),
    get_string('user'),
    get_string('date'),
    get_string('planstatus', 'totara_plan'),
    get_string('numaffectedusers', 'totara_plan'),
    get_string('manuallycreated', 'totara_plan'),
    get_string('autocreated', 'totara_plan'),
    get_string('complete')
);
$tablecolumns = array(
    'template',
    'user',
    'date',
    'planstatus',
    'numusers',
    'manual',
    'auto',
    'complete'
);

$table = new flexible_table('cohortplancreatehistory');
$table->define_baseurl(qualified_me());
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->attributes['id'] = 'cohortplancreatehistory';

$table->attributes['class'] = 'fullwidth';

$table->setup();

echo $OUTPUT->heading(get_string('history', 'totara_cohort'));

$usernamefields = get_all_user_name_fields(true, 'u');
$history_sql = "SELECT cph.id,
                       t.fullname as template,
                       {$usernamefields},
                       cph.planstatus,
                       cph.affectedusers,
                       cph.timecreated,
                       cph.manual,
                       cph.auto,
                       cph.completed
                    FROM {cohort_plan_history} cph
                    JOIN {user} u
                        ON cph.usercreated = u.id
                    JOIN {dp_template} t
                        ON cph.templateid = t.id
                        WHERE cph.cohortid = ?
                    ORDER BY
                        cph.timecreated DESC, cph.id ASC";

$perpage = COHORT_HISTORY_PER_PAGE;

$countsql = 'SELECT COUNT(*) FROM
                {cohort_plan_history} cph
            JOIN {user} u
              ON cph.usercreated = u.id
            JOIN {dp_template} t
              ON cph.templateid = t.id
            WHERE cph.cohortid = ?';

$totalcount = $DB->count_records_sql($countsql, array($cohort->id));

$table->initialbars($totalcount > $perpage);
$table->pagesize($perpage, $totalcount);

if ($history_records = $DB->get_records_sql($history_sql, array($cohort->id), $table->get_page_start(), $table->get_page_size())) {
    foreach ($history_records as $record) {
        $row = array();

        $row[] = $record->template;
        $fullname = fullname($record);
        $row[] = $fullname;
        unset($user);
        $row[] = userdate($record->timecreated, get_string('strfdateattime', 'langconfig'));
        switch ($record->planstatus) {
            case DP_PLAN_STATUS_UNAPPROVED:
                $status = get_string('unapproved', 'totara_plan');
                break;

            case DP_PLAN_STATUS_APPROVED:
                $status = get_string('approved', 'totara_plan');
                break;

            default:
                $status = '';
                break;
        }
        $row[] = $status;
        $row[] = $record->affectedusers;
        $row[] = display_yes_no($record->manual);
        $row[] = display_yes_no($record->auto);
        $row[] = display_yes_no($record->completed);

        $table->add_data($row);
    }
}

$table->finish_html();

echo $OUTPUT->footer();

function display_yes_no($value) {
    return (isset($value) && $value == 1) ? get_string('yes') : get_string('no');
}
