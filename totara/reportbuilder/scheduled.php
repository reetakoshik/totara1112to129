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
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Page for setting up scheduled reports
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir  . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/scheduled_forms.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->dirroot . '/totara/reportbuilder/email_setting_schedule.php');

require_login();
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url('/totara/reportbuilder/scheduled.php');
$PAGE->set_totara_menu_selected('\totara_core\totara\menu\myreports');

// Get the report id. This can be in one of two variables because the two forms are constructed differently.
$reportid = optional_param('reportid', 0, PARAM_INT); //report that a schedule is being added for
$formdata = optional_param_array('addanewscheduledreport', null, PARAM_INT);
// Get the id of a scheduled report that's being edited.
$id = optional_param('id', 0, PARAM_INT);

if ($reportid === 0 && isset($formdata['reportid'])) {
    $reportid = clean_param($formdata['reportid'], PARAM_INT);
}

$myreportsurl = $CFG->wwwroot . '/my/reports.php';
$returnurl = new moodle_url('/totara/reportbuilder/scheduled.php', ['id' => $id]);
$output = $PAGE->get_renderer('totara_reportbuilder');

$newreport = empty($id);

if ($newreport) {
    // Try to create report object to catch invalid data.
    $report = reportbuilder::create($reportid);
    $schedule = new stdClass();
    $schedule->id = 0;
    $schedule->reportid = $reportid;
    $schedule->frequency = null;
    $schedule->schedule = null;
    $schedule->format = null;
    $schedule->exporttofilesystem = null;
    $schedule->userid = $USER->id;
    $schedule->sendtoself = 1; // New schedules are sent to the creating user by default.
    $schedule->usermodified = $schedule->userid;
    $schedule->lastmodified = \time();

    // Does this schedule belong to the current user. For new schedules its always yes.
    $myscheduledreport = true;
} else {
    if (!$schedule = $DB->get_record('report_builder_schedule', array('id' => $id))) {
        print_error('error:invalidreportscheduleid', 'totara_reportbuilder');
    }
    // This will be set accurately when processing the current system users later.
    $schedule->sendtoself = 0;

    $report = reportbuilder::create($schedule->reportid);

    // Does this schedule belong to the current user.
    $myscheduledreport = ($USER->id == $schedule->userid);
}

if (!reportbuilder::is_capable($schedule->reportid)) {
    print_error('nopermission', 'totara_reportbuilder');
}

require_capability('totara/reportbuilder:createscheduledreports', context_system::instance());
if ($schedule->userid != $USER->id) {
    // Since TL-9004, it is possible for an admin to adjust scheduled report settings even if
    // he is not the scheduled report owner. Hence the check for the required capability here.
    require_capability('totara/reportbuilder:managescheduledreports', context_system::instance());
}

$allowedscheduledrecipients = get_config('totara_reportbuilder', 'allowedscheduledrecipients');
$allowedscheduledrecipients = explode(',', $allowedscheduledrecipients);

$context = context_system::instance();
$allow_audiences = in_array('audiences', $allowedscheduledrecipients) && has_capability('moodle/cohort:view', $context);
$allow_systemusers = in_array('systemusers', $allowedscheduledrecipients) && has_capability('moodle/user:viewdetails', $context);
$allow_emailexternalusers = in_array('emailexternalusers', $allowedscheduledrecipients);
$allow_sendtoself = in_array('sendtoself', $allowedscheduledrecipients) && $myscheduledreport;
// Clean up.
unset($allowedscheduledrecipients);

$savedsearches = $report->get_saved_searches($schedule->reportid, $USER->id);
if (!isset($report->src->redirecturl)) {
    $savedsearches[0] = get_string('alldata', 'totara_reportbuilder');
}

// Get list of emails settings for this schedule report.
if ($newreport) {
    $current_audiences = $schedule->audiences = array();
    $current_systemusers = $schedule->systemusers = array();
    $current_externalusers = $schedule->externalusers = array();
} else {
    $current_audiences = $schedule->audiences = email_setting_schedule::get_audiences_to_email($id);
    $current_systemusers = $schedule->systemusers = email_setting_schedule::get_system_users_to_email($id);
    $current_externalusers = $schedule->externalusers = email_setting_schedule::get_external_users_to_email($id);
}
$schedule->otherrecipients = [];

// An array of already selected system users.
$existingusers = array();
$otherrecipients = [];
foreach ($schedule->systemusers as $key => $user) {
    if ($allow_sendtoself && $user->id == $USER->id) {
        // The current user owns this schedule, and the the system user is the current user.
        // As they own it we want to use the sendtoself checkbox and remove them from the external users.
        $schedule->sendtoself = 1;
        unset($schedule->systemusers[$key]);
        continue;
    }
    if ($allow_systemusers) {
        $existingusers[$user->id] = $user;
    } else {
        $key = 'otherrecipients_' . sha1('user:'.$user->id);
        $schedule->otherrecipients[$key] = 1;
        $otherrecipients[] = [
            'key' => $key,
            'type' => 'systemusers',
            'a' => $user->fullname,
            'value' => $user->id
        ];
    }
}

$existingaudiences = array();
foreach ($schedule->audiences as $audience) {
    if ($allow_audiences) {
        $existingaudiences[$audience->id] = $audience;
    } else {
        $key = 'otherrecipients_' . sha1('audience:'.$audience->id);
        $schedule->otherrecipients[$key] = 1;
        $otherrecipients[] = [
            'key' => $key,
            'type' => 'audiences',
            'a' => $audience->fullname,
            'value' => $audience->id
        ];
    }
}

$existingexternal = array();
foreach ($schedule->externalusers as $email) {
    if ($allow_emailexternalusers) {
        $existingexternal[] = $email;
    } else {
        $key = 'otherrecipients_' . sha1('email:'.$email);
        $schedule->otherrecipients[$key] = 1;
        $otherrecipients[] = [
            'key' => $key,
            'type' => 'emailexternalusers',
            'a' => $email,
            'value' => $email
        ];
    }
}
$schedule->externalusers = $existingexternal;


// Get existing users and audiences IDs.
$existingsyusers = !empty($existingusers) ? implode(',', array_keys($existingusers)) : '';
$existingaud = !empty($existingaudiences) ? implode(',', array_keys($existingaudiences)) : '';

// Load JS for lightbox.
local_js(array(
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));

$args = array('args'=>'{"reportid":' . $reportid . ','
    . '"id":' . $id . ','
    . '"existingsyusers":"' . $existingsyusers .'",'
    . '"existingaud":"' . $existingaud .'",'
    . '"excludeself":"'.(int)$allow_sendtoself.'"}'
);

$jsmodule = array('name' => 'totara_email_scheduled_report',
    'fullpath' => '/totara/reportbuilder/js/email_scheduled_report.js',
    'requires' => array('json')
);

$PAGE->requires->strings_for_js(array('addsystemusers', 'addcohorts', 'emailexternaluserisonthelist'), 'totara_reportbuilder');
$PAGE->requires->strings_for_js(array('err_email'), 'form');
$PAGE->requires->strings_for_js(array('error:badresponsefromajax'), 'totara_cohort');
$PAGE->requires->js_init_call('M.totara_email_scheduled_report.init', $args, false, $jsmodule);

// Form definition.
$mform = new scheduled_reports_new_form(
    null,
    array(
        'id' => $id,
        'report' => $report,
        'frequency' => $schedule->frequency,
        'schedule' => $schedule->schedule,
        'format' => $schedule->format,
        'savedsearches' => $savedsearches,
        'exporttofilesystem' => $schedule->exporttofilesystem,
        'ownerid' => $schedule->userid,
        'otherrecipients' => $otherrecipients,
        'allow_audiences' => $allow_audiences,
        'allow_emailexternalusers' => $allow_emailexternalusers,
        'allow_systemusers' => $allow_systemusers,
        'allow_sendtoself' => $allow_sendtoself,
    )
);

$mform->set_data($schedule);

if ($mform->is_cancelled()) {
    redirect($myreportsurl);
}
if ($fromform = $mform->get_data()) {

    if (empty($fromform->submitbutton)) {
        totara_set_notification(get_string('error:unknownbuttonclicked', 'totara_reportbuilder'), $returnurl);
    }

    if (!isset($fromform->reportid) || !isset($fromform->format) || !isset($fromform->frequency)) {
        $noticekey = ($newreport) ? 'error:addscheduledreport' : 'error:updatescheduledreport';
        totara_set_notification(get_string($noticekey, 'totara_reportbuilder'), $returnurl);
    }

    $subject = new stdClass();
    $subject->schedule = $fromform->schedule;
    $subject->frequency = $fromform->frequency;
    $scheduler = new scheduler($subject);
    $nextevent = $scheduler->next(time(), false, core_date::get_user_timezone());

    $transaction = $DB->start_delegated_transaction();
    $todb = new stdClass();
    if (!$newreport) {
        // It's an existing scheduled report, save the id and don't change the owner.
        $todb->id = $id;
    } else {
        // Its a new scheduled report, set the owner to the current user.
        $todb->userid = $USER->id;
    }
    $todb->reportid = $fromform->reportid;
    $todb->savedsearchid = $fromform->savedsearchid;
    $todb->format = $fromform->format;
    $todb->exporttofilesystem = $fromform->emailsaveorboth;
    $todb->frequency = $fromform->frequency;
    $todb->schedule = $fromform->schedule;
    $todb->nextreport = $nextevent->get_scheduled_time();

    // Record the person who *modified* the schedule report settings; the
    // *creator* of the scheduled report is only captured when the scheduled
    // report is first created.
    $todb->usermodified = $USER->id;
    $todb->lastmodified = \time();

    if ($newreport) {
        $newid = $DB->insert_record('report_builder_schedule', $todb);

        // Get audiences, system users and external users and update email tables.
        $audiences = ($allow_audiences && !empty($fromform->audiences)) ? explode(',', $fromform->audiences) : array();
        $systemusers = ($allow_systemusers && !empty($fromform->systemusers)) ? explode(',', $fromform->systemusers) : array();
        $externalusers = ($allow_emailexternalusers && !empty($fromform->externalemails)) ? explode(',', $fromform->externalemails) : array();

    } else {
        $DB->update_record('report_builder_schedule', $todb);
        $newid = $todb->id;
        $audiences = array();
        $systemusers = array();
        $externalusers = array();

        if ($allow_audiences && !empty($fromform->audiences)) {
            $audiences = explode(',', $fromform->audiences);
        } else if (!$allow_audiences) {
            $audiences = array_keys($current_audiences);
        }
        if ($allow_systemusers && !empty($fromform->systemusers)) {
            $systemusers = explode(',', $fromform->systemusers);
        } else if (!$allow_systemusers) {
            $systemusers = array_keys($current_systemusers);
        }
        if ($allow_emailexternalusers && !empty($fromform->externalemails)) {
            $externalusers = explode(',', $fromform->externalemails);
        } else if (!$allow_emailexternalusers) {
            $externalusers = $current_externalusers;
        }
    }

    if ($allow_sendtoself && !empty($fromform->sendtoself) && !in_array($USER->id, $systemusers)) {
        // If the schedule belongs to the current user and seld to self has been selected then
        // we want to remove that and add the current user to the system users before we save.
        // NOTE: send to self is only shown to the user who owns the schedule.
        unset($fromform->sendtoself);
        array_push($systemusers, $USER->id);
    }

    if (!empty($fromform->otherrecipients)) {
        // Advanced checkboxes are shown for other recipients.
        // We iterate the known other users, and if the value for them is given, and is 0 then
        // we will remove them as recipients.
        foreach ($otherrecipients as $recipient) {
            if (isset($fromform->otherrecipients[$recipient['key']]) && empty($fromform->otherrecipients[$recipient['key']])) {
                switch ($recipient['type']) {
                    case 'systemusers':
                        $recipientkey = array_search($recipient['value'], $systemusers);
                        if ($recipient !== false) {
                            unset($systemusers[$recipientkey]);
                        }
                        break;
                    case 'audiences':
                        $recipientkey = array_search($recipient['value'], $audiences);
                        if ($recipient !== false) {
                            unset($audiences[$recipientkey]);
                        }
                        break;
                    case 'emailexternalusers':
                        $recipientkey = array_search($recipient['value'], $externalusers);
                        if ($recipient !== false) {
                            unset($externalusers[$recipientkey]);
                        }
                        break;
                    default:
                        throw new coding_exception('Unexpected recipient type', $recipient);
                        break;
                }
            }
        }
    }

    $scheduleemail = new email_setting_schedule($newid);
    $scheduleemail->set_email_settings($audiences, $systemusers, $externalusers);

    $transaction->allow_commit();

    if ($newreport) {
        $todb->id = $newid;
        \totara_reportbuilder\event\scheduled_report_created::create_from_schedule($todb)->trigger();
    } else {
        \totara_reportbuilder\event\scheduled_report_updated::create_from_schedule($todb)->trigger();
    }

    $noticekey = ($newreport) ? 'addedscheduledreport' : 'updatescheduledreport';
    totara_set_notification(get_string($noticekey, 'totara_reportbuilder'), $myreportsurl, array('class' => 'notifysuccess'));
}

if ($newreport) {
    $pagename = 'addscheduledreport';
} else {
    $pagename = 'editscheduledreport';
}

$PAGE->set_title(get_string($pagename, 'totara_reportbuilder'));
$PAGE->navbar->add(get_string('reports', 'totara_core'), new moodle_url('/my/reports.php'));
$PAGE->navbar->add(get_string($pagename, 'totara_reportbuilder'));
echo $output->header();

echo $output->heading(get_string($pagename, 'totara_reportbuilder'));

$mform->display();

echo $output->footer();
