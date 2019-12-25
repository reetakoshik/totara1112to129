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
 * @author Murali Nair (murali.nair@totaralearning.com)
 * @package totara_appraisal
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/appraisal/tests/appraisal_testcase.php');

/**
 * Appraisal snapshot related tests.
 * @group totara_appraisal
 */
class appraisal_snapshots_test extends appraisal_testcase {
    /**
     * Tests that appraisal snapshots are deleted when the learner is removed as
     * a system user.
     */
    public function test_snapshots_deleted_when_learner_deleted() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => 7,
                        appraisal::ROLE_MANAGER => 1
                    ))
                ))
            ))
        ));
        list($appraisal, $users) = $this->prepare_appraisal_with_users($def);

        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $appraisalid = $appraisal->id;
        $snapshotfilter = "
            SELECT COUNT(id)
            FROM {files}
            WHERE component = 'totara_appraisal'
            AND filearea LIKE 'snapshot%'
        ";
        $this->assertSame(0, $DB->count_records_sql($snapshotfilter), "wrong files snapshot count BEFORE creating snapshots");
        $this->assertCount(0, appraisal::list_snapshots($appraisalid, null), "wrong snapshot count");

        $learner = $users[0];
        $mgr = get_admin()->id;
        $filepath = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $userswithsnapshots = [
            appraisal::ROLE_LEARNER => $learner->id,
            appraisal::ROLE_MANAGER => $mgr
        ];
        foreach ($userswithsnapshots as $role => $userid) {
            $roleassignment = appraisal_role_assignment::get_role($appraisalid, $learner->id, $userid, $role);
            $this->assertNotEmpty($roleassignment, "no appraisal $appraisalid role assignment for user $userid, role $role");
            $appraisal->save_snapshot($filepath, $roleassignment->id);
        }

        // Notice there are x2 records for the files table; this is because there is a "directory" entry for every
        // uploaded file entry.
        $noofsnapshots = count($userswithsnapshots);
        $this->assertSame($noofsnapshots*2, $DB->count_records_sql($snapshotfilter), "wrong files snapshot count BEFORE delete");
        $this->assertCount($noofsnapshots, appraisal::list_snapshots($appraisalid, null), "wrong snapshot count BEFORE delete");

        $deleteevent =  \core\event\user_deleted::create([
            'relateduserid' => $learner->id,
            'objectid' => $learner->id,
            'context' => \context_system::instance(),
            'other' => [
                'username' => $learner->username,
                'email' => $learner->email,
                'idnumber' => $learner->idnumber,
                'picture' => $learner->picture,
                'mnethostid' => $learner->mnethostid
            ]
        ]);
        \totara_appraisal_observer::user_deleted($deleteevent);

        $this->assertCount(0, appraisal::list_snapshots($appraisalid, null), "wrong snapshot count AFTER delete");
        $this->assertSame(0, $DB->count_records_sql($snapshotfilter), "wrong files snapshot count AFTER delete");
    }


    /**
     * Tests that appraisal snapshots are deleted when the entire appraisal is
     * deleted.
     */
    public function test_snapshots_deleted_when_appraisal_deleted() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => 7,
                        appraisal::ROLE_MANAGER => 1
                    ))
                ))
            ))
        ));
        list($appraisal, $users) = $this->prepare_appraisal_with_users($def);

        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $appraisalid = $appraisal->id;
        $snapshotfilter = "
            SELECT COUNT(id)
            FROM {files}
            WHERE component = 'totara_appraisal'
            AND filearea LIKE 'snapshot%'
        ";
        $this->assertSame(0, $DB->count_records_sql($snapshotfilter), "wrong files snapshot count BEFORE creating snapshots");
        $this->assertCount(0, appraisal::list_snapshots($appraisalid, null), "wrong snapshot count");

        $mgr = get_admin()->id;
        $filepath = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        foreach ($users as $user) {
            $userid = $user->id;
            $roleassignment = appraisal_role_assignment::get_role($appraisalid, $userid, $userid, appraisal::ROLE_LEARNER);
            $this->assertNotEmpty($roleassignment, "no appraisal $appraisalid role assignment for user $userid, role learner");
            $appraisal->save_snapshot($filepath, $roleassignment->id);

            $roleassignment = appraisal_role_assignment::get_role($appraisalid, $userid, $mgr, appraisal::ROLE_MANAGER);
            $this->assertNotEmpty($roleassignment, "no appraisal $appraisalid role assignment for manager");
            $appraisal->save_snapshot($filepath, $roleassignment->id);
        }

        // Notice there are x2 records for the files table; this is because there is a "directory" entry for every
        // uploaded file entry.
        $noofsnapshots = count($users) * 2; // 1 each snapshot for the learner AND his manager
        $this->assertSame($noofsnapshots*2, $DB->count_records_sql($snapshotfilter), "wrong files snapshot count BEFORE delete");
        $this->assertCount($noofsnapshots, appraisal::list_snapshots($appraisalid, null), "wrong snapshot count BEFORE delete");

        $appraisal->delete();

        $this->assertCount(0, appraisal::list_snapshots($appraisalid, null), "wrong snapshot count AFTER delete");
        $this->assertSame(0, $DB->count_records_sql($snapshotfilter), "wrong files snapshot count AFTER delete");
    }
}
