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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_plan
 */

use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

class totara_plan_capability_testcase extends advanced_testcase {

    /**
     * Test can_create_or_edit_evidence() in evidence/lib.php handles permissions correctly.
     */
    public function test_can_create_or_edit_evidence() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/plan/record/evidence/lib.php');

        $user_role = $DB->get_record('role', ['shortname' => 'user'])->id;
        $context = context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1->id);

        // Without any capabilities or job assignments, user can only create (and not edit) evidence for themselves
        unassign_capability('totara/plan:editownsiteevidence', $user_role);
        $this->assertTrue(can_create_or_edit_evidence($user1->id));
        $this->assertFalse(can_create_or_edit_evidence($user1->id, true));
        $this->assertFalse(can_create_or_edit_evidence($user1->id, false, true));
        $this->assertFalse(can_create_or_edit_evidence($user2->id));

        // User is now managing another user and can edit their evidence
        $manager_job = job_assignment::create(['userid' => $user1->id, 'idnumber' => 1]);
        job_assignment::create(['userid' => $user2->id, 'idnumber' => 2, 'managerjaid' => $manager_job->id]);
        $this->assertFalse(can_create_or_edit_evidence($user1->id, 1));
        $this->assertTrue(can_create_or_edit_evidence($user2->id));
        $this->assertTrue(can_create_or_edit_evidence($user2->id, true));
        $this->assertFalse(can_create_or_edit_evidence($user2->id, false, true));

        // User can edit as well as create evidence for themselves
        assign_capability('totara/plan:editownsiteevidence', CAP_ALLOW, $user_role, $context);
        $this->assertTrue(can_create_or_edit_evidence($user1->id, true));
        $this->assertFalse(can_create_or_edit_evidence($user1->id, false, true));
        unassign_capability('totara/plan:editownsiteevidence', $user_role);

        // User can edit any evidence in the system including read only evidence
        assign_capability('totara/plan:editsiteevidence', CAP_ALLOW, $user_role, $context);
        $this->assertTrue(can_create_or_edit_evidence($user1->id));
        $this->assertTrue(can_create_or_edit_evidence($user1->id, true));
        $this->assertTrue(can_create_or_edit_evidence($user1->id, false, true));
        $this->assertTrue(can_create_or_edit_evidence($user2->id));
        unassign_capability('totara/plan:editsiteevidence', $user_role);

        // User can edit any evidence in the system including read only evidence
        assign_capability('totara/plan:accessanyplan', CAP_ALLOW, $user_role, $context);
        $this->assertTrue(can_create_or_edit_evidence($user1->id));
        $this->assertTrue(can_create_or_edit_evidence($user1->id, true));
        $this->assertTrue(can_create_or_edit_evidence($user1->id, false, true));
        $this->assertTrue(can_create_or_edit_evidence($user2->id));
        unassign_capability('totara/plan:accessanyplan', $user_role);
    }

}
