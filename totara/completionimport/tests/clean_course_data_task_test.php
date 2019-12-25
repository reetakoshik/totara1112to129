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
 * Tests importing courses from a generated csv file
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit importcourse_testcase totara/completionimport/tests/reset_course_data_task_test.php
 *
 * @package    totara_completionimport
 * @subpackage phpunit
 * @author     Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

define('COURSE_IMPORT_USERS', 11);
define('COURSE_IMPORT_COURSES', 11);
define('COURSE_IMPORT_CSV_ROWS', 100);

/**
 * Class clean_course_data_task_testcase
 *
 * @group totara_completionimport
 */
class clean_course_data_task_testcase extends advanced_testcase {

    public function test_task() {
        global $CFG, $DB;

        require_once($CFG->libdir . '/csvlib.class.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/totara/completionimport/lib.php');

        set_config('enablecompletion', 1);
        $this->resetAfterTest(true);

        $importname = 'course';
        $pluginname = 'totara_completionimport_' . $importname;
        $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);

        $this->setAdminUser();

        // Create courses with completion enabled.
        $coursedefaults = array('enablecompletion' => COMPLETION_ENABLED);
        for ($i = 1; $i <= COURSE_IMPORT_USERS; $i++) {
            $this->getDataGenerator()->create_course($coursedefaults);
        }

        // Create users.
        for ($i = 1; $i <= COURSE_IMPORT_COURSES; $i++) {
            $this->getDataGenerator()->create_user();
        }

        // Generate import data - product of user and course tables.
        $fields = array('username', 'courseshortname', 'courseidnumber', 'completiondate', 'grade');
        // Start building the content that would be returned from a csv file.
        $content = implode(",", $fields) . "\n";

        $uniqueid = $DB->sql_concat('u.username', 'c.shortname');
        $sql = "SELECT  {$uniqueid} AS uniqueid,
                        u.username,
                        c.shortname AS courseshortname,
                        c.idnumber AS courseidnumber
                FROM    {user} u,
                        {course} c
                WHERE   u.id > 2
                AND     c.id > 1";
        $imports = $DB->get_recordset_sql($sql, null, 0, COURSE_IMPORT_CSV_ROWS);
        if ($imports->valid()) {
            $count = 0;
            foreach ($imports as $import) {
                $data = array();
                $data['username'] = $import->username;
                $data['courseshortname'] = $import->courseshortname;
                $data['courseidnumber'] = $import->courseidnumber;
                $data['completiondate'] = date($csvdateformat, strtotime(date('Y-m-d') . ' -' . rand(1, 365) . ' days'));
                $data['grade'] = rand(1, 100);
                $content .= implode(",", $data) . "\n";
                $count++;
            }
            // Create records to save them as evidence.
            $countevidence = 2;
            for ($i = 1; $i <= $countevidence; $i++) {
                $lastrecord = $data;
                $data['username'] = $lastrecord['username'];
                $data['courseshortname'] = ($i == 1) ? 'mycourseshortname' : $lastrecord['courseshortname'];
                $data['courseidnumber'] = 'XXXY';
                $data['completiondate'] = $lastrecord['completiondate'];
                $data['grade'] = rand(1, 100);
                $content .= implode(",", $data) . "\n";
                $count++;
            }
        }
        $imports->close();

        $totalcourserows = COURSE_IMPORT_CSV_ROWS + $countevidence;

        $importstart = time();
        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        // Test total number of records before running reset_course_report_data_task.
        $this->assertEquals($totalcourserows, $DB->count_records('totara_compl_import_course'), 'Record count mismatch in the totara_compl_import_course table');

        $time = time();
        // Set courseloglifetime to 2 days.
        $loglifetime = 2;
        set_config('courseloglifetime', $loglifetime, 'complrecords');

        // Simulate timecreated to 12 days ago.
        $timecreated = $time - (($loglifetime+10) * DAYSECS);
        $DB->execute("UPDATE {totara_compl_import_course} SET timecreated = ?", array($timecreated));

        ob_start(); // Start a buffer to catch all the mtraces in the task.
        // Run scheduled course task to remove the records.
        $task = new \totara_completionimport\task\clean_course_completion_upload_logs_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.
        // Test total number of records after running reset_course_report_data_task.
        $this->assertEquals(0, $DB->count_records('totara_compl_import_course'), 'Record count mismatch in the totara_compl_import_course table');
    }
}
