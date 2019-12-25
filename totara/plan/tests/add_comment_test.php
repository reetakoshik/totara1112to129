<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot."/totara/plan/lib.php");

/**
 * A unit test for checking whether the email is
 * sending or not base on the user's preference
 * when a new comment is added to their plan
 */
class totara_plan_add_comment_testcase extends advanced_testcase {

    /** @var totara_plan_generator $plangenerator */
    private $plangenerator = null;

    /** @var testing_data_generator $datagenerator */
    private $datagenerator = null;

    protected function setUp() {
        parent::setup();

        $this->resetAfterTest();

        $this->datagenerator = $this->getDataGenerator();
        $this->plangenerator = $this->datagenerator->get_plugin_generator('totara_plan');
    }

    protected function tearDown() {
        $this->datagenerator = null;
        $this->plangenerator = null;
        parent::tearDown();
    }

    /**
     * @param stdClass  $user
     * @param bool      $issendingemail True if user should be subscribed to an email
     */
    private function prepare_user_preferences(\stdClass $user, $issendingemail = false): void {
        global $DB;

        $value = $issendingemail ? "email" : "none";
        $DB->insert_record("user_preferences", (object)[
            'userid' => $user->id,
            'name' => \totara_plan\add_comment_helper::COMPETENCY_PLAN_COMMENT_LOGGEDIN,
            'value' => $value
        ]);

        $DB->insert_record("user_preferences", (object)[
            'userid' => $user->id,
            'name' => \totara_plan\add_comment_helper::COMPETENCY_PLAN_COMMENT_LOGGEDOFF,
            'value' => $value
        ]);
    }

    private function create_comment(\stdClass $user, \stdClass $plan, string $content): stdClass {
        global $DB;

        $comment = new \stdClass();
        $comment->commentarea = 'plan_overview';
        $comment->contextid = 1;
        $comment->component = 'totara_plan';
        $comment->content = $content;
        $comment->itemid = $plan->id;
        $comment->userid = $user->id;
        $comment->timecreated = time();
        $DB->insert_record('comments', $comment);

        return $comment;
    }

    public function test_totara_plan_comment_add(): void {
        // Create a plan user.
        $planuser = $this->datagenerator->create_user();
        $this->prepare_user_preferences($planuser);

        // Create a manager user.
        $manager = $this->datagenerator->create_user();
        $managerja = \totara_job\job_assignment::create_default($manager->id);
        $this->prepare_user_preferences($manager, true);

        // Assign manager to the plan user.
        \totara_job\job_assignment::create_default($planuser->id, ['managerjaid' => $managerja->id]);

        // Some other user who should not see the plan.
        $otheruser = $this->datagenerator->create_user();
        $this->prepare_user_preferences($otheruser, true);

        $plan = $this->plangenerator->create_learning_plan(['userid' => $planuser->id]);

        $this->setUser($planuser);
        $sink = $this->redirectEmails();
        $planuser_comment = $this->create_comment($planuser, $plan, 'Comment 1');
        totara_plan_comment_add($planuser_comment);

        $emails = $sink->get_messages();
        $this->assertCount(1, $emails); // Manager gets an email.
        $sink->clear();

        $this->setUser($otheruser);
        $otheruser_comment = $this->create_comment($otheruser, $plan, 'Comment 2');
        totara_plan_comment_add($otheruser_comment);

        $emails = $sink->get_messages();
        $this->assertCount(1, $emails); // Manager gets an email, plan user is unsubscribed.
        $sink->clear();

        $this->setUser($manager);
        $manager_comment = $this->create_comment($manager, $plan, 'Comment 3');
        totara_plan_comment_add($manager_comment);

        $emails = $sink->get_messages();
        $this->assertCount(0, $emails); // No one gets an email (plan user is unsubscribed, other user has no access).
        $sink->clear();

        $this->setUser($planuser);
        $planuser_comment = $this->create_comment($planuser, $plan, 'Comment 4');
        totara_plan_comment_add($planuser_comment);

        $emails = $sink->get_messages();
        $this->assertCount(1, $emails); // Manager gets an email.
        $sink->clear();

        $roleid = $this->datagenerator->create_role();
        role_change_permission($roleid, context_system::instance(), 'totara/plan:accessanyplan', CAP_ALLOW);
        $this->datagenerator->role_assign($roleid, $otheruser->id);

        $this->setUser($planuser);
        $planuser_comment = $this->create_comment($planuser, $plan, 'Comment 5');
        totara_plan_comment_add($planuser_comment);

        $emails = $sink->get_messages();
        $this->assertCount(2, $emails); // Manager gets an email, other user gets an email.

        $sink->close();
    }
}
