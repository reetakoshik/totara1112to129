<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

$action = optional_param('action', 'view', PARAM_ALPHA);
$confirm = optional_param('confirm', '', PARAM_RAW);

admin_externalpage_setup('opensesameregister');

$config = get_config('repository_opensesame');

if ($action !== 'new' and empty($config->tenantkey)) {
    redirect(new moodle_url($PAGE->url, array('action' => 'new')));
}

if ($action === 'new') {
    $mform = new repository_opensesame_form_register_new();

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/'));
    } else if ($data = $mform->get_data()) {
        // No interruptions here!
        ignore_user_abort(true);
        core_php_time_limit::raise(120);

        if (!empty($config->tenantkey)) {
            // This must be a double click or cancelled request.
            redirect(new moodle_url($PAGE->url, array('action' => 'view')));
        }

        if (empty($config->repositorykey)) {
            $repositorykey = sha1(random_string(100));
        } else {
            $repositorykey = $config->repositorykey;
        }
        $result = \repository_opensesame\local\totaralms_com::provision_tenant($data->tenantname, $data->tenanttype, $repositorykey, $data->tenantdemosecret);

        if ($result['status'] === 'success') {
            set_config('repositorykey', $repositorykey, 'repository_opensesame');
            set_config('tenantid', $result['data']['TenantId'], 'repository_opensesame');
            set_config('tenantkey', $result['data']['TenantKey'], 'repository_opensesame');
            set_config('tenantsecret', $result['data']['TenantSecret'], 'repository_opensesame');
            set_config('tenantapiurl', $result['data']['TenantApiUrl'], 'repository_opensesame');
            set_config('tenantname', $result['data']['TenantName'], 'repository_opensesame');
            set_config('tenanttype', $result['data']['TenantType'], 'repository_opensesame');

            \repository_opensesame\local\util::enable_repository();

            totara_rb_purge_ignored_reports();

            \repository_opensesame\event\tenant_registered::create_from_tenantid($result['data']['TenantId'])->trigger();

            if (has_capability('repository/opensesame:managepackages', context_system::instance())) {
                redirect(new moodle_url('/repository/opensesame/browse.php'));
            } else {
                redirect(new moodle_url($PAGE->url, array('action' => 'view')));
            }

        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('register', 'repository_opensesame'));
            if ($result['status'] === 'error') {
                $message = $result['message'];
            } else {
                $message = get_string('error');
            }
            notice($message, new moodle_url($PAGE->url, array('action' => 'new')));
            echo $OUTPUT->footer();
            die;
        }
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('register', 'repository_opensesame'));
    echo $OUTPUT->box(get_string('aboutopensesamedesc', 'repository_opensesame'));
    $mform->display();
    echo $OUTPUT->footer();
    die;

} else if ($action === 'delete' and !defined('REPOSITORY_OPENSESAME_DEMO_SECRET')) {
    $mform = new repository_opensesame_form_confirm_delete(null, $config);
    if ($mform->is_cancelled()) {
        redirect(new moodle_url($PAGE->url, array('action' => 'view')));

    } else if ($data = $mform->get_data()) {
        // No interruptions here!
        ignore_user_abort(true);
        core_php_time_limit::raise(120);

        $result = \repository_opensesame\local\totaralms_com::remove_tenant($data->tenantid, $config->repositorykey);

        if ($result['status'] === 'success') {
            \repository_opensesame\local\util::disable_repository();

            unset_config('repositorykey', 'repository_opensesame');
            unset_config('tenantid', 'repository_opensesame');
            unset_config('tenantkey', 'repository_opensesame');
            unset_config('tenantsecret', 'repository_opensesame');
            unset_config('tenantapiurl', 'repository_opensesame');
            unset_config('tenantname', 'repository_opensesame');
            unset_config('tenanttype', 'repository_opensesame');

            $fs = get_file_storage();
            $fs->delete_area_files(context_system::instance()->id, 'repository_opensesame');

            $DB->delete_records('repository_opensesame_bps', array());
            $DB->delete_records('repository_opensesame_pkgs', array());
            $DB->delete_records('repository_opensesame_bdls', array());

            totara_rb_purge_ignored_reports();

            \repository_opensesame\event\tenant_unregistered::create_from_tenantid($data->tenantid)->trigger();

            redirect(new moodle_url($PAGE->url, array('action' => 'view')));

        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('unregister', 'repository_opensesame'));
            if ($result['status'] === 'error') {
                $message = $result['message'];
            } else {
                $message = get_string('error');
            }
            notice($message, new moodle_url($PAGE->url, array('action' => 'view')));
            echo $OUTPUT->footer();
            die;
        }
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('unregister', 'repository_opensesame'));
    $mform->display();
    echo $OUTPUT->footer();
    die;

} else if ($action === 'fetchall') {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('fetchall', 'repository_opensesame'));
    core_php_time_limit::raise(60 * 20);
    raise_memory_limit(MEMORY_HUGE);
    $count = \repository_opensesame\local\util::fetch_packages('full');

    if ($count === false) {
        echo $OUTPUT->notification(get_string('coursefetcherror', 'repository_opensesame'), 'notifyproblem');
    } else if ($count > 0) {
        echo $OUTPUT->notification(get_string('coursefetchsuccess', 'repository_opensesame', $count), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('coursefetchsuccessnocourse', 'repository_opensesame'), 'notifysuccess');
    }

    echo $OUTPUT->continue_button('/repository/opensesame/index.php');

    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('registration', 'repository_opensesame'));

$tenanttypes = \repository_opensesame\local\opensesame_com::get_tenant_types();

echo $OUTPUT->box_start();
echo '<p>'.get_string('registrationdetails', 'repository_opensesame').'</p>';
echo '<dl>';
echo '<dt>' . get_string('tenantname', 'repository_opensesame') . '</dt>';
echo '<dd>' . s($config->tenantname) . '</dd>';
if ($config->tenanttype !== 'Prod') {
    echo '<dt>' . get_string('tenanttype', 'repository_opensesame') . '</dt>';
    echo '<dd>' . $tenanttypes[$config->tenanttype] . '</dd>';
}
echo '<dt>' . get_string('tenantid', 'repository_opensesame') . '</dt>';
echo '<dd>' . s($config->tenantid) . '</dd>';
echo '</dl>';
echo $OUTPUT->box_end();

echo '<div class="buttons">';
if (!defined('REPOSITORY_OPENSESAME_DEMO_SECRET')) {
    $deleteurl = new moodle_url($PAGE->url, array('action' => 'delete'));
    echo $OUTPUT->single_button($deleteurl, get_string('unregister', 'repository_opensesame'));
}
$fetchurl = new moodle_url($PAGE->url, array('action' => 'fetchall'));
echo $OUTPUT->single_button($fetchurl, get_string('fetchall', 'repository_opensesame'));
echo '</div>';

echo $OUTPUT->footer();
