<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\userdata;

defined('MOODLE_INTERNAL') || die();

use context;
use context_course;
use context_coursecat;
use context_module;
use context_system;
use mod_facetoface_facetoface_testcase;
use phpunit_util;
use stdClass;
use totara_core\event\user_suspended;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

global $CFG;
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/tests/facetoface_testcase.php');

/**
 * Test signups item
 *
 * @group totara_userdata
 */
class mod_facetoface_userdata_signups_testcase extends mod_facetoface_facetoface_testcase {


    /**
     * Set up tests.
     */
    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test purging of signups in system context.
     */
    public function test_purge_in_system_context() {
        global $DB;
        $this->setAdminUser(); // Necessary for file handling.

        $datagenerator = $this->getDataGenerator();
        /** @var \mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $datagenerator->get_plugin_generator('mod_facetoface');

        $course = $datagenerator->create_course();

        $student1 = $datagenerator->create_user();
        $student2 = $datagenerator->create_user();

        $session1 = $f2fgenerator->create_session_for_course($course);
        $session2 = $f2fgenerator->create_session_for_course($course, 2);

        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        $signups = [];
        $signups[11] = $f2fgenerator->create_signup($student1, $session1);

        $signupcustomfieldids = [];
        $signupcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'signup', 3, 1);

        $f2fgenerator->create_cancellation($student1, $session1);

        $cancellationcustomfieldids = [];
        $cancellationcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'cancellation', 1, 3);

        $signups[12] = $f2fgenerator->create_signup($student1, $session2);
        $signups[21] = $f2fgenerator->create_signup($student2, $session1);
        $signups[22] = $f2fgenerator->create_signup($student2, $session2);

        $signupcustomfieldids[12] = $f2fgenerator->create_customfield_data($signups[12], 'signup', 1, 3);
        $signupcustomfieldids[21] = $f2fgenerator->create_customfield_data($signups[21], 'signup', 4, 1);
        $signupcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'signup', 1, 4);

        $f2fgenerator->create_cancellation($student2, $session2);
        $cancellationcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'cancellation', 3, 1);

        $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile1.txt', 1);
        $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile2.txt', 1);
        $f2fgenerator->create_file_customfield($signups[11], 'cancellation', 'testfile3.txt', 2);
        $f2fgenerator->create_file_customfield($signups[21], 'signup', 'testfile4.txt', 3);
        $f2fgenerator->create_file_customfield($signups[22], 'cancellation', 'testfile5.txt', 4);

        $this->execute_adhoc_tasks();

        // Purge data in system context.
        $targetuser = new target_user($student1);
        $sink = $this->redirectEvents();
        $status = signups::execute_purge($targetuser, context_system::instance());
        $events = $sink->get_events();
        $sink->close();

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check that signup cancelling was called correctly (triggers event signup_status_updated).
        $this->assertCount(2, $events);
        $this->assertInstanceOf('\mod_facetoface\event\signup_status_updated', $events[0]);
        $this->assertInstanceOf('\mod_facetoface\event\booking_cancelled', $events[1]);
        $event = $events[0];
        $event_data = $event->get_data();
        $this->assertEquals($student1->id, $event_data['other']['userid']);

        // Verify expected data. Everything should be purged for student1 while nothing should be purged for student2.
        $this->assertEquals(0, $DB->count_records('facetoface_signups', ['userid' => $student1->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_signups', ['userid' => $student2->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_notification_sent', ['userid' => $student1->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_notification_sent', ['userid' => $student2->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_notification_hist', ['userid' => $student1->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_notification_hist', ['userid' => $student2->id]));

        $select_signups_status = 'SELECT id FROM {facetoface_signups_status} WHERE signupid ';
        list($sqlin, $inparams) = $DB->get_in_or_equal([$signups[11]->id, $signups[12]->id]);
        $this->assertCount(0, $DB->get_records_sql($select_signups_status . $sqlin, $inparams));

        $select_signups_status = 'SELECT id FROM {facetoface_signups_status} WHERE signupid ';
        list($sqlin, $inparams) = $DB->get_in_or_equal([$signups[21]->id, $signups[22]->id]);
        $this->assertCount(3, $DB->get_records_sql($select_signups_status . $sqlin, $inparams));

        $this->assert_count_customfield_data('signup', [$signups[11]->id, $signups[12]->id], 0, 0);
        $this->assert_count_customfield_data('signup', [$signups[21]->id], 5, 1);
        $this->assert_count_customfield_data('signup', [$signups[22]->id], 1, 4);
        $this->assert_count_customfield_data('cancellation', [$signups[11]->id, $signups[12]->id, $signups[21]->id], 0, 0);
        $this->assert_count_customfield_data('cancellation', [$signups[22]->id], 4, 1);

        // Files should be purged as well.
        $this->assert_leftover_files($student1->id, []);
        $this->assert_leftover_files($student2->id, ['testfile4.txt', 'testfile5.txt']);
    }

    /**
     * Data provider to loop through the possible user statuses.
     *
     * @return array
     */
    public function user_status_data_provider() {
        return [
            ['active'],
            ['deleted'],
            ['suspended'],
        ];
    }

    /**
     * Test purging of signups in module context.
     *
     * @param string $userstatus
     * @dataProvider user_status_data_provider
     */
    public function test_purge_in_module_context(string $userstatus) {
        global $DB;
        $this->setAdminUser();

        $datagenerator = $this->getDataGenerator();
        /** @var \mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $datagenerator->get_plugin_generator('mod_facetoface');

        $course = $datagenerator->create_course();

        $student1 = $datagenerator->create_user();
        $student2 = $datagenerator->create_user();

        $session1 = $f2fgenerator->create_session_for_course($course);
        $session2 = $f2fgenerator->create_session_for_course($course, 2);

        $this->getDataGenerator()->enrol_user($student1->id, $course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id);

        $signups = [];
        $signups[11] = $f2fgenerator->create_signup($student1, $session1);
        $signups[12] = $f2fgenerator->create_signup($student1, $session2);
        $signups[21] = $f2fgenerator->create_signup($student2, $session1);
        $signups[22] = $f2fgenerator->create_signup($student2, $session2);

        $emailsink = $this->redirectMessages();
        $this->execute_adhoc_tasks();
        $emailsink->close();

        $signupcustomfieldids = [];
        $signupcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'signup', 3, 1);
        $signupcustomfieldids[12] = $f2fgenerator->create_customfield_data($signups[12], 'signup', 1, 3);
        $signupcustomfieldids[21] = $f2fgenerator->create_customfield_data($signups[21], 'signup', 4, 1);
        $signupcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'signup', 1, 4);

        $f2fgenerator->create_cancellation($student1, $session1);
        $f2fgenerator->create_cancellation($student2, $session2);

        $cancellationcustomfieldids = [];
        $cancellationcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'cancellation', 1, 3);
        $cancellationcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'cancellation', 3, 1);

        $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile1.txt', 1);
        $f2fgenerator->create_file_customfield($signups[12], 'signup', 'testfile2.txt', 2);
        $f2fgenerator->create_file_customfield($signups[12], 'cancellation', 'testfile3.txt', 3);
        $f2fgenerator->create_file_customfield($signups[21], 'signup', 'testfile4.txt', 4);
        $f2fgenerator->create_file_customfield($signups[22], 'cancellation', 'testfile5.txt', 5);

        $student1 = $this->handle_user_status($student1, $userstatus);

        $this->execute_adhoc_tasks();

        // Purge data in module context.
        $coursemodule = get_coursemodule_from_instance('facetoface', $session2->facetoface);
        $targetuser = new target_user($student1);
        $sink = $this->redirectEvents();
        $status = signups::execute_purge($targetuser, context_module::instance($coursemodule->id));
        $events = $sink->get_events();
        $sink->close();

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        $this->assert_signup_status_updated_event($targetuser, $events);

        // Verify expected data. Only student1/session2 data should be purged.
        $this->assertEquals(1, $DB->count_records('facetoface_signups', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_signups', ['userid' => $student1->id, 'sessionid' => $session1->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_signups', ['userid' => $student2->id]));

        $this->assertEquals(1, $DB->count_records('facetoface_notification_sent', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_notification_sent', ['userid' => $student1->id, 'sessionid' => $session1->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_notification_sent', ['userid' => $student2->id]));

        $this->assertEquals(1, $DB->count_records('facetoface_notification_hist', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_notification_hist', ['userid' => $student1->id, 'sessionid' => $session1->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_notification_hist', ['userid' => $student2->id]));

        $this->assertEquals(2, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[11]->id]));
        $this->assertFalse($DB->record_exists('facetoface_signups_status', ['signupid' => $signups[12]->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[21]->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[22]->id]));

        $this->assert_count_customfield_data('signup', [$signups[11]->id], 4, 1);
        $this->assert_count_customfield_data('signup', [$signups[12]->id], 0, 0);
        $this->assert_count_customfield_data('signup', [$signups[21]->id], 5, 1);
        $this->assert_count_customfield_data('signup', [$signups[22]->id], 1, 4);
        $this->assert_count_customfield_data('cancellation', [$signups[11]->id], 1, 3);
        $this->assert_count_customfield_data('cancellation', [$signups[12]->id, $signups[21]->id], 0, 0);
        $this->assert_count_customfield_data('cancellation', [$signups[22]->id], 4, 1);

        // Files should be purged as well.
        $this->assert_leftover_files($student1->id, ['testfile1.txt']);
        $this->assert_leftover_files($student2->id, ['testfile4.txt', 'testfile5.txt']);
    }

    /**
     * Test purging of signups in course context.
     *
     * @param string $userstatus
     * @dataProvider user_status_data_provider
     */
    public function test_purge_in_course_context(string $userstatus) {
        global $DB;
        $this->setAdminUser();

        $datagenerator = $this->getDataGenerator();
        /** @var \mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $datagenerator->get_plugin_generator('mod_facetoface');

        $course1 = $datagenerator->create_course();
        $course2 = $datagenerator->create_course();

        $student1 = $datagenerator->create_user();
        $student2 = $datagenerator->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course2->id);

        $session1 = $f2fgenerator->create_session_for_course($course1);
        $session2 = $f2fgenerator->create_session_for_course($course1, 2);
        $session3 = $f2fgenerator->create_session_for_course($course2, 3);

        $signups = [];
        $signups[11] = $f2fgenerator->create_signup($student1, $session1);
        $signups[12] = $f2fgenerator->create_signup($student1, $session2);
        $signups[13] = $f2fgenerator->create_signup($student1, $session3);
        $signups[21] = $f2fgenerator->create_signup($student2, $session1);
        $signups[22] = $f2fgenerator->create_signup($student2, $session2);
        $signups[23] = $f2fgenerator->create_signup($student2, $session3);

        $emailsink = $this->redirectMessages();
        $this->execute_adhoc_tasks();
        $emailsink->close();

        $signupcustomfieldids = [];
        $signupcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'signup', 3, 1);
        $signupcustomfieldids[12] = $f2fgenerator->create_customfield_data($signups[12], 'signup', 1, 3);
        $signupcustomfieldids[13] = $f2fgenerator->create_customfield_data($signups[13], 'signup', 4, 5);
        $signupcustomfieldids[21] = $f2fgenerator->create_customfield_data($signups[21], 'signup', 4, 1);
        $signupcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'signup', 1, 4);
        $signupcustomfieldids[23] = $f2fgenerator->create_customfield_data($signups[23], 'signup', 5, 2);

        $f2fgenerator->create_cancellation($student1, $session1);
        $f2fgenerator->create_cancellation($student2, $session2);

        $cancellationcustomfieldids = [];
        $cancellationcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'cancellation', 1, 3);
        $cancellationcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'cancellation', 3, 1);

        $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile1.txt', 1);
        $f2fgenerator->create_file_customfield($signups[12], 'signup', 'testfile2.txt', 2);
        $f2fgenerator->create_file_customfield($signups[12], 'cancellation', 'testfile3.txt', 3);
        $f2fgenerator->create_file_customfield($signups[21], 'signup', 'testfile4.txt', 4);
        $f2fgenerator->create_file_customfield($signups[22], 'cancellation', 'testfile5.txt', 5);

        $student1 = $this->handle_user_status($student1, $userstatus);

        $this->execute_adhoc_tasks();

        // Purge data in course context.
        $targetuser = new target_user($student1);
        $sink = $this->redirectEvents();
        $status = signups::execute_purge($targetuser, context_course::instance($course1->id));
        $events = $sink->get_events();
        $sink->close();

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        $this->assert_signup_status_updated_event($targetuser, $events);

        // For deleted/suspended the active signups get cancelled, so we expect additional records in signups_status table.
        // Also, we expect the additional default cancellation customfield.
        $additionalcancellation = in_array($userstatus, ['deleted', 'suspended']) ? 1 : 0;

        // Verify expected data. Only student1/session1&session2 data should be purged.
        $this->assertEquals(1, $DB->count_records('facetoface_signups', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_signups', ['userid' => $student1->id, 'sessionid' => $session3->id]));
        $this->assertEquals(3, $DB->count_records('facetoface_signups', ['userid' => $student2->id]));

        $this->assertEquals(1, $DB->count_records('facetoface_notification_sent', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_notification_sent', ['userid' => $student1->id, 'sessionid' => $session3->id]));
        $this->assertEquals(3, $DB->count_records('facetoface_notification_sent', ['userid' => $student2->id]));

        $this->assertEquals(1, $DB->count_records('facetoface_notification_hist', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_notification_hist', ['userid' => $student1->id, 'sessionid' => $session3->id]));
        $this->assertEquals(3, $DB->count_records('facetoface_notification_hist', ['userid' => $student2->id]));

        $this->assertFalse($DB->record_exists('facetoface_signups_status', ['signupid' => $signups[11]->id]));
        $this->assertFalse($DB->record_exists('facetoface_signups_status', ['signupid' => $signups[12]->id]));
        $this->assertEquals(1 + $additionalcancellation, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[13]->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[21]->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[22]->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[23]->id]));

        $this->assert_count_customfield_data('signup', [$signups[11]->id], 0, 0);
        $this->assert_count_customfield_data('signup', [$signups[12]->id], 0, 0);
        $this->assert_count_customfield_data('signup', [$signups[13]->id], 4, 5);
        $this->assert_count_customfield_data('signup', [$signups[21]->id], 5, 1);
        $this->assert_count_customfield_data('signup', [$signups[22]->id], 1, 4);
        $this->assert_count_customfield_data('signup', [$signups[23]->id], 5, 2);

        $this->assert_count_customfield_data('cancellation', [$signups[11]->id, $signups[12]->id, $signups[21]->id, $signups[23]->id], 0, 0);
        $this->assert_count_customfield_data('cancellation', [$signups[13]->id], 0, 0);
        $this->assert_count_customfield_data('cancellation', [$signups[22]->id], 4, 1);

        // Files should be purged as well.
        $this->assert_leftover_files($student1->id, []);
        $this->assert_leftover_files($student2->id, ['testfile4.txt', 'testfile5.txt']);
    }

    /**
     * Test purging of signups in category context.
     *
     * @param string $userstatus
     * @dataProvider user_status_data_provider
     */
    public function test_purge_in_category_context(string $userstatus) {
        global $DB;
        $this->setAdminUser();

        $datagenerator = $this->getDataGenerator();
        /** @var \mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $datagenerator->get_plugin_generator('mod_facetoface');

        $category1 = $datagenerator->create_category();
        $category2 = $datagenerator->create_category();
        $course1 = $datagenerator->create_course(['category' => $category1->id]);
        $course2 = $datagenerator->create_course(['category' => $category2->id]);
        $course3 = $datagenerator->create_course(['category' => $category2->id]);

        $student1 = $datagenerator->create_user();
        $student2 = $datagenerator->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course3->id);

        $session1 = $f2fgenerator->create_session_for_course($course1);
        $session2 = $f2fgenerator->create_session_for_course($course2, 2);
        $session3 = $f2fgenerator->create_session_for_course($course3, 3);

        $signups = [];
        $signups[11] = $f2fgenerator->create_signup($student1, $session1);
        $signups[12] = $f2fgenerator->create_signup($student1, $session2);
        $signups[13] = $f2fgenerator->create_signup($student1, $session3);
        $signups[21] = $f2fgenerator->create_signup($student2, $session1);
        $signups[22] = $f2fgenerator->create_signup($student2, $session2);
        $signups[23] = $f2fgenerator->create_signup($student2, $session3);

        $emailsink = $this->redirectMessages();
        $this->execute_adhoc_tasks();
        $emailsink->close();

        $signupcustomfieldids = [];
        $signupcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'signup', 3, 1);
        $signupcustomfieldids[12] = $f2fgenerator->create_customfield_data($signups[12], 'signup', 1, 3);
        $signupcustomfieldids[13] = $f2fgenerator->create_customfield_data($signups[13], 'signup', 4, 5);
        $signupcustomfieldids[21] = $f2fgenerator->create_customfield_data($signups[21], 'signup', 4, 1);
        $signupcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'signup', 1, 4);
        $signupcustomfieldids[23] = $f2fgenerator->create_customfield_data($signups[23], 'signup', 5, 2);

        $f2fgenerator->create_cancellation($student1, $session2);
        $f2fgenerator->create_cancellation($student2, $session2);

        $cancellationcustomfieldids = [];
        $cancellationcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[12], 'cancellation', 1, 3);
        $cancellationcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'cancellation', 3, 1);

        $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile1.txt', 1);
        $f2fgenerator->create_file_customfield($signups[12], 'signup', 'testfile2.txt', 2);
        $f2fgenerator->create_file_customfield($signups[12], 'cancellation', 'testfile3.txt', 3);
        $f2fgenerator->create_file_customfield($signups[21], 'signup', 'testfile4.txt', 4);
        $f2fgenerator->create_file_customfield($signups[22], 'cancellation', 'testfile5.txt', 5);

        $student1 = $this->handle_user_status($student1, $userstatus);

        $this->execute_adhoc_tasks();

        // Purge data in category context.
        $targetuser = new target_user($student1);
        $sink = $this->redirectEvents();
        $status = signups::execute_purge($targetuser, context_coursecat::instance($course2->category));
        $events = $sink->get_events();
        $sink->close();

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        $this->assert_signup_status_updated_event($targetuser, $events);

        // For deleted/suspended the active signups get cancelled, so we expect additional records in signups_status table.
        // Also, we expect the additional default cancellation customfield.
        $additionalcancellation = in_array($userstatus, ['deleted', 'suspended']) ? 1 : 0;

        // Verify expected data. Only student1/session2&session3 data should be purged.
        $this->assertEquals(1, $DB->count_records('facetoface_signups', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_signups', ['userid' => $student1->id, 'sessionid' => $session1->id]));
        $this->assertEquals(3, $DB->count_records('facetoface_signups', ['userid' => $student2->id]));

        $this->assertEquals(1, $DB->count_records('facetoface_notification_sent', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_notification_sent', ['userid' => $student1->id, 'sessionid' => $session1->id]));
        $this->assertEquals(3, $DB->count_records('facetoface_notification_sent', ['userid' => $student2->id]));

        $this->assertEquals(1, $DB->count_records('facetoface_notification_hist', ['userid' => $student1->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_notification_hist', ['userid' => $student1->id, 'sessionid' => $session1->id]));
        $this->assertEquals(3, $DB->count_records('facetoface_notification_hist', ['userid' => $student2->id]));

        $this->assertFalse($DB->record_exists('facetoface_signups_status', ['signupid' => $signups[12]->id]));
        $this->assertFalse($DB->record_exists('facetoface_signups_status', ['signupid' => $signups[13]->id]));
        $this->assertEquals(1 + $additionalcancellation, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[11]->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[21]->id]));
        $this->assertEquals(2, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[22]->id]));
        $this->assertEquals(1, $DB->count_records('facetoface_signups_status', ['signupid' => $signups[23]->id]));

        $this->assert_count_customfield_data('signup', [$signups[11]->id], 4, 1);
        $this->assert_count_customfield_data('signup', [$signups[12]->id], 0, 0);
        $this->assert_count_customfield_data('signup', [$signups[13]->id], 0, 0);
        $this->assert_count_customfield_data('signup', [$signups[21]->id], 5, 1);
        $this->assert_count_customfield_data('signup', [$signups[22]->id], 1, 4);
        $this->assert_count_customfield_data('signup', [$signups[23]->id], 5, 2);

        $this->assert_count_customfield_data('cancellation', [$signups[12]->id, $signups[13]->id, $signups[21]->id, $signups[23]->id], 0, 0);
        $this->assert_count_customfield_data('cancellation', [$signups[11]->id], 0, 0);
        $this->assert_count_customfield_data('cancellation', [$signups[22]->id], 4, 1);

        // Files should be purged as well.
        $this->assert_leftover_files($student1->id, ['testfile1.txt']);
        $this->assert_leftover_files($student2->id, ['testfile4.txt', 'testfile5.txt']);
    }

    /**
     * Helper method to change user status from active to the given status.
     *
     * @param stdClass $student
     * @param string $userstatus
     * @return stdClass
     */
    private function handle_user_status(stdClass $student, string $userstatus): stdClass {
        global $DB;

        if ($userstatus == 'deleted') {
            // Suppress DEV debug message caused by different f2f event handlers trying to repeatedly cancel signups (user_deleted, user_unenrolled).
            set_debugging(DEBUG_NORMAL);
            delete_user($student);
            $usercontextid = null;
            $student = $DB->get_record('user', ['id' => $student->id]);
        } else if ($userstatus == 'suspended') {
            set_debugging(DEBUG_NORMAL);
            $student = $DB->get_record('user', ['id' => $student->id]);
            $student->suspended = 1;
            user_update_user($student, false);
            user_suspended::create_from_user($student)->trigger();
        }
        return $student;
    }

    /**
     * Helper method to check if signup_status_updated event was/wasn't fired.
     *
     * @param target_user $targetuser
     * @param array $events
     */
    private function assert_signup_status_updated_event(target_user $targetuser, array $events) {
        // Check that signup cancelling was called correctly (triggers event signup_status_updated).
        // For deleted and suspended users this doesn't trigger an event because it already happened at deletion/suspension time.
        if (!$targetuser->deleted && !$targetuser->suspended) {
            $this->assertCount(2, $events);
            $this->assertInstanceOf('\mod_facetoface\event\signup_status_updated', $events[0]);
            $this->assertInstanceOf('\mod_facetoface\event\booking_cancelled', $events[1]);
            $event = $events[0];
                $event_data = $event->get_data();
            $this->assertEquals($targetuser->id, $event_data['other']['userid']);
        } else {
            $this->assertEmpty($events);
        }
    }

    /**
     * Helper method to check that only the expected files are still there for this user.
     * This means that if an empty array is passed it will be asserted that there is no file for this user.
     *
     * @param int $userid
     * @param array $expectedfilenames
     */
    private function assert_leftover_files(int $userid, array $expectedfilenames) {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            context_system::instance()->id,
            'totara_customfield',
            ['facetofacesignup_filemgr', 'facetofacecancellation_filemgr'],
            false, 'filename ASC', false, 0,
            $userid
        );
        $this->assertCount(count($expectedfilenames), $files);
        foreach ($files as $file) {
            $this->assertContains($file->get_filename(), $expectedfilenames);
        }
    }

    /**
     * Test counting of signups.
     */
    public function test_count() {
        $datagenerator = $this->getDataGenerator();
        /** @var \mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $datagenerator->get_plugin_generator('mod_facetoface');

        $category1 = $datagenerator->create_category();
        $category2 = $datagenerator->create_category();

        $course1 = $datagenerator->create_course(['category' => $category1->id]);
        $course2 = $datagenerator->create_course(['category' => $category2->id]);
        $course3 = $datagenerator->create_course(['category' => $category2->id]);

        $student1 = $datagenerator->create_user();
        $student2 = $datagenerator->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course3->id);

        $session1 = $f2fgenerator->create_session_for_course($course1);
        $session2 = $f2fgenerator->create_session_for_course($course1, 2);
        $session3 = $f2fgenerator->create_session_for_course($course2, 3);
        $session4 = $f2fgenerator->create_session_for_course($course3, 4);

        $f2fgenerator->create_signup($student1, $session1);
        $f2fgenerator->create_signup($student1, $session2);
        $f2fgenerator->create_signup($student1, $session3);
        $f2fgenerator->create_signup($student1, $session4);
        $f2fgenerator->create_signup($student2, $session1);
        $f2fgenerator->create_signup($student2, $session2);
        $f2fgenerator->create_signup($student2, $session3);

        $emailsink = $this->redirectMessages();
        $this->execute_adhoc_tasks();
        $emailsink->close();

        $targetuser1 = new target_user($student1);
        $targetuser2 = new target_user($student2);

        // System context.
        $this->assertEquals(4, signups::execute_count($targetuser1, context_system::instance()));
        $this->assertEquals(3, signups::execute_count($targetuser2, context_system::instance()));

        // Course context.
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);
        $coursecontext3 = context_course::instance($course3->id);
        $this->assertEquals(2, signups::execute_count($targetuser1, $coursecontext1));
        $this->assertEquals(2, signups::execute_count($targetuser2, $coursecontext1));
        $this->assertEquals(1, signups::execute_count($targetuser1, $coursecontext2));
        $this->assertEquals(1, signups::execute_count($targetuser2, $coursecontext2));
        $this->assertEquals(1, signups::execute_count($targetuser1, $coursecontext3));
        $this->assertEquals(0, signups::execute_count($targetuser2, $coursecontext3));

        // Category context.
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $this->assertEquals(2, signups::execute_count($targetuser1, $categorycontext1));
        $this->assertEquals(2, signups::execute_count($targetuser2, $categorycontext1));
        $this->assertEquals(2, signups::execute_count($targetuser1, $categorycontext2));
        $this->assertEquals(1, signups::execute_count($targetuser2, $categorycontext2));

        // Module context.
        $coursemodule3 = get_coursemodule_from_instance('facetoface', $session3->facetoface);
        $coursemodule4 = get_coursemodule_from_instance('facetoface', $session4->facetoface);
        $modulecontext3 = context_module::instance($coursemodule3->id);
        $modulecontext4 = context_module::instance($coursemodule4->id);
        $this->assertEquals(1, signups::execute_count($targetuser1, $modulecontext3));
        $this->assertEquals(1, signups::execute_count($targetuser2, $modulecontext3));
        $this->assertEquals(1, signups::execute_count($targetuser1, $modulecontext4));
        $this->assertEquals(0, signups::execute_count($targetuser2, $modulecontext4));
    }

    /**
     * Test export of signups.
     */
    public function test_export() {
        $datagenerator = $this->getDataGenerator();
        /** @var \mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $datagenerator->get_plugin_generator('mod_facetoface');

        $category1 = $datagenerator->create_category();
        $category2 = $datagenerator->create_category();

        $course1 = $datagenerator->create_course(['category' => $category1->id]);
        $course2 = $datagenerator->create_course(['category' => $category2->id]);
        $course3 = $datagenerator->create_course(['category' => $category2->id]);

        $student1 = $datagenerator->create_user();
        $student2 = $datagenerator->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course3->id);

        $session1 = $f2fgenerator->create_session_for_course($course1);
        $session2 = $f2fgenerator->create_session_for_course($course1, 2);
        $session3 = $f2fgenerator->create_session_for_course($course2, 3);
        $session4 = $f2fgenerator->create_session_for_course($course3, 4);

        $f2fgenerator->create_signup($student1, $session1);
        $f2fgenerator->create_signup($student1, $session2);
        $f2fgenerator->create_signup($student1, $session3);
        $f2fgenerator->create_signup($student1, $session4);
        $f2fgenerator->create_signup($student2, $session1);
        $f2fgenerator->create_signup($student2, $session2);
        $f2fgenerator->create_signup($student2, $session3);

        $emailsink = $this->redirectMessages();
        $this->execute_adhoc_tasks();
        $emailsink->close();

        $targetuser1 = new target_user($student1);
        $targetuser2 = new target_user($student2);

        // System context.
        $this->assert_export_data($targetuser1, context_system::instance(), [$session1->id, $session2->id, $session3->id, $session4->id]);
        $this->assert_export_data($targetuser2, context_system::instance(), [$session1->id, $session2->id, $session3->id]);

        // Course context.
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);
        $coursecontext3 = context_course::instance($course3->id);
        $this->assert_export_data($targetuser1, $coursecontext1, [$session1->id, $session2->id]);
        $this->assert_export_data($targetuser1, $coursecontext2, [$session3->id]);
        $this->assert_export_data($targetuser1, $coursecontext3, [$session4->id]);
        $this->assert_export_data($targetuser2, $coursecontext1, [$session1->id, $session2->id]);
        $this->assert_export_data($targetuser2, $coursecontext2, [$session3->id]);
        $this->assert_export_data($targetuser2, $coursecontext3, []);

        // Category context.
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $this->assert_export_data($targetuser1, $categorycontext1, [$session1->id, $session2->id]);
        $this->assert_export_data($targetuser1, $categorycontext2, [$session3->id, $session4->id]);
        $this->assert_export_data($targetuser2, $categorycontext1, [$session1->id, $session2->id]);
        $this->assert_export_data($targetuser2, $categorycontext2, [$session3->id]);

        // Module context.
        $coursemodule3 = get_coursemodule_from_instance('facetoface', $session3->facetoface);
        $coursemodule4 = get_coursemodule_from_instance('facetoface', $session4->facetoface);
        $modulecontext3 = context_module::instance($coursemodule3->id);
        $modulecontext4 = context_module::instance($coursemodule4->id);
        $this->assert_export_data($targetuser1, $modulecontext3, [$session3->id]);
        $this->assert_export_data($targetuser1, $modulecontext4, [$session4->id]);
        $this->assert_export_data($targetuser2, $modulecontext3, [$session3->id]);
        $this->assert_export_data($targetuser2, $modulecontext4, []);
    }

    /**
     * Execute export and assert expected data.
     *
     * @param target_user $targetuser
     * @param context $context
     * @param array $expectedsessionids
     */
    private function assert_export_data(target_user $targetuser, context $context, array $expectedsessionids) {
        $this->execute_adhoc_tasks();

        $export = signups::execute_export($targetuser, $context);
        $data = $export->data;
        $this->assertCount(count($expectedsessionids), $data);
        $datasessionids = [];
        foreach ($data as $signup) {
            $this->assertObjectHasAttribute('id', $signup);
            $this->assertObjectHasAttribute('userid', $signup);
            $this->assertObjectHasAttribute('sessionid', $signup);
            $this->assertObjectHasAttribute('discountcode', $signup);
            $this->assertObjectHasAttribute('notificationtype', $signup);
            $this->assertObjectHasAttribute('archived', $signup);
            $this->assertObjectHasAttribute('bookedby', $signup);
            $this->assertObjectHasAttribute('managerid', $signup);
            $this->assertObjectHasAttribute('jobassignmentid', $signup);

            $this->assertEquals($targetuser->id, $signup->userid);
            $datasessionids[] = $signup->sessionid;
        }
        $this->assertCount(count($expectedsessionids), array_intersect($expectedsessionids, $datasessionids));
    }
}
