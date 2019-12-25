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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 */

$functions = array(

    // Resorts all of the job assignments held by a single user.
    'totara_job_external_resort_job_assignments' => array(
        'classname'         => 'totara_job_external',
        'methodname'        => 'resort_job_assignments',
        'classpath'         => 'totara/job/externallib.php',
        'description'       => 'Resorts all of a users job assignments',
        'type'              => 'write',
        'loginrequired'     => true,
        'ajax'              => true,
        'capabilities'      => 'totara/hierarchy:viewposition, moodle/user:viewdetails, totara/hierarchy:assignuserposition, '
                             . 'totara/hierarchy:assignselfposition'
    ),

    // Deletes a job assignment for a given user.
    'totara_job_external_delete_job_assignment' => array(
        'classname'         => 'totara_job_external',
        'methodname'        => 'delete_job_assignment',
        'classpath'         => 'totara/job/externallib.php',
        'description'       => 'Deletes a given job assignment from a given used.',
        'type'              => 'write',
        'loginrequired'     => true,
        'ajax'              => true,
        'capabilities'      => 'totara/hierarchy:viewposition, moodle/user:viewdetails, totara/hierarchy:assignuserposition, '
            . 'totara/hierarchy:assignselfposition'
    ),

);
