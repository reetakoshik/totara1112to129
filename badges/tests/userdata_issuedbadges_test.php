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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_badges
 */

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use core_badges\userdata\issuedbadges;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the issued badges userdata.
 *
 * @group totara_userdata
 */
class core_badges_userdata_issuedbadges_testcase extends advanced_testcase {

    /**
     * This prepares a complete dataset for test coverage that ensures we test in all situations.
     * @return array
     */
    private function prepare_test_data() {
        global $CFG;

        require_once($CFG->dirroot . '/lib/badgeslib.php');

        $generator = $this->getDataGenerator();
        /** @var \core_badges_generator $badgegenerator */
        $badgegenerator = $generator->get_plugin_generator('core_badges');

        $admin = get_admin();
        $user1 = $generator->create_user(['username' => 'test1']);
        $user2 = $generator->create_user(['username' => 'test2']);

        $category1 = $generator->create_category();
        $course1 = $generator->create_course(['category' => $category1->id]);
        $course2 = $generator->create_course(['category' => $category1->id]);

        $category2 = $generator->create_category();
        $course3 = $generator->create_course(['category' => $category2->id]);

        $badge1id = $badgegenerator->create_badge(get_admin()->id, ['name' => 'Site badge 1: active', 'status' => BADGE_STATUS_ACTIVE]);
        $badge2id = $badgegenerator->create_badge(get_admin()->id, ['name' => 'Site badge 2: inactive', 'status' => BADGE_STATUS_ACTIVE]);

        $badge3id = $badgegenerator->create_badge(get_admin()->id, [
            'name' => 'Course badge 1: active',
            'courseid' => $course1->id,
            'status' => BADGE_STATUS_ACTIVE,
            'type' => BADGE_TYPE_COURSE
        ]);
        $badge4id = $badgegenerator->create_badge(get_admin()->id, [
            'name' => 'Course badge 2: inactive',
            'courseid' => $course1->id,
            'status' => BADGE_STATUS_ACTIVE,
            'type' => BADGE_TYPE_COURSE
        ]);

        $badge5id = $badgegenerator->create_badge(get_admin()->id, [
            'name' => 'Course badge 3: active',
            'courseid' => $course2->id,
            'status' => BADGE_STATUS_ACTIVE,
            'type' => BADGE_TYPE_COURSE
        ]);
        $badge6id = $badgegenerator->create_badge(get_admin()->id, [
            'name' => 'Course badge 4: inactive',
            'courseid' => $course2->id,
            'status' => BADGE_STATUS_ACTIVE,
            'type' => BADGE_TYPE_COURSE
        ]);

        $badge7id = $badgegenerator->create_badge(get_admin()->id, [
            'name' => 'Course badge 5: active',
            'courseid' => $course3->id,
            'status' => BADGE_STATUS_ACTIVE,
            'type' => BADGE_TYPE_COURSE
        ]);

        $badgegenerator->add_manual_badge_criteria($badge1id);
        $badgegenerator->add_manual_badge_criteria($badge2id);
        $badgegenerator->add_manual_badge_criteria($badge3id);
        $badgegenerator->add_manual_badge_criteria($badge4id);
        $badgegenerator->add_manual_badge_criteria($badge5id);
        $badgegenerator->add_manual_badge_criteria($badge6id);
        $badgegenerator->add_manual_badge_criteria($badge7id);

        $badgegenerator->issue_badge_manually($user1, $admin, $badge1id);
        $badgegenerator->issue_badge_manually($user1, $admin, $badge2id);
        $badgegenerator->issue_badge_manually($user1, $admin, $badge3id);
        $badgegenerator->issue_badge_manually($user1, $admin, $badge4id);
        $badgegenerator->issue_badge_manually($user1, $admin, $badge5id);
        $badgegenerator->issue_badge_manually($user1, $admin, $badge6id);
        $badgegenerator->issue_badge_manually($user1, $admin, $badge7id);

        $badgegenerator->issue_badge_manually($user2, $admin, $badge1id);
        $badgegenerator->issue_badge_manually($user2, $admin, $badge2id);
        $badgegenerator->issue_badge_manually($user2, $admin, $badge3id);
        $badgegenerator->issue_badge_manually($user2, $admin, $badge4id);
        $badgegenerator->issue_badge_manually($user2, $admin, $badge5id);
        $badgegenerator->issue_badge_manually($user2, $admin, $badge6id);
        $badgegenerator->issue_badge_manually($user2, $admin, $badge7id);

        $badge = new \badge($badge2id);
        $badge->set_status(BADGE_STATUS_INACTIVE);

        $badge = new \badge($badge4id);
        $badge->set_status(BADGE_STATUS_INACTIVE);

        $badge = new \badge($badge6id);
        $badge->set_status(BADGE_STATUS_INACTIVE);

        return [
            'users' => [$user1, $user2],
            'categories' => [$category1, $category2],
            'courses' => [$course1, $course2, $course3],
            'badgeids' => [$badge1id, $badge2id, $badge3id, $badge4id, $badge5id, $badge6id]
        ];
    }

    /**
     * Test issuebadges is purgeable in all statuses.
     */
    public function test_is_purgeable() {
        self::assertTrue(issuedbadges::is_purgeable(target_user::STATUS_ACTIVE));
        self::assertTrue(issuedbadges::is_purgeable(target_user::STATUS_DELETED));
        self::assertTrue(issuedbadges::is_purgeable(target_user::STATUS_SUSPENDED));
    }

    /**
     * Test checking context levels are compatible.
     */
    public function test_is_compatible_context() {
        self::assertTrue(issuedbadges::is_compatible_context_level(CONTEXT_SYSTEM));
        self::assertTrue(issuedbadges::is_compatible_context_level(CONTEXT_COURSECAT));
        self::assertTrue(issuedbadges::is_compatible_context_level(CONTEXT_COURSE));
        self::assertFalse(issuedbadges::is_compatible_context_level(CONTEXT_USER));
        self::assertFalse(issuedbadges::is_compatible_context_level(CONTEXT_MODULE));
        self::assertFalse(issuedbadges::is_compatible_context_level(CONTEXT_BLOCK));
        self::assertFalse(issuedbadges::is_compatible_context_level(CONTEXT_PROGRAM));
    }

    /**
     * Test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
        $this->assertEquals($expectedcontextlevels, issuedbadges::get_compatible_context_levels());
    }

    /**
     * Test counting badges issued in the system context.
     *
     */
    public function test_count_system_context() {
        $this->resetAfterTest();

        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);

        self::assertSame(7, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(7, issuedbadges::execute_count($user2, \context_system::instance()));
    }

    /**
     * Test purging badges in the system context.
     */
    public function test_purge_system_context() {
        $this->resetAfterTest();

        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);

        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        self::assertSame(7, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(7, issuedbadges::execute_count($user2, \context_system::instance()));

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user1, \context_system::instance()));

        self::assertSame(7, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(7, issuedbadges::execute_count($user2, \context_system::instance()));

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user2, \context_system::instance()));

        self::assertSame(7, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(0, issuedbadges::execute_count($user2, \context_system::instance()));
    }

    /**
     * Test purging badges in the system context for suspended users
     */
    public function test_purge_system_context_suspended_users() {
        $this->resetAfterTest();

        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);

        self::assertSame(7, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(7, issuedbadges::execute_count($user2, \context_system::instance()));

        $user1 = new target_user($this->delete_user_for_testing($user1->id));
        $user2 = new target_user($this->delete_user_for_testing($user2->id));

        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user1, \context_system::instance()));

        // No events for deleted users
        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(7, issuedbadges::execute_count($user2, \context_system::instance()));

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user2, \context_system::instance()));

        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(0, issuedbadges::execute_count($user2, \context_system::instance()));
    }

    /**
     * Test purging badges in the system context for deleted users
     */
    public function test_purge_system_context_deleted_users() {
        $this->resetAfterTest();

        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);

        self::assertSame(7, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(7, issuedbadges::execute_count($user2, \context_system::instance()));

        $user1 = new target_user($this->delete_user_for_testing($user1->id));
        $user2 = new target_user($this->delete_user_for_testing($user2->id));

        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user1, \context_system::instance()));

        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(7, issuedbadges::execute_count($user2, \context_system::instance()));

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user2, \context_system::instance()));

        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(0, issuedbadges::execute_count($user2, \context_system::instance()));
    }

    /**
     * Test counting badges issues in coursecat contexts.
     */
    public function test_count_coursecat_context() {

        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);
        $context = \context_coursecat::instance($data['categories'][0]->id);

        self::assertSame(4, issuedbadges::execute_count($user1, $context));
        self::assertSame(4, issuedbadges::execute_count($user2, $context));
    }

    /**
     * context data provide
     *
     * @return array
     */
    public function provider_coursecat_context() {
        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);
        $context = \context_coursecat::instance($data['categories'][0]->id);

        return $data;
    }

    public function test_purge_coursecat_context() {
        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);
        $context = \context_coursecat::instance($data['categories'][0]->id);

        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        self::assertSame(4, issuedbadges::execute_count($user1, $context));
        self::assertSame(4, issuedbadges::execute_count($user2, $context));

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user1, $context));

        self::assertSame(4, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, $context));
        self::assertSame(4, issuedbadges::execute_count($user2, $context));

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user2, $context));

        self::assertSame(4, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, $context));
        self::assertSame(0, issuedbadges::execute_count($user2, $context));
        self::assertSame(3, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(3, issuedbadges::execute_count($user2, \context_system::instance()));
    }

    public function test_purge_course_context() {
        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);
        $context = \context_course::instance($data['courses'][0]->id);

        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        self::assertSame(0, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        self::assertSame(2, issuedbadges::execute_count($user1, $context));
        self::assertSame(2, issuedbadges::execute_count($user2, $context));

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user1, $context));

        self::assertSame(2, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, $context));
        self::assertSame(2, issuedbadges::execute_count($user2, $context));

        self::assertSame(item::RESULT_STATUS_SUCCESS, issuedbadges::execute_purge($user2, $context));

        self::assertSame(2, $eventsink->count());
        self::assertSame(0, $messagesink->count());

        $events = $eventsink->get_events();
        foreach ($events as $event) {
            self::assertInstanceOf('\core\event\badge_revoked', $event);
        }
        $eventsink->clear();

        self::assertSame(0, issuedbadges::execute_count($user1, $context));
        self::assertSame(0, issuedbadges::execute_count($user2, $context));
        self::assertSame(5, issuedbadges::execute_count($user1, \context_system::instance()));
        self::assertSame(5, issuedbadges::execute_count($user2, \context_system::instance()));
    }

    /**
     * Test exporting issued badges.
     */
    public function test_export() {
        $this->resetAfterTest();
        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);
        $context_system = \context_system::instance();
        $context_cat1 = \context_coursecat::instance($data['categories'][0]->id);
        $context_cat2 = \context_coursecat::instance($data['categories'][1]->id);
        $context_course1 = \context_course::instance($data['courses'][0]->id);
        $context_course2 = \context_course::instance($data['courses'][1]->id);
        $context_course3 = \context_course::instance($data['courses'][2]->id);

        // Test system context, expecting all 7.
        $result = issuedbadges::execute_export($user1, $context_system);
        self::assertCount(7, $result->data);
        self::assertCount(7, $result->files);

        $expectedbadges = [
            'Site badge 1: active',
            'Site badge 2: inactive',
            'Course badge 1: active',
            'Course badge 2: inactive',
            'Course badge 3: active',
            'Course badge 4: inactive',
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test category 1, expecting course badges 1-4
        $result = issuedbadges::execute_export($user1, $context_cat1);
        self::assertCount(4, $result->data);
        self::assertCount(4, $result->files);

        $expectedbadges = [
            'Course badge 1: active',
            'Course badge 2: inactive',
            'Course badge 3: active',
            'Course badge 4: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test category 2, expecting course badge 5 only
        $result = issuedbadges::execute_export($user1, $context_cat2);
        self::assertCount(1, $result->data);
        self::assertCount(1, $result->files);

        $expectedbadges = [
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 1, expecting course badges 1, and 2
        $result = issuedbadges::execute_export($user1, $context_course1);
        self::assertCount(2, $result->data);
        self::assertCount(2, $result->files);

        $expectedbadges = [
            'Course badge 1: active',
            'Course badge 2: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 2, expecting course badges 3, and 4
        $result = issuedbadges::execute_export($user1, $context_course2);
        self::assertCount(2, $result->data);
        self::assertCount(2, $result->files);

        $expectedbadges = [
            'Course badge 3: active',
            'Course badge 4: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 3, expecting course badge 5 only
        $result = issuedbadges::execute_export($user1, $context_course3);
        self::assertCount(1, $result->data);
        self::assertCount(1, $result->files);

        $expectedbadges = [
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        $badgearray = (array)$badge;
        $expectedkeys = ['badgeid', 'courseid', 'dateissued', 'dateexpire', 'name', 'description', 'issuername', 'issuerurl', 'issuercontact', 'file'];
        $actualkeys = array_keys($badgearray);
        sort($expectedkeys);
        sort($actualkeys);
        self::assertSame($expectedkeys, $actualkeys);
    }

    /**
     * Test exporting issued badges to suspended users.
     */
    public function test_export_suspended_users() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);
        $context_system = \context_system::instance();
        $context_cat1 = \context_coursecat::instance($data['categories'][0]->id);
        $context_cat2 = \context_coursecat::instance($data['categories'][1]->id);
        $context_course1 = \context_course::instance($data['courses'][0]->id);
        $context_course2 = \context_course::instance($data['courses'][1]->id);
        $context_course3 = \context_course::instance($data['courses'][2]->id);

        $user1 = new target_user($this->suspend_user_for_testing($user1->id));

        // Test system context, expecting all 7.
        $result = issuedbadges::execute_export($user1, $context_system);
        self::assertCount(7, $result->data);
        self::assertCount(7, $result->files);

        $sitebadge = null;
        $coursebadge = null;
        $expectedbadges = [
            'Site badge 1: active',
            'Site badge 2: inactive',
            'Course badge 1: active',
            'Course badge 2: inactive',
            'Course badge 3: active',
            'Course badge 4: inactive',
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
            if ($badge->name === 'Site badge 1: active') {
                $sitebadge = $badge;
            }
            if ($badge->name === 'Course badge 1: active') {
                $coursebadge = $badge;
            }
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Verify a site badge.
        self::assertInstanceOf('stdClass', $sitebadge);
        self::assertSame('Site badge 1: active', $sitebadge->name);
        self::assertSame('Test issuer', $sitebadge->issuername);
        self::assertNull($sitebadge->courseid);
        $dbbadge = $DB->get_record('badge_issued', ['badgeid' => $sitebadge->badgeid, 'userid' => $user1->id], '*', MUST_EXIST);
        $usercontext = \context_user::instance($user1->id);
        $fs = get_file_storage();
        $file = $fs->get_file($usercontext->id, 'badges', 'userbadge', $sitebadge->badgeid, '/', $dbbadge->uniquehash . '.png');
        self::assertInstanceOf('stdClass', $sitebadge->file);
        self::assertSame($dbbadge->uniquehash . '.png', $sitebadge->file->filename);
        self::assertSame($file->get_contenthash(), $sitebadge->file->contenthash);

        // Verify a course badge.
        self::assertInstanceOf('stdClass', $coursebadge);
        self::assertSame('Course badge 1: active', $coursebadge->name);
        self::assertSame('Test issuer', $coursebadge->issuername);
        self::assertNotNull($coursebadge->courseid);
        $dbbadge = $DB->get_record('badge_issued', ['badgeid' => $coursebadge->badgeid, 'userid' => $user1->id], '*', MUST_EXIST);
        $usercontext = \context_user::instance($user1->id);
        $fs = get_file_storage();
        $file = $fs->get_file($usercontext->id, 'badges', 'userbadge', $coursebadge->badgeid, '/', $dbbadge->uniquehash . '.png');
        self::assertInstanceOf('stdClass', $coursebadge->file);
        self::assertSame($dbbadge->uniquehash . '.png', $coursebadge->file->filename);
        self::assertSame($file->get_contenthash(), $coursebadge->file->contenthash);

        // Test category 1, expecting course badges 1-4
        $result = issuedbadges::execute_export($user1, $context_cat1);
        self::assertCount(4, $result->data);
        self::assertCount(4, $result->files);

        $expectedbadges = [
            'Course badge 1: active',
            'Course badge 2: inactive',
            'Course badge 3: active',
            'Course badge 4: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test category 2, expecting course badge 5 only
        $result = issuedbadges::execute_export($user1, $context_cat2);
        self::assertCount(1, $result->data);
        self::assertCount(1, $result->files);

        $expectedbadges = [
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 1, expecting course badges 1, and 2
        $result = issuedbadges::execute_export($user1, $context_course1);
        self::assertCount(2, $result->data);
        self::assertCount(2, $result->files);

        $expectedbadges = [
            'Course badge 1: active',
            'Course badge 2: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 2, expecting course badges 3, and 4
        $result = issuedbadges::execute_export($user1, $context_course2);
        self::assertCount(2, $result->data);
        self::assertCount(2, $result->files);

        $expectedbadges = [
            'Course badge 3: active',
            'Course badge 4: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 3, expecting course badge 5 only
        $result = issuedbadges::execute_export($user1, $context_course3);
        self::assertCount(1, $result->data);
        self::assertCount(1, $result->files);

        $expectedbadges = [
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        $badgearray = (array)$badge;
        $expectedkeys = ['badgeid', 'courseid', 'dateissued', 'dateexpire', 'name', 'description', 'issuername', 'issuerurl', 'issuercontact', 'file'];
        $actualkeys = array_keys($badgearray);
        sort($expectedkeys);
        sort($actualkeys);
        self::assertSame($expectedkeys, $actualkeys);
    }

    /**
     * Test exporting issued badges of deleted users
     */
    public function test_export_deleted_users() {
        $this->resetAfterTest();
        $data = $this->prepare_test_data();

        $user1 = new target_user($data['users'][0]);
        $user2 = new target_user($data['users'][1]);
        $context_system = \context_system::instance();
        $context_cat1 = \context_coursecat::instance($data['categories'][0]->id);
        $context_cat2 = \context_coursecat::instance($data['categories'][1]->id);
        $context_course1 = \context_course::instance($data['courses'][0]->id);
        $context_course2 = \context_course::instance($data['courses'][1]->id);
        $context_course3 = \context_course::instance($data['courses'][2]->id);

        $user1 = new target_user($this->delete_user_for_testing($user1->id));
        $user2 = new target_user($this->delete_user_for_testing($user2->id));

        // Test system context, expecting all 7.
        $result = issuedbadges::execute_export($user1, $context_system);
        self::assertCount(7, $result->data);
        // Note that if the user has been deleted then the files have already been deleted.
        // This is because they are stored in the user context and when that is deleted so are all files.
        self::assertCount(0, $result->files);

        $expectedbadges = [
            'Site badge 1: active',
            'Site badge 2: inactive',
            'Course badge 1: active',
            'Course badge 2: inactive',
            'Course badge 3: active',
            'Course badge 4: inactive',
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test category 1, expecting course badges 1-4
        $result = issuedbadges::execute_export($user1, $context_cat1);
        self::assertCount(4, $result->data);
        self::assertCount(0, $result->files);

        $expectedbadges = [
            'Course badge 1: active',
            'Course badge 2: inactive',
            'Course badge 3: active',
            'Course badge 4: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test category 2, expecting course badge 5 only
        $result = issuedbadges::execute_export($user1, $context_cat2);
        self::assertCount(1, $result->data);
        self::assertCount(0, $result->files);

        $expectedbadges = [
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 1, expecting course badges 1, and 2
        $result = issuedbadges::execute_export($user1, $context_course1);
        self::assertCount(2, $result->data);
        self::assertCount(0, $result->files);

        $expectedbadges = [
            'Course badge 1: active',
            'Course badge 2: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 2, expecting course badges 3, and 4
        $result = issuedbadges::execute_export($user1, $context_course2);
        self::assertCount(2, $result->data);
        self::assertCount(0, $result->files);

        $expectedbadges = [
            'Course badge 3: active',
            'Course badge 4: inactive',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        // Test course 3, expecting course badge 5 only
        $result = issuedbadges::execute_export($user1, $context_course3);
        self::assertCount(1, $result->data);
        self::assertCount(0, $result->files);

        $expectedbadges = [
            'Course badge 5: active',
        ];
        $actualbadges = [];
        foreach ($result->data as $badge) {
            $actualbadges[] = $badge->name;
        }
        sort($expectedbadges);
        sort($actualbadges);
        self::assertEquals($expectedbadges, $actualbadges);

        $badgearray = (array)$badge;
        $expectedkeys = ['badgeid', 'courseid', 'dateissued', 'dateexpire', 'name', 'description', 'issuername', 'issuerurl', 'issuercontact', 'file'];
        $actualkeys = array_keys($badgearray);
        sort($expectedkeys);
        sort($actualkeys);
        self::assertSame($expectedkeys, $actualkeys);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function suspend_user_for_testing($userid) {
        global $DB;
        $user = $DB->get_record('user', ['id' => $userid]);
        $user->suspended = 1;
        // No need to end user sessions. DO NOT COPY THIS TO PRODUCTION CODE!
        user_update_user($user, false);
        \totara_core\event\user_suspended::create_from_user($user)->trigger();
        return $DB->get_record('user', ['id' => $user->id]);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function delete_user_for_testing($userid) {
        global $DB;
        user_delete_user($DB->get_record('user', ['id' => $userid]));
        return $DB->get_record('user', ['id' => $userid]);
    }
}