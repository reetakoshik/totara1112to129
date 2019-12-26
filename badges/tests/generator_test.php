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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/lib/badgeslib.php");

/**
 * Tests the badges generator
 */
class core_badges_generator_testcase extends advanced_testcase {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');
    }

    /**
     * Test the badge generator instance is what we expect.
     */
    public function test_generator_instance() {
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        self::assertInstanceOf(core_badges_generator::class, $generator);
    }

    /**
     * Tests creation of badges via the badge generator.
     */
    public function test_create_badge() {
        $this->resetAfterTest();

        /** @var core_badges_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $user = get_admin();

        self::assertCount(0, badges_get_badges(BADGE_TYPE_SITE));
        self::assertCount(0, badges_get_badges(BADGE_TYPE_COURSE));

        $badgeid = $generator->create_badge($user->id);

        self::assertCount(0, badges_get_badges(BADGE_TYPE_COURSE));
        $badges = badges_get_badges(BADGE_TYPE_SITE);
        self::assertCount(1, $badges);
        self::assertArrayHasKey($badgeid, $badges);
        $badge = $badges[$badgeid];
        self::assertEquals($badgeid, $badge->id);
        self::assertEquals($user->id, $badge->usercreated);
        self::assertEquals($user->id, $badge->usermodified);
        self::assertEquals('', $badge->courseid);
        self::assertEquals(BADGE_TYPE_SITE, $badge->type);
    }

    /**
     * Tests creation of badges via the badge generator.
     */
    public function test_create_badge_customised() {
        $this->resetAfterTest();

        /** @var core_badges_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        self::assertCount(0, badges_get_badges(BADGE_TYPE_SITE));
        self::assertCount(0, badges_get_badges(BADGE_TYPE_COURSE));

        $properties = [
            'name' => 'Custom name',
            'description' => 'Custom description',
            'timecreated' => time() - (86400*2),
            'timemodified' => time() - 86400,
            'usercreated' => $admin->id,
            'usermodified' => $user->id,
            'issuername' => 'Example issuer',
            'issuerurl' => 'https://badgeissuer.example.com',
            'issuercontact' => 'user@example.com',
            'expiredate' => time() + 86400,
            'expireperiod' => '86400',
            'type' => BADGE_TYPE_COURSE,
            'courseid' => $course->id,
            'message' => 'I am the message',
            'messagesubject' => 'Message subject',
            'attachment' => '0',
            'notification' => '1',
            'status' => BADGE_STATUS_INACTIVE,
        ];

        $badgeid = $generator->create_badge($user->id, $properties);

        self::assertCount(0, badges_get_badges(BADGE_TYPE_SITE));
        $badges = badges_get_badges(BADGE_TYPE_COURSE);
        self::assertCount(1, $badges);
        self::assertArrayHasKey($badgeid, $badges);
        $badge = $badges[$badgeid];
        foreach ($properties as $key => $value) {
            self::assertEquals($value, $badge->{$key});
        }
    }

    /**
     * Test that we can't provide invalid properties
     */
    public function test_create_badge_invalid() {
        self::expectException(coding_exception::class);
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $user = get_admin();
        $generator->create_badge($user->id, ['titanic' => 'iceberg']);
    }

    /**
     * Tests creation of connectedbackpacks connections via the generator.
     */
    public function test_create_backpack_connection() {
        $this->resetAfterTest();

        /** @var core_badges_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $admin = get_admin();

        self::assertFalse(badges_user_has_backpack($admin->id));

        $generator->create_backpack_connection($admin);

        self::assertTrue(badges_user_has_backpack($admin->id));

        $generator->create_backpack_connection($admin);

        self::assertTrue(badges_user_has_backpack($admin->id));
    }

    /**
     * Tests adding manual criteria to existing badges.
     */
    public function test_add_manual_badge_criteria() {
        $this->resetAfterTest();

        /** @var core_badges_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $user = get_admin();

        $badgeid = $generator->create_badge($user->id);
        $badge = new badge($badgeid);
        self::assertCount(0, $badge->get_criteria());

        $generator->add_manual_badge_criteria($badgeid);

        $criteria = $badge->get_criteria();
        // We expect the overall criteria and the manual criteria.
        self::assertCount(2, $badge->get_criteria());

        $overall = null;
        $manual = null;
        foreach ($criteria as $criterion) {
            if ($overall === null && $criterion instanceof award_criteria_overall) {
                $overall = $criterion;
            } else if ($manual === null && $criterion instanceof award_criteria_manual) {
                $manual = $criterion;
            }
        }
        self::assertNotNull($overall);
        self::assertNotNull($manual);
    }

    /**
     * Tests manually issuing badges.
     */
    public function test_issue_badge_manually() {
        $this->resetAfterTest();

        /** @var core_badges_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        // Create the first badge, we won't issue this.
        $badge1id = $generator->create_badge($user->id);
        // Track this one, we'll issue it.
        $badge2id = $generator->create_badge($user->id);
        $generator->add_manual_badge_criteria($badge2id);

        self::assertCount(0, badges_get_user_badges($user->id));

        $generator->issue_badge_manually($user, $admin, $badge2id);

        $badges = badges_get_user_badges($user->id);
        self::assertCount(1, $badges);
        $badgeids = [];
        foreach ($badges as $badge) {
            $badgeids[$badge->id] = $badge->id;
        }
        self::assertArrayNotHasKey($badge1id, $badgeids);
        self::assertArrayHasKey($badge2id, $badgeids);
    }

    /**
     * Test that badges cannot be issued without manual criteria.
     */
    public function test_issue_badge_without_criteria() {
        $this->resetAfterTest();

        /** @var core_badges_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();
        $badgeid = $generator->create_badge($user->id);

        self::expectException(coding_exception::class);
        $generator->issue_badge_manually($user, $admin, $badgeid);
    }

    /**
     * Test that mocking external badges within the cache passes through the API correctly.
     */
    public function test_mock_external_badges_in_cache() {
        $this->resetAfterTest();

        /** @var core_badges_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $user = $this->getDataGenerator()->create_user();
        $cache = \cache::make('core', 'externalbadges');

        $expected = $generator->mock_external_badges_in_cache($user);
        $actual = $cache->get($user->id);
        self::assertEquals($expected, $actual);

        $actual = get_backpack_settings($user->id);
        self::assertEquals($expected, $actual);
    }

}