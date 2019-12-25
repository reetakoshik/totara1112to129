<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package core
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/completion/completion_completion.php');

// Get CLI options.
list($options, $unrecognized) = cli_get_params(
    array(
        'courseid' => false,
        'starttime' => false,
        'endtime' => false,
        'help' => false
    ),
    array(
        'c' => 'courseid',
        's' => 'starttime',
        'e' => 'endtime',
        'h' => 'help'
    )
);

if ($options['help'] || empty($options['courseid'])) {
    $help = "Bulk-deletes unnecessary course completion records created when users are enrolled and then immediately 
unenrolled from a course without starting it.

To prevent deletion of historic, unstarted enrolments, use the start and end time parameters to
limit deletion to records that were created in a specific window of time.

Options:
-c, --courseid        Id of the course to delete completions for. Required
-s, --starttime       Minimum enrolment time (in seconds since Unix Epoch) of records to delete
-e, --endtime         Maximum enrolment time (in seconds since Unix Epoch) of records to delete
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/delete_unused_course_completions.php --courseid=287
\$sudo -u www-data /usr/bin/php admin/cli/delete_unused_course_completions.php -c=287 -s=1564527800 -e=1564527860
";

    echo $help;
    die;
}

// Look up course.
$conditions = ['id' => $options['courseid']];
$course = $DB->get_record('course', $conditions);
if (empty($course->id)) {
    echo "Course {$options['courseid']} not found.\n";
    exit(1);
}

// Validate times.
if (!empty($options['starttime'])) {
    if (intval($options['starttime']) != $options['starttime'] || $options['starttime'] < 1) {
        echo "Start time must be a valid Unix timestamp.\n";
        exit(1);
    }
    $startdate = date('Y-m-d H:i:s', $options['starttime']);
}
if (!empty($options['endtime'])) {
    if (intval($options['endtime']) != $options['endtime'] || $options['endtime'] < 1) {
        echo "End time must be a valid Unix timestamp.\n";
        exit(1);
    }
    if (!empty($options['starttime']) && $options['starttime'] > $options['endtime']) {
        echo "End time must be later than or equal to start time.\n";
        exit(1);
    }
    $enddate = date('Y-m-d H:i:s', $options['endtime']);
}

// Prepare parameters.
$parameters = ['courseid' => $options['courseid']];

// Is there an enrolment timeframe?
$time_where = "";
$between = "";
if (!empty($options['starttime'])) {
    $time_where .= "AND timeenrolled >= :starttime ";
    $parameters['starttime'] = $options['starttime'];
    if (!empty($options['endtime'])) {
        $between = ", created between '{$startdate}' and '{$enddate}'";
    } else {
        $between = ", created before '{$startdate}'";
    }
}
if (!empty($options['endtime'])) {
    $time_where .= "AND timeenrolled <= :endtime ";
    $parameters['endtime'] = $options['endtime'];
    if (empty($options['starttime'])) {
        $between = ", created after '{$enddate}'";
    }
}

// Final parameters, and build core of query.
$parameters['subcourseid'] = $options['courseid'];
$parameters['enrolment_status'] = ENROL_USER_ACTIVE;
$parameters['completion_status'] = COMPLETION_STATUS_NOTYETSTARTED;
$sql = "FROM {course_completions}
       WHERE course = :courseid 
             {$time_where}
         AND userid NOT IN (
                 SELECT ue.userid 
                   FROM mdl_user_enrolments ue 
             INNER JOIN mdl_enrol e ON e.id = ue.enrolid 
                  WHERE e.courseid = :subcourseid 
                    AND ue.status = :enrolment_status
             ) 
         AND status = :completion_status
         AND timestarted = 0";

// Determine number of records affected.
$select_sql = "SELECT count(id) AS affected " . $sql;
$count = $DB->get_record_sql($select_sql, $parameters);

if ($count->affected < 1) {
    echo "No completion records match.\n";
    exit(1);
}

// Confirm.
echo "Delete {$count->affected} empty completion records for {$course->fullname}{$between}?\n";
$prompt = get_string('cliyesnoprompt', 'admin');
$input = cli_input($prompt, '', array(get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')));
if ($input == get_string('clianswerno', 'admin')) {
    echo "Cancelled.\n";
    exit(1);
}

// Do delete.
$delete_sql = "DELETE " . $sql;
$DB->execute($delete_sql, $parameters);

// Check our work.
$count = $DB->get_record_sql($select_sql, $parameters);
if ($count->affected > 0) {
    echo "Something went wrong, there are still {$count->affected} matching records.\n";
    exit(1);
}

echo "Done.\n";
exit(0);
