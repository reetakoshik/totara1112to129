<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Andrew Bell <andrewb@learningpool.com>
 * @author Ryan Lynch <ryanlynch@learningpool.com>
 * @author Barry McKay <barry@learningpool.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */
/** @var admin_settingpage $settings passed from admin/settings/plugins.php */

$ADMIN->add('authsettings', new admin_category(
    'authapprovedfolder',
    new lang_string('pluginname', 'auth_approved'),
    $settings->is_hidden()));

$settingspage = new admin_settingpage(
    'authsettingapproved',
    new lang_string('settings', 'core_plugin'),
    'moodle/site:config',
    $settings->is_hidden());

if ($ADMIN->fulltree) {
    $settingspage->add(new admin_setting_confightmleditor(
        'auth_approved/instructions',
        new lang_string('instructions', 'auth_approved'),
        new lang_string('instructions_desc', 'auth_approved'),
        ''));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/requireapproval',
        new lang_string('requireapproval', 'auth_approved'),
        new lang_string('requireapproval_desc', 'auth_approved'),
        1));

    $settingspage->add(new auth_approved_setting_domainwhitelist(
        'auth_approved/domainwhitelist'));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/recaptcha',
        new lang_string('enablerecaptcha', 'auth_approved'),
        new lang_string('enablerecaptcha_desc', 'auth_approved'),
        0));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/allowexternaldefaults',
        new lang_string('allowexternaldefaults', 'auth_approved'),
        new lang_string('allowexternaldefaults_desc', 'auth_approved'),
        0));

    // Organisations related settings.

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/alloworganisation',
        new lang_string('alloworganisation', 'auth_approved'),
        new lang_string('alloworganisation_desc', 'auth_approved'),
        0));

    $settingspage->add(new auth_approved_setting_organisationframeworks(
        'auth_approved/organisationframeworks'));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/alloworganisationfreetext',
        new lang_string('alloworganisationfreetext', 'auth_approved'),
        new lang_string('alloworganisationfreetext_desc', 'auth_approved'),
        0));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/requireorganisation',
        new lang_string('requireorganisation', 'auth_approved'),
        new lang_string('requireorganisation_desc', 'auth_approved'),
        0));

    // Positions related settings.

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/allowposition',
        new lang_string('allowposition', 'auth_approved'),
        new lang_string('allowposition_desc', 'auth_approved'),
        0));

    $settingspage->add(new auth_approved_setting_positionframeworks(
        'auth_approved/positionframeworks'));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/allowpositionfreetext',
        new lang_string('allowpositionfreetext', 'auth_approved'),
        new lang_string('allowpositionfreetext_desc', 'auth_approved'),
        0));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/requireposition',
        new lang_string('requireposition', 'auth_approved'),
        new lang_string('requireposition_desc', 'auth_approved'),
        0));

    // Managers related settings.

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/allowmanager',
        new lang_string('allowmanager', 'auth_approved'),
        new lang_string('allowmanager_desc', 'auth_approved'),
        0));

    $settingspage->add(new auth_approved_setting_managerorganisationframeworks(
        'auth_approved/managerorganisationframeworks'));

    $settingspage->add(new auth_approved_setting_managerpositionframeworks(
        'auth_approved/managerpositionframeworks'));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/allowmanagerfreetext',
        new lang_string('allowmanagerfreetext', 'auth_approved'),
        new lang_string('allowmanagerfreetext_desc', 'auth_approved'),
        0));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/requiremanager',
        new lang_string('requiremanager', 'auth_approved'),
        new lang_string('requiremanager_desc', 'auth_approved'),
        0));

    // Password expiration - auth_manual strings are reused intentionally.
    $settingspage->add(new admin_setting_heading(
        'auth_approved/expiration_heading',
        new lang_string('passwdexpire_settings', 'auth_manual'), ''));

    $settingspage->add(new admin_setting_configcheckbox(
        'auth_approved/expiration',
        new lang_string('expiration', 'auth_manual'),
        new lang_string('expiration_desc', 'auth_manual'), 0));

    $options = array(
        '0' => new lang_string('unlimited'),
        '30' => new lang_string('numdays', '', 30),
        '60' => new lang_string('numdays', '', 60),
        '90' => new lang_string('numdays', '', 90),
        '120' => new lang_string('numdays', '', 120),
        '150' => new lang_string('numdays', '', 150),
        '180' => new lang_string('numdays', '', 180),
        '365' => new lang_string('numdays', '', 365),
    );
    $settingspage->add(new admin_setting_configselect(
        'auth_approved/expirationtime',
        new lang_string('passwdexpiretime', 'auth_manual'),
        new lang_string('passwdexpiretime_desc', 'auth_manual'), 30, $options));

    $options = array(
        '0' => new lang_string('never'),
        '1' => new lang_string('numdays', '', 1),
        '2' => new lang_string('numdays', '', 2),
        '3' => new lang_string('numdays', '', 3),
        '4' => new lang_string('numdays', '', 4),
        '5' => new lang_string('numdays', '', 5),
        '6' => new lang_string('numdays', '', 6),
        '7' => new lang_string('numdays', '', 7),
        '10' => new lang_string('numdays', '', 10),
        '14' => new lang_string('numdays', '', 14),
    );
    $settingspage->add(new admin_setting_configselect(
        'auth_approved/expiration_warning',
        new lang_string('expiration_warning', 'auth_manual'),
        new lang_string('expiration_warning_desc', 'auth_manual'), 0, $options));

    // Display locking of profile fields.
    $authplugin = get_auth_plugin('approved');
    display_auth_lock_options($settingspage, $authplugin->authtype,
        $authplugin->userfields, get_string('auth_fieldlocks_help', 'auth'), false, false);
}

$ADMIN->add('authapprovedfolder', $settingspage);

$ADMIN->add('authapprovedfolder', new admin_externalpage(
    'authapprovedpending',
    new lang_string('reportpending', 'auth_approved'),
    new moodle_url('/auth/approved/index.php'),
    'auth/approved:approve', $settings->is_hidden()));

$settings = null;
