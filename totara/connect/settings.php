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
 * @package totara_connect
 */

defined('MOODLE_INTERNAL') || die();

$hidden = empty($CFG->enableconnectserver);

$ADMIN->add('users', new admin_category('totaraconnect', new lang_string('server', 'totara_connect'), $hidden));

$settingspage = new admin_settingpage('totaraconnectsettings',
    new lang_string('settingspage', 'totara_connect'),
    'moodle/site:config',
    $hidden);

$settingspage->add(new admin_setting_configcheckbox('totara_connect/syncpasswords',
    new lang_string('syncpasswords', 'totara_connect'),  new lang_string('syncpasswords_desc', 'totara_connect'),
    0));

// NOTE TL-7406: add setting for sync of user preferences and custom profile fields here, off by default for performance reasons.

$ADMIN->add('totaraconnect', $settingspage);

$ADMIN->add('totaraconnect', new admin_externalpage('totaraconnectclients',
    new lang_string('clients', 'totara_connect'),
    new moodle_url('/totara/connect/index.php'),
    'totara/connect:manage',
    $hidden)
);
