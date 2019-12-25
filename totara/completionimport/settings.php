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
 * @author  Russell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage completionimport
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('courses',
    new admin_category('totara_completionimport',
      get_string('completionimport', 'totara_completionimport'))
);

$options = array(
    0    => new lang_string('neverdeletelogs'),
    1000 => new lang_string('numdays', '', 1000),
    365  => new lang_string('numdays', '', 365),
    180  => new lang_string('numdays', '', 180),
    150  => new lang_string('numdays', '', 150),
    120  => new lang_string('numdays', '', 120),
    90   => new lang_string('numdays', '', 90),
    60   => new lang_string('numdays', '', 60),
    35   => new lang_string('numdays', '', 35),
    10   => new lang_string('numdays', '', 10),
    5    => new lang_string('numdays', '', 5),
    2    => new lang_string('numdays', '', 2)
);

$settings = new admin_settingpage(
    'complrecordssettings',
    get_string('settings', 'totara_completionimport'),
    'totara/completionimport:import'
);

$settings->add(
    new admin_setting_configselect('complrecords/courseloglifetime',
    new lang_string('courseloglifetime', 'totara_completionimport'),
    new lang_string('courseloglifetime_desc', 'totara_completionimport'),
        0,
        $options));

$settings->add(
    new admin_setting_configselect('complrecords/certificationloglifetime',
    new lang_string('certificationloglifetime', 'totara_completionimport'),
    new lang_string('certificationloglifetime_desc', 'totara_completionimport'),
        0,
        $options));

$ADMIN->add('totara_completionimport', $settings);

$ADMIN->add('totara_completionimport',
        new admin_externalpage(
                'totara_completionimport_upload',
                get_string('completionimport', 'totara_completionimport'),
                new moodle_url('/totara/completionimport/upload.php'),
                array('totara/completionimport:import')));

$ADMIN->add('totara_completionimport',
        new admin_externalpage(
                'totara_completionimport_course',
                get_string('report_course', 'totara_completionimport'),
                new moodle_url('/totara/completionimport/viewreport.php', array('importname' => 'course', 'clearfilters' => 1)),
                array('totara/completionimport:import')));

$ADMIN->add('totara_completionimport',
        new admin_externalpage(
                'totara_completionimport_certification',
                get_string('report_certification', 'totara_completionimport'),
                new moodle_url('/totara/completionimport/viewreport.php', array('importname' => 'certification', 'clearfilters' => 1)),
                array('totara/completionimport:import'),
                totara_feature_disabled('certifications')
        ));

$ADMIN->add('totara_completionimport',
        new admin_externalpage(
                'totara_completionimport_reset',
                get_string('resetimport', 'totara_completionimport'),
                new moodle_url('/totara/completionimport/reset.php'),
                array('totara/completionimport:import')));