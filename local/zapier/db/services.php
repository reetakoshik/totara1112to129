<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// We defined the web service functions to install.
$functions = array(
    'local_zapier_course_completion' => array(
        'classname' => 'local_zapier_external',
        'methodname' => 'course_completion',
        'classpath' => 'local/zapier/externallib.php',
        'description' => 'Return course completion info',
        'type' => 'read'
    ),
    'local_zapier_user_login' => array(
        'classname' => 'local_zapier_external',
        'methodname' => 'user_login',
        'classpath' => 'local/zapier/externallib.php',
        'description' => 'check if user can be loged in',
        'type' => 'read'
    ),
    'local_zapier_user_assign_hierarchy' => array(
        'classname'   => 'local_zapier_external',
        'methodname'  => 'user_update',
        'classpath'   => 'local/zapier/externallib.php',
        'description' => 'update users'
    )
);


// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Zapier' => array(
        'functions' => array(
            'local_zapier_course_completion', 
            'local_zapier_user_login', 
            'core_user_create_users',
            'core_user_update_users',
            'local_zapier_user_assign_hierarchy'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
