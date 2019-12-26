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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'totara/program:createprogram',
    'totara/program:configuredetails',
    'totara/core:programmanagecustomfield',
);
if ($hassiteconfig or has_any_capability($capabilities, $systemcontext)) {

    $programsenabled = totara_feature_disabled('programs');

    $ADMIN->add('programs', new admin_externalpage(
        'programmgmt',
        new lang_string('manageprograms', 'admin'),
        $CFG->wwwroot . '/totara/program/manage.php',
        ['totara/program:createprogram', 'totara/program:configuredetails'],
        $programsenabled
    ));

    $ADMIN->add('programs', new admin_externalpage(
        'programcustomfields',
        new lang_string('customfields', 'totara_customfield'),
    $CFG->wwwroot . '/totara/customfield/index.php?prefix=program',
        ['totara/core:programmanagecustomfield'],
        $programsenabled
    ));

    unset($programsenabled);
}
