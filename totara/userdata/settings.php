<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

defined('MOODLE_INTERNAL') || die();

$temp = new admin_settingpage('userdatasettings', new lang_string('settings', 'totara_userdata'), 'totara/userdata:config');
$temp->add(new totara_userdata_admin_setting_purge_type_select(
    'suspended',
    'totara_userdata/defaultsuspendedpurgetypeid',
    new lang_string('defaultsuspendedpurgetype', 'totara_userdata'),
    new lang_string('defaultsuspendedpurgetype_desc', 'totara_userdata')));
$temp->add(new totara_userdata_admin_setting_purge_type_select(
    'deleted',
    'totara_userdata/defaultdeletedpurgetypeid',
    new lang_string('defaultdeletedpurgetype', 'totara_userdata'),
    new lang_string('defaultdeletedpurgetype_desc', 'totara_userdata')));
$temp->add(new admin_setting_configcheckbox(
    'totara_userdata/selfexportenable',
    new lang_string('selfexportenable', 'totara_userdata'),
    new lang_string('selfexportenable_desc', 'totara_userdata'),
    0));
$ADMIN->add('userdata', $temp);

$ADMIN->add('userdata', new admin_externalpage('userdataexporttypes', get_string('exporttypes', 'totara_userdata'),
    $CFG->wwwroot . '/totara/userdata/export_types.php', 'totara/userdata:viewexports'));

$ADMIN->add('userdata', new admin_externalpage('userdatapurgetypes', get_string('purgetypes', 'totara_userdata'),
    $CFG->wwwroot . '/totara/userdata/purge_types.php', 'totara/userdata:viewpurges'));

$ADMIN->add('userdata', new admin_externalpage('userdataexports', get_string('exports', 'totara_userdata'),
    $CFG->wwwroot . '/totara/userdata/exports.php', 'totara/userdata:viewexports'));

$ADMIN->add('userdata', new admin_externalpage('userdatapurges', get_string('purges', 'totara_userdata'),
    $CFG->wwwroot . '/totara/userdata/purges.php', 'totara/userdata:viewpurges'));

$ADMIN->add('userdata', new admin_externalpage('userdatadeletedusers', get_string('sourcetitle', 'rb_source_userdata_deleted_users'),
    $CFG->wwwroot . '/totara/userdata/deleted_users.php', 'totara/core:seedeletedusers'));

