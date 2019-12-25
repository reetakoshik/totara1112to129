<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @package tool_sitepolicy
 */
/*
 * Site Policy Settings
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
if (!empty($CFG->enablesitepolicies)) {
    $sitepolicy = new admin_category('tool_sitepolicy', get_string('pluginname', 'tool_sitepolicy'));
    $ADMIN->add('security', $sitepolicy);
    $ADMIN->add(
        'tool_sitepolicy',
        new admin_externalpage(
            'tool_sitepolicy-managerpolicies',
            get_string('managepolicies', 'tool_sitepolicy'),
            new moodle_url("/{$CFG->admin}/tool/sitepolicy/index.php"),
            'tool/sitepolicy:manage'
        )
    );
    $ADMIN->add(
        'tool_sitepolicy',
        new admin_externalpage(
            'tool_sitepolicy-userconsentreport',
            get_string('userconsentreport', 'tool_sitepolicy'),
            new moodle_url("/{$CFG->admin}/tool/sitepolicy/sitepolicyreport.php"),
            'tool/sitepolicy:manage'
        )
    );
}
