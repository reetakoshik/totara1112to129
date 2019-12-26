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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_cohort
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/totara/cohort/lib.php");


class totara_cohort_notify_users_testcase extends advanced_testcase {
    public function test_notify_users_when_cohort_change(): void {
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        /** @var totara_cohort_generator $cohortgen */
        $cohortgen = $gen->get_plugin_generator('totara_cohort');

        $userids = [];
        $cohort = $cohortgen->create_cohort([
            'alertmembers' => COHORT_ALERT_AFFECTED
        ]);

        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $userids[] = $user->id;
            $cohortgen->create_cohort_member([
                'cohortid' => $cohort->id,
                'userid' => $user->id
            ]);
        }

        $sink = phpunit_util::start_message_redirection();
        totara_cohort_notify_users($cohort->id, $userids, 'membersadded');

        $messages = $sink->get_messages();
        $this->assertCount(2, $messages);
    }

    public function test_notify_users_when_remove_members(): void {
        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();

        /** @var totara_cohort_generator $cohortgen */
        $cohortgen = $gen->get_plugin_generator('totara_cohort');

        $userids = [];
        $cohort = $cohortgen->create_cohort([
            'alertmembers' => COHORT_ALERT_ALL,
        ]);

        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $userids[] = $user->id;

            $cohortgen->create_cohort_member([
                'cohortid' => $cohort->id,
                'userid' => $user->id
            ]);
        }

        $sink = phpunit_util::start_message_redirection();
        totara_cohort_notify_users($cohort->id, $userids, 'membersremoved');
        $messages = $sink->get_messages();

        // Because the alert was set to all, and there are 2 members in an audience and there are 2
        // users to be deleted, therefore 4 emails should be sent out, as 2 emails to notify the
        // members of audience, and the other 2 are for users that are about to be removed.
        $this->assertCount(4, $messages);
    }
}