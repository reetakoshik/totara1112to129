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

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('courses', new admin_category('opensesamecourses', new lang_string('pluginname', 'repository_opensesame')));
if (get_config('repository_opensesame', 'tenantkey')) {
    $ADMIN->add('opensesamecourses',
        new admin_externalpage('opensesamebrowse',
            new lang_string('browsecatalogue', 'repository_opensesame'),
            $CFG->wwwroot . '/repository/opensesame/browse.php',
            'repository/opensesame:managepackages'
        )
    );
    $ADMIN->add('opensesamecourses',
        new admin_externalpage('opensesamereport',
            new lang_string('browsepackages', 'repository_opensesame'),
            $CFG->wwwroot . '/repository/opensesame/index.php',
            'repository/opensesame:managepackages'
        )
    );
    $ADMIN->add('opensesamecourses',
        new admin_externalpage('opensesameregister',
            new lang_string('registration', 'repository_opensesame'),
            $CFG->wwwroot . '/repository/opensesame/register.php',
            'moodle/site:config'
        )
    );
} else {
    $ADMIN->add('opensesamecourses',
        new admin_externalpage('opensesamereport',
            new lang_string('browsepackages', 'repository_opensesame'),
            $CFG->wwwroot . '/repository/opensesame/index.php',
            'repository/opensesame:managepackages',
            true
        )
    );
    $ADMIN->add('opensesamecourses',
        new admin_externalpage('opensesameregister',
            new lang_string('register', 'repository_opensesame'),
            $CFG->wwwroot . '/repository/opensesame/register.php',
            'moodle/site:config'
        )
    );
}
$ADMIN->add('opensesamecourses',
    new admin_externalpage('opensesameabout',
        new lang_string('about', 'repository_opensesame'),
        $CFG->wwwroot . '/repository/opensesame/about.php',
        'moodle/site:config'
    )
);
