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
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot . '/totara/dashboard/lib.php');
require_once($CFG->dirroot . '/lib/navigationlib.php');

redirect_if_major_upgrade_required();

$edit   = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off.
$reset  = optional_param('reset', null, PARAM_BOOL);
$id =  optional_param('id', 0, PARAM_INT);

require_login(null, false);
if (isguestuser()) {
    // No dashboards for guests!
    redirect(new moodle_url('/'));
}

// Check Totara Dashboard is enable.
totara_dashboard::check_feature_enabled();

$userid = $USER->id;

$availabledash = array_keys(totara_dashboard::get_user_dashboards($userid));

// Validate dashboard id.
if ($id) {
    if (!in_array($id, $availabledash)) {
        // If not available, redirect to default dashboard.
        redirect(new moodle_url('/totara/dashboard/index.php'));
    }
} else if (get_user_preferences('user_home_page_preference') == HOMEPAGE_TOTARA_DASHBOARD &&
        in_array(get_user_preferences('user_home_totara_dashboard_id'), $availabledash)) {
    // Set home page dashboard id.
    $id = get_user_preferences('user_home_totara_dashboard_id');
} else if (isset($availabledash[0])) {
    // Set first in sort order.
    $id = $availabledash[0];
}

$params = array('id' => $id);
$PAGE->set_url('/totara/dashboard/index.php', $params);

if (!$id) {
    $header = $SITE->shortname. ': ' . get_string('dashboard', 'totara_dashboard');
    $PAGE->set_context(context_user::instance($USER->id));
    totara_set_notification(get_string('noavailabledashboards', 'totara_dashboard'));
} else {
    $dashboard = new totara_dashboard($id);
    $userpageid = $dashboard->get_user_pageid($userid);

    $header = $SITE->shortname. ': ' . $dashboard->name;

    // Set personal or system dashboard.
    if (!$dashboard->is_locked() && $userpageid) {
        $context = context_user::instance($USER->id);
    } else {
        $context = context_system::instance();
        $userpageid = 'default';
    }

    $PAGE->set_context($context);
    $PAGE->set_subpage($userpageid);
    $PAGE->set_blocks_editing_capability('totara/dashboard:manageblocks');
    $PAGE->set_pagelayout('dashboard');
    $PAGE->set_pagetype('totara-dashboard-' . $id);
    $PAGE->set_subpage($userpageid);
    // Method add_region requires pagetype set first.
    $PAGE->blocks->add_region('content');
    $PAGE->navbar->add($dashboard->name);
    // Do not show "User profile settings" in navbar.
    $PAGE->navbar->ignore_active(true);

    // Set dashboard as homepage for user when user home page preference is enabled.
    if (!empty($CFG->allowdefaultpageselection) and (get_home_page() == HOMEPAGE_SITE)) {
        if (optional_param('setdefaulthome', 0, PARAM_BOOL)) {
            require_sesskey();
            set_user_preference('user_home_page_preference', HOMEPAGE_TOTARA_DASHBOARD);
            set_user_preference('user_home_totara_dashboard_id', $id);
            $url = new moodle_url('/totara/dashboard/index.php', array('id' => $id));
            totara_set_notification(get_string('userhomepagechanged', 'totara_dashboard'), $url, array('class' => 'notifysuccess'));
        }
        $newhomeurl = new moodle_url('/totara/dashboard/index.php', array('setdefaulthome' => 1, 'id' => $id, 'sesskey' => sesskey()));
        $PAGE->settingsnav->add(get_string('makedashboardmyhomepage', 'totara_dashboard'), $newhomeurl, navigation_node::TYPE_SETTING);
    }

    $resetbutton = '';
    $editbutton = '';
    // Toggle the editing state and switches.
    if ($PAGE->user_allowed_editing() && !$dashboard->is_locked()) {
        if ($reset !== null) {
            if (!is_null($userid)) {
                require_sesskey();
                $dashboard->user_reset($userid);

                redirect(new moodle_url('/totara/dashboard/index.php', array('id' => $dashboard->get_id())));
            }
        } else if ($edit !== null) {
            $USER->editing = $edit;
            if ($userpageid == 'default' && $edit) {
                // If we are viewing a system page as ordinary user, and the user turns
                // editing on, copy the system pages as new user pages, and get the
                // new page record.
                $userpageid = $dashboard->user_copy($userid);
                $context = context_user::instance($userid);
                $PAGE->set_context($context);
                $PAGE->set_subpage($userpageid);
            }
        } else { // Editing state is in session.
            if ($userpageid != 'default') {
                if (!empty($USER->editing)) {
                    $edit = 1;
                } else {
                    $edit = 0;
                }
            } else {
                $USER->editing = $edit = 0; // Disable editing completely, just to be safe.
            }
        }

        // Add button for editing page.
        $params = array('id' => $id, 'edit' => !$edit);
        $resetstring = get_string('resetdashboard', 'totara_dashboard');
        $reseturl = new moodle_url("/totara/dashboard/index.php", array('id' => $id, 'edit' => 1, 'reset' => 1));

        if ($userpageid == 'default') {
            // Viewing a system page - let the user customise it.
            $editstring = get_string('customiseon', 'totara_dashboard');
            $params['edit'] = 1;
        } else if (empty($edit)) {
            $editstring = get_string('customiseon', 'totara_dashboard');
        } else {
            $editstring = get_string('customiseoff', 'totara_dashboard');
            $resetbutton = $OUTPUT->single_button($reseturl, $resetstring);
        }

        $editurl = new moodle_url("/totara/dashboard/index.php", $params);
        $editbutton = $OUTPUT->single_button($editurl, $editstring);
    } else {
        $USER->editing = $edit = 0;
    }

    // Set page buttons.
    $managebutton = '';
    if (has_capability('totara/dashboard:manage', context_system::instance())) {
        $managestring = get_string('managedashboards', 'totara_dashboard');
        $managebutton = $OUTPUT->single_button(new moodle_url("/totara/dashboard/manage.php"), $managestring);
    }
    $PAGE->set_button($resetbutton . $editbutton . $managebutton);

    // HACK WARNING!  This loads up all this page's blocks in the system context.
    if ($userpageid == 'default') {
        $CFG->blockmanagerclass = 'my_syspage_block_manager';
    }
}

$PAGE->set_title($header);
$PAGE->set_heading($header);

echo $OUTPUT->header();

echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();
