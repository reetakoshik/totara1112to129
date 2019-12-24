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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/plan/db/upgradelib.php');

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_plan_upgradelib_testcase totara/plan/tests/upgradelib_test.php
 */
class totara_plan_upgradelib_testcase extends advanced_testcase {

    public function test_totara_plan_upgrade_fix_invalid_program_duedates() {
        global $DB;

        $this->resetAfterTest(true);

        // Fake the plan and program ids. They're not important.
        $plan1id = 123;
        $plan2id = 234;
        $plan3id = 345;
        $program1id = 456;
        $program2id = 567;
        $program3id = 678;
        $program4id = 789;

        $records = array(
            array('planid' => $plan1id, 'programid' => $program1id, 'duedate' => 0),
            array('planid' => $plan1id, 'programid' => $program2id, 'duedate' => -1), // Should be changed to 0.
            array('planid' => $plan1id, 'programid' => $program3id, 'duedate' => 333),
            array('planid' => $plan1id, 'programid' => $program4id, 'duedate' => -1), // Should be changed to 0.
            array('planid' => $plan2id, 'programid' => $program1id, 'duedate' => 555),
            array('planid' => $plan2id, 'programid' => $program2id, 'duedate' => null),
            array('planid' => $plan2id, 'programid' => $program3id, 'duedate' => 777),
            array('planid' => $plan3id, 'programid' => $program1id, 'duedate' => null),
            array('planid' => $plan3id, 'programid' => $program2id, 'duedate' => -1), // Should be changed to 0.
            array('planid' => $plan3id, 'programid' => $program4id, 'duedate' => 0),
        );
        foreach ($records as $record) {
            $DB->insert_record('dp_plan_program_assign', $record);
        }

        // Run the upgrade.
        totara_plan_upgrade_fix_invalid_program_duedates();

        // The table has the same number of records as before.
        $results = $DB->get_records('dp_plan_program_assign');
        $this->assertEquals(count($records), count($results));

        // Check each record exists in the db.
        foreach ($records as $record) {
            $recordduedate = $record['duedate'];
            unset($record['duedate']);

            $results = $DB->get_records('dp_plan_program_assign', $record);

            $this->assertCount(1, $results);
            $result = reset($results);

            if (is_null($recordduedate)) {
                $this->assertNull($result->duedate);
            } else if ($recordduedate === -1) {
                $this->assertEquals(0, $result->duedate);
            } else {
                $this->assertEquals($recordduedate, $result->duedate);
            }
        }
    }
}
