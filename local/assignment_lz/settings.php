<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $roles = get_all_roles();

    foreach ($roles as $key => $role) {
        $roles[$role->shortname] = $role->shortname;
        unset($roles[$key]);
    }

    $settings = new admin_settingpage('local_assignment_lz_settings', get_string('pluginname', 'local_assignment_lz'));
    $settings->add(new admin_setting_configselect('local_assignment_lz/role',
            get_string('settings:roles', 'local_assignment_lz'),
            get_string('settings:rolesdescription', 'local_assignment_lz'),
            'teacher',
            $roles));

    $ADMIN->add('localplugins', $settings);
}
