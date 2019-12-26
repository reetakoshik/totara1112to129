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
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage plan
 */

/**
 * Plan view page
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

$id = required_param('id', PARAM_INT); // plan id
$action = optional_param('action', 'view', PARAM_TEXT);

if ($action == 'edit') {
    require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
}

$componentname = 'plan';

$currenturl = qualified_me();
$viewurl = strip_querystring(qualified_me())."?id={$id}&action=view";
$editurl = strip_querystring(qualified_me())."?id={$id}&action=edit";

require_login();
$plan = new development_plan($id);

// Permissions check
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/totara/plan/view.php', array('id' => $id)));
$PAGE->set_pagelayout('report');

$ownplan = $USER->id == $plan->userid;
$menuitem = ($ownplan) ? '\totara_plan\totara\menu\learningplans' : '\totara_core\totara\menu\myteam';
$PAGE->set_totara_menu_selected($menuitem);

if (!$plan->can_view()) {
    print_error('error:nopermissions', 'totara_plan');
}

require_once('edit_form.php');
$plan->descriptionformat = FORMAT_HTML;
/** @var development_plan $plan This is some horrible code! */
$plan = file_prepare_standard_editor($plan, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                    'totara_plan', 'dp_plan', $plan->id);
$form = new plan_edit_form($currenturl, array('plan' => $plan, 'action' => $action));

if ($form->is_cancelled()) {
    totara_set_notification(get_string('planupdatecancelled', 'totara_plan'), $viewurl, array('class' => 'notifysuccess'));
}

// Handle form submits
if ($data = $form->get_data()) {
    if (!$plan->can_manage()) {
        print_error('error:nopermissions', 'totara_plan');
    }
    if (isset($data->edit)) {
        if (!$plan->can_update()) {
            print_error('error:nopermissions', 'totara_plan');
        }
        redirect($editurl);
    } else if (isset($data->delete)) {
        if (!$plan->can_delete_plan()) {
            print_error('error:nopermissions', 'totara_plan');
        }
        redirect(strip_querystring(qualified_me())."?id={$id}&action=delete");
    } else if (isset($data->deleteyes)) {
        if (!$plan->can_delete_plan()) {
            print_error('error:nopermissions', 'totara_plan');
        }
        if ($plan->delete()) {
            totara_set_notification(get_string('plandeletesuccess', 'totara_plan', $plan->name), "{$CFG->wwwroot}/totara/plan/index.php?userid={$plan->userid}", array('class' => 'notifysuccess'));
        }
    } else if (isset($data->deleteno)) {
        redirect($viewurl);
    } else if (isset($data->complete)) {
        if (!$plan->can_mark_plan_complete()) {
            print_error('error:nopermissions', 'totara_plan');
        }
        redirect(strip_querystring(qualified_me())."?id={$id}&action=complete");
    } else if (isset($data->completeyes)) {
        if (!$plan->can_mark_plan_complete()) {
            print_error('error:nopermissions', 'totara_plan');
        }
        if ($plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_MANUAL_COMPLETE)) {
            \totara_plan\event\plan_completed::create_from_plan($plan)->trigger();
            $plan->send_completion_alert();
            totara_set_notification(get_string('plancompletesuccess', 'totara_plan', $plan->name), $viewurl, array('class' => 'notifysuccess'));
        } else {
            totara_set_notification(get_string('plancompletefail', 'totara_plan', $plan->name), $viewurl);
        }
    } else if (isset($data->completeno)) {
        redirect($viewurl);
    } else if (isset($data->submitbutton)) {
        if (!$plan->can_update()) {
            print_error('error:nopermissions', 'totara_plan');
        }
        // Save plan data
        $data = file_postupdate_standard_editor($data, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_plan', 'dp_plan', $data->id);
        $DB->update_record('dp_plan', $data);
        $plan = new development_plan($data->id);
        \totara_plan\event\plan_updated::create_from_plan($plan)->trigger();
        totara_set_notification(get_string('planupdatesuccess', 'totara_plan'), $viewurl, array('class' => 'notifysuccess'));
    }

    // Reload plan to reflect any changes
    $plan = new development_plan($id);
}


/**
 * Display header
 */
dp_get_plan_base_navlinks($plan->userid);
$PAGE->navbar->add($plan->name);
$plan->print_header('plan');

\totara_plan\event\plan_viewed::create_from_plan($plan)->trigger();

// Plan details
if ($plan->timecompleted) {
    $plan->enddate = $plan->timecompleted;
}
$form->set_data($plan);
$form->display();

if ($action == 'view') {
    // Comments
    require_once($CFG->dirroot.'/comment/lib.php');
    comment::init();
    $options = new stdClass();
    $options->area    = 'plan_overview';
    $options->context = $context;
    $options->itemid  = $plan->id;
    $options->showcount = true;
    $options->component = 'totara_plan';
    $options->autostart = true;
    $options->notoggle = true;
    $comment = new comment($options);
    echo $comment->output(true);
}

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
