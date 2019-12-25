<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/certification/classes/observer.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');

/**
 * Class totara_program_observer_testcase
 *
 * Tests functions found within the totara_program_observer class.
 */
class totara_certification_observer_testcase extends reportcache_advanced_testcase {

    private $users;
    private $course1, $course2, $course3, $course4;
    private $certif1, $certif2;

    protected function tearDown() {
        $this->users = null;
        $this->course1 = $this->course2 = $this->course3 = $this->course4 = null;
        $this->certif1 = $this->certif2 = null;
        parent::tearDown();
    }

    public function setUp() {
        global $DB, $CFG;

        parent::setUp();

        $this->resetAfterTest(true);
        $CFG->enablecompletion = true;


        $this->users = array();
        for ($i = 1; $i <= 5; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }

        $this->course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $this->course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $this->course3 = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $this->course4 = $this->getDataGenerator()->create_course(array('enablecompletion' => true));

        // Reload courses. Otherwise when we compare the courses with the returned courses,
        // we get subtle differences in some values such as cacherev and sortorder.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
        $this->course4 = $DB->get_record('course', array('id' => $this->course4->id));

        // Create a certification.
        $certdata = array(
            'cert_activeperiod' => '6 Months',
            'cert_windowperiod' => '2 Months',
        );
        $this->certif1 = $this->getDataGenerator()->create_certification($certdata);

        $this->getDataGenerator()->add_courseset_program($this->certif1->id, array($this->course1->id, $this->course2->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($this->certif1->id, array($this->course3->id), CERTIFPATH_RECERT);

        // Assign some users.
        $this->getDataGenerator()->assign_to_program($this->certif1->id, ASSIGNTYPE_INDIVIDUAL, $this->users[1]->id);
        $this->getDataGenerator()->assign_to_program($this->certif1->id, ASSIGNTYPE_INDIVIDUAL, $this->users[2]->id);
        $this->getDataGenerator()->assign_to_program($this->certif1->id, ASSIGNTYPE_INDIVIDUAL, $this->users[3]->id);

        // Create a certification.
        $certdata = array(
            'cert_activeperiod' => '6 Months',
            'cert_windowperiod' => '2 Months',
        );
        $this->certif2 = $this->getDataGenerator()->create_certification($certdata);

        $this->getDataGenerator()->add_courseset_program($this->certif2->id, array($this->course4->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($this->certif2->id, array($this->course4->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->assign_to_program($this->certif2->id, ASSIGNTYPE_INDIVIDUAL, $this->users[3]->id);
        $this->getDataGenerator()->assign_to_program($this->certif2->id, ASSIGNTYPE_INDIVIDUAL, $this->users[4]->id);
        $this->getDataGenerator()->assign_to_program($this->certif2->id, ASSIGNTYPE_INDIVIDUAL, $this->users[5]->id);
    }

    public function test_course_started() {
        $this->resetAfterTest(true);
        global $DB;

        $now = time();
        $past = $now - 1000;
        $future = $now + 1000;

        // Check the first user has not started the program.

        // Mark the first course as started for user 1 and make sure they are marked as in progress.
        $status0 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[1]->id, 'certifid' => $this->certif1->certifid));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $status0);
        $completion = new completion_completion(array('userid' => $this->users[1]->id, 'course' => $this->course1->id));
        $completion->mark_inprogress($past);
        $status1 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[1]->id, 'certifid' => $this->certif1->certifid));
        $this->assertEquals(CERTIFSTATUS_INPROGRESS, $status1);

        // Check that has had no effect on user 2, then start the recert course and make sure that has no effect as well.
        $status2 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[2]->id, 'certifid' => $this->certif1->certifid));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $status2);
        $completion = new completion_completion(array('userid' => $this->users[2]->id, 'course' => $this->course3->id));
        $completion->mark_inprogress($past);
        $status3 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[2]->id, 'certifid' => $this->certif1->certifid));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $status3);

        // Check that starting user 3 in cert 1 doesn't effect his status in cert 2.
        $status4 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[3]->id, 'certifid' => $this->certif1->certifid));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $status4);
        $completion = new completion_completion(array('userid' => $this->users[3]->id, 'course' => $this->course1->id));
        $completion->mark_inprogress($past);
        $status5 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[3]->id, 'certifid' => $this->certif1->certifid));
        $this->assertEquals(CERTIFSTATUS_INPROGRESS, $status5);
        $status6 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[3]->id, 'certifid' => $this->certif2->certifid));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $status6);

        // Finally get user 5 started in certif 2, and make sure it works as expected.
        $status7 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[5]->id, 'certifid' => $this->certif2->certifid));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $status7);
        $completion = new completion_completion(array('userid' => $this->users[5]->id, 'course' => $this->course4->id));
        $completion->mark_inprogress($future);
        $status8 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[5]->id, 'certifid' => $this->certif2->certifid));
        $this->assertEquals(CERTIFSTATUS_INPROGRESS, $status8);
        $status9 = $DB->get_field('certif_completion', 'status', array('userid' => $this->users[4]->id, 'certifid' => $this->certif2->certifid));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $status9);
    }
}
