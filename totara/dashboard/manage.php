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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_dashboard
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/dashboard/lib.php');
require_once($CFG->libdir.'/adminlib.php');

$action = optional_param('action', null, PARAM_ALPHANUMEXT);

admin_externalpage_setup('totaradashboard', '', array('action' => $action));

// Check Totara Dashboard is enable.
totara_dashboard::check_feature_enabled();

/** @var totara_dashboard_renderer $output */
$output = $PAGE->get_renderer('totara_dashboard');

$dashboards = totara_dashboard::get_manage_list();

$dashboard = null;
if ($action != '') {
    $id = required_param('id', PARAM_INT);
    $dashboard = new totara_dashboard($id);
    $returnurl = new moodle_url('/totara/dashboard/manage.php');
}

switch ($action) {
    case 'clone':
        // This operation clones the given dashboard as well as the blocks it uses and any assigned audiences.
        // It does not clone any user customisations of the dashboard.
        $confirm = optional_param('confirm', null, PARAM_INT);
        if ($confirm) {
            require_sesskey();
            $newid = $dashboard->clone_dashboard();
            $clone = new totara_dashboard($newid);
            $args = array(
                'original' => $dashboard->name,
                'clone' => $clone->name
            );
            totara_set_notification(get_string('dashboardclonesuccess', 'totara_dashboard', $args), $returnurl,
                array('class' => 'notifysuccess'));
        }
        break;
    case 'delete':
        $confirm = optional_param('confirm', null, PARAM_INT);
        if ($confirm) {
            require_sesskey();
            $dashboard->delete($id);
            totara_set_notification(get_string('dashboarddeletesuccess', 'totara_dashboard'), $returnurl,
                    array('class' => 'notifysuccess'));
        }
        break;
    case 'up':
        require_sesskey();
        $dashboard->move_up();
        redirect($returnurl);
        break;
    case 'down':
        require_sesskey();
        $dashboard->move_down();
        redirect($returnurl);
        break;
    case 'reset':
        $confirm = optional_param('confirm', null, PARAM_INT);
        if ($confirm) {
            require_sesskey();
            $dashboard->reset_all();
            totara_set_notification(get_string('dashboardresetsuccess', 'totara_dashboard'), $returnurl,
                    array('class' => 'notifysuccess'));
        }
        break;
}

$requiresconfirmation = array('delete', 'reset', 'clone');
if (in_array($action, $requiresconfirmation)) {
    switch ($action) {
        case 'delete':
            $confirmtext = get_string('deletedashboardconfirm', 'totara_dashboard', $dashboard->name);
            break;
        case 'reset':
            $confirmtext = get_string('resetdashboardconfirm', 'totara_dashboard', $dashboard->name);
            break;
        case 'clone':
            $confirmtext = get_string('clonedashboardconfirm', 'totara_dashboard', $dashboard->name);
            break;
        default:
            throw new coding_exception('Invalid action passed to confirmation.');
            break;
    }

    $url = new moodle_url('/totara/dashboard/manage.php', array('action'=> $action, 'id' => $id, 'confirm' => 1));
    $continue = new single_button($url, get_string('continue'), 'post');
    $cancel = new single_button($returnurl, get_string('cancel'), 'get');

    echo $output->header();
    echo $output->confirm(format_text($confirmtext), $continue, $cancel);
    echo $output->footer();
    exit;
}

echo $output->header();
echo $output->heading(get_string('managedashboards', 'totara_dashboard'));
echo $output->create_dashboard_button();
echo $output->dashboard_manage_table($dashboards);
echo $output->footer();
