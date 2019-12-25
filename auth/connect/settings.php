<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package auth_connect
 */

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->libdir}/authlib.php");

$ADMIN->add('authsettings', new admin_category('authconnectfolder', new lang_string('pluginname', 'auth_connect'),
    $settings->is_hidden()));

$settingspage = new admin_settingpage('authsettingconnect', new lang_string('settings', 'core_plugin'),
    'moodle/site:config', $settings->is_hidden());

$settingspage->add(new auth_connect_setting_autossoserver());

$settingspage->add(new admin_setting_configcheckbox('auth_connect/migrateusers',
    new lang_string('migrateusers', 'auth_connect'),  new lang_string('migrateusers_desc', 'auth_connect'),
    0));

$options = array(
    'username' => get_string('username'),
    'email'    => get_string('email'),
    'idnumber' => get_string('idnumber'),
    'uniqueid' => get_string('migratebyuniqueid', 'auth_connect'),
);
$settingspage->add(new admin_setting_configselect('auth_connect/migratemap',
    new lang_string('migratemap', 'auth_connect'), new lang_string('migratemap_desc', 'auth_connect'),
    'username', $options));

$settingspage->add(new admin_setting_configcheckbox('auth_connect/syncprofilefields',
    new lang_string('syncprofilefields', 'auth_connect'), new lang_string('syncprofilefields_desc', 'auth_connect'), 0));

$options = array(
    AUTH_REMOVEUSER_KEEP       => get_string('auth_remove_keep', 'auth'),
    AUTH_REMOVEUSER_SUSPEND    => get_string('auth_remove_suspend', 'auth'),
    AUTH_REMOVEUSER_FULLDELETE => get_string('auth_remove_delete', 'auth'),
);
$settingspage->add(new admin_setting_configselect('auth_connect/removeuser',
    new lang_string('removeuser', 'auth_connect'), new lang_string('removeuser_desc', 'auth_connect'),
    AUTH_REMOVEUSER_SUSPEND, $options));

$settingspage->add(new admin_setting_configcheckbox('auth_connect/syncjobs',
    new lang_string('syncjobs', 'auth_connect'), new lang_string('syncjobs_desc', 'auth_connect'), 0));

if (!totara_feature_disabled('positions')) {
    $settingspage->add(new admin_setting_configcheckbox('auth_connect/syncpositions',
        new lang_string('syncpositions', 'auth_connect'), new lang_string('syncpositions_desc', 'auth_connect'), 0));
}

$settingspage->add(new admin_setting_configcheckbox('auth_connect/syncorganisations',
    new lang_string('syncorganisations', 'auth_connect'), new lang_string('syncorganisations_desc', 'auth_connect'), 0));

$settingspage->add(new admin_setting_configcheckbox('auth_connect/allowpluginsepservices',
    new lang_string('allowpluginsepservices', 'auth_connect'), new lang_string('allowpluginsepservices_desc', 'auth_connect'), 0));

$ADMIN->add('authconnectfolder', $settingspage);

$ADMIN->add('authconnectfolder', new admin_externalpage('authconnectservers', new lang_string('serverspage', 'auth_connect'),
    new moodle_url('/auth/connect/index.php'),
    'moodle/site:config', $settings->is_hidden())
);

$settings = null;
