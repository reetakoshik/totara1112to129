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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_appraisal
 */
global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

use \totara_job\job_assignment;

/**
 * Tests the sending of activation notifications for dynamic appraisals.
 */
class totara_appraisal_messages_dynamic_test extends appraisal_testcase {
    /**
     * @var stdClass test execution context with these fields:
     *      - [appraisal] appraisal: test appraisal
     *      - [array] appraisee0: result from appraisee_details().
     *      - [array] appraisee1: result from appraisee_details().
     *      - [totara_appraisal_generator] generator: appraisal generator.
     *      - [array[string=>mixed]] restores: system configuration values to
     *        be restored after the test.
     *      - [phpunit_phpmailer_sink] sink: email sink.
     *      - [array] tasks: cron tasks
     */
    private $testenv;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('totara_appraisal');
        $appraisal = $generator->create_appraisal();

        $appraisalid = $appraisal->id;
        $roles = [
            appraisal::ROLE_LEARNER => appraisal::ACCESS_CANANSWER,
            appraisal::ROLE_MANAGER => appraisal::ACCESS_CANANSWER
        ];
        $stage = $generator->create_stage($appraisalid);
        $page = $generator->create_page($stage->id);
        $generator->create_question($page->id, ['roles' => $roles]);
        $generator->create_message($appraisalid, ['roles' => array_keys($roles), 'messageto' => null]);

        $restores = $this->restorable_config_values();
        set_config('dynamicappraisals', true);

        $this->testenv = (object) [
            'appraisal' => $appraisal,
            'appraisee0' => $this->appraisee_details('u0'),
            'appraisee1' => $this->appraisee_details('u1'),
            'generator' => $generator,
            'restores' => $restores,
            'sink' => $this->redirectEmails(),
            'tasks' => [
                new totara_appraisal\task\scheduled_messages(),
                new totara_appraisal\task\update_learner_assignments_task(),
                new totara_appraisal\task\cleanup_task()
            ]
        ];
    }

    public function tearDown(): void {
        $this->testenv->appraisal->close();
        $this->testenv->sink->close();

        foreach ($this->testenv->restores as $key => $value) {
            set_config($key, $value);
        }

        $this->testenv = null;

        parent::tearDown();
    }

    /**
     * Tests appraisal activation with multiple jobs off in this sequence:
     * 1) add users before activation
     * 2) activate appraisal
     * 3) run cron tasks
     * 4) run cron tasks again
     */
    public function test_no_multijob_with_before_no_after(): void {
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        [$appraisee, $manager, $cohort, ] = $this->testenv->appraisee0;
        $this->assign_cohort_step($this->testenv, $cohort);

        // Step #2
        $this->activate_step($this->testenv, []);

        // Step #3
        $recipients = [
            'Learner' => $appraisee->email,
            'Manager' => $manager->email
        ];
        $this->cron_step($this->testenv, $recipients);

        // Step #4
        $this->cron_step($this->testenv, []);
    }

    /**
     * Tests appraisal activation with multiple jobs off in this sequence:
     * 1) add users before activation
     * 2) activate appraisal
     * 3) run cron tasks
     * 4) add users after activation
     * 5) simulate pressing of 'update' button
     * 6) run cron tasks
     */
    public function test_no_multijob_with_before_with_after_with_update(): void {
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        [$appraisee, $manager, $cohort, ] = $this->testenv->appraisee0;
        $this->assign_cohort_step($this->testenv, $cohort);

        // Step #2
        $this->activate_step($this->testenv, []);

        // Step #3
        $recipients = [
            'Learner' => $appraisee->email,
            'Manager' => $manager->email
        ];
        $this->cron_step($this->testenv, $recipients);

        // Step #4
        [$appraisee1, $manager1, $cohort1, ] = $this->testenv->appraisee1;
        $this->assign_cohort_step($this->testenv, $cohort1);

        // Step #5
        $recipients1 = [
            'Learner' => $appraisee1->email,
            'Manager' => $manager1->email
        ];
        $this->update_button_step($this->testenv, $recipients1);

        // Step #6
        $this->cron_step($this->testenv, []);
    }

    /**
     * Tests appraisal activation with multiple jobs off in this sequence:
     * 1) add users before activation
     * 2) activate appraisal
     * 3) run cron tasks
     * 4) add users after activation, NO "pressing" of update button
     * 5) run cron tasks
     */
    public function test_no_multijob_with_before_with_after_no_update(): void {
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        [$appraisee, $manager, $cohort, ] = $this->testenv->appraisee0;
        $this->assign_cohort_step($this->testenv, $cohort);

        // Step #2
        $this->activate_step($this->testenv, []);

        // Step #3
        $recipients = [
            'Learner' => $appraisee->email,
            'Manager' => $manager->email
        ];
        $this->cron_step($this->testenv, $recipients);

        // Step #4
        [$appraisee1, $manager1, $cohort1, ] = $this->testenv->appraisee1;
        $this->assign_cohort_step($this->testenv, $cohort1);

        // Step #5
        $recipients1 = [
            'Learner' => $appraisee1->email,
            'Manager' => $manager1->email
        ];
        $this->cron_step($this->testenv, $recipients1);
    }

    /**
     * Tests appraisal activation with multiple jobs off in this sequence:
     * 1) activate appraisal, NO users assigned before activation
     * 2) add users after activation, NO "pressing" of update button
     * 3) run cron tasks
     */
    public function test_no_multijob_no_before_with_after_no_update(): void {
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        $this->activate_step($this->testenv, []);

        // Step #2
        [$appraisee1, $manager1, $cohort1, ] = $this->testenv->appraisee1;
        $this->assign_cohort_step($this->testenv, $cohort1);

        // Step #3
        $recipients1 = [
            'Learner' => $appraisee1->email,
            'Manager' => $manager1->email
        ];
        $this->cron_step($this->testenv, $recipients1);
    }

    /**
     * Tests appraisal activation with multiple jobs off in this sequence:
     * 1) activate appraisal, NO users assigned before activation
     * 2) add users after activation
     * 3) simulate pressing of 'update' button
     * 4) run cron tasks
     */
    public function test_no_multijob_no_before_with_after_with_update(): void {
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        $this->activate_step($this->testenv, []);

        // Step #2
        [$appraisee1, $manager1, $cohort1, ] = $this->testenv->appraisee1;
        $this->assign_cohort_step($this->testenv, $cohort1);

        // Step #3
        $this->update_button_step($this->testenv, []);

        // Step #4
        $recipients1 = [
            'Learner' => $appraisee1->email,
            'Manager' => $manager1->email
        ];
        $this->cron_step($this->testenv, $recipients1);
    }

    /**
     * Tests appraisal activation with multiple jobs on in this sequence:
     * 1) add users before activation
     * 2) activate appraisal
     * 3) run cron tasks
     * 4) simulate learner viewing appraisal
     * 5) run cron tasks
     */
    public function test_multijob_with_before_no_after(): void {
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        [$appraisee, $manager, $cohort, $appraiseeja] = $this->testenv->appraisee0;
        $this->assign_cohort_step($this->testenv, $cohort);

        // Step #2
        $this->activate_step($this->testenv, []);

        // Step #3
        $recipients = [
            'Learner' => $appraisee->email
        ];
        $this->cron_step($this->testenv, $recipients);

        // Step #4
        $recipients = [
            'Manager' => $manager->email
        ];
        $this->view_appraisal_step($this->testenv, $recipients, $appraisee, $appraiseeja);

        // Step #5
        $this->cron_step($this->testenv, []);
    }

    /**
     * Tests appraisal activation with multiple jobs on in this sequence:
     * 1) add users before activation
     * 2) activate appraisal
     * 3) run cron tasks
     * 4) simulate learner viewing appraisal
     * 5) add users after activation
     * 6) simulate pressing of 'update' button
     * 7) simulate learner viewing appraisal
     * 8) run cron tasks
     */
    public function test_multijob_with_before_with_after_with_update(): void {
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        [$appraisee, $manager, $cohort, $appraiseeja] = $this->testenv->appraisee0;
        $this->assign_cohort_step($this->testenv, $cohort);

        // Step #2
        $this->activate_step($this->testenv, []);

        // Step #3
        $recipients = [
            'Learner' => $appraisee->email
        ];
        $this->cron_step($this->testenv, $recipients);

        // Step #4
        $recipients = [
            'Manager' => $manager->email
        ];
        $this->view_appraisal_step($this->testenv, $recipients, $appraisee, $appraiseeja);

        // Step #5
        [$appraisee1, $manager1, $cohort1, $appraisee1ja] = $this->testenv->appraisee1;
        $this->assign_cohort_step($this->testenv, $cohort1);

        // Step #6
        $recipients1 = [
            'Learner' => $appraisee1->email
        ];
        $this->update_button_step($this->testenv, $recipients1);

        // Step #7
        $recipients1 = [
            'Manager' => $manager1->email
        ];
        $this->view_appraisal_step($this->testenv, $recipients1, $appraisee1, $appraisee1ja);

        // Step #8
        $this->cron_step($this->testenv, []);
    }

    /**
     * Tests appraisal activation with multiple jobs on in this sequence:
     * 1) add users before activation
     * 2) activate appraisal
     * 3) run cron tasks
     * 4) simulate learner viewing appraisal
     * 5) add users after activation
     * 6) run cron tasks
     * 7) simulate learner viewing appraisal
     * 8) run cron tasks
     */
    public function test_multijob_with_before_with_after_no_update(): void {
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        [$appraisee, $manager, $cohort, $appraiseeja] = $this->testenv->appraisee0;
        $this->assign_cohort_step($this->testenv, $cohort);

        // Step #2
        $this->activate_step($this->testenv, []);

        // Step #3
        $recipients = [
            'Learner' => $appraisee->email
        ];
        $this->cron_step($this->testenv, $recipients);

        // Step #4
        $recipients = [
            'Manager' => $manager->email
        ];
        $this->view_appraisal_step($this->testenv, $recipients, $appraisee, $appraiseeja);

        // Step #5
        [$appraisee1, $manager1, $cohort1, $appraisee1ja] = $this->testenv->appraisee1;
        $this->assign_cohort_step($this->testenv, $cohort1);

        // Step #6
        $recipients1 = [
            'Learner' => $appraisee1->email
        ];
        $this->cron_step($this->testenv, $recipients1);

        // Step #7
        $recipients1 = [
            'Manager' => $manager1->email
        ];
        $this->view_appraisal_step($this->testenv, $recipients1, $appraisee1, $appraisee1ja);

        // Step #8
        $this->cron_step($this->testenv, []);
    }

    /**
     * Tests appraisal activation with multiple jobs on in this sequence:
     * 1) activate appraisal, NO users assigned before activation
     * 2) add users after activation
     * 3) simulate pressing of 'update' button
     * 4) simulate learner viewing appraisal
     * 5) run cron tasks
     */
    public function test_multijob_no_before_with_after_with_update(): void {
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        $this->activate_step($this->testenv, []);

        // Step #2
        [$appraisee1, $manager1, $cohort1, $appraisee1ja] = $this->testenv->appraisee1;
        $this->assign_cohort_step($this->testenv, $cohort1);

        // Step #3
        $this->update_button_step($this->testenv, []);

        // Step #4
        $this->view_appraisal_step($this->testenv, [], $appraisee1, $appraisee1ja);

        // Step #5
        $recipients1 = [
            'Learner' => $appraisee1->email,
            'Manager' => $manager1->email
        ];
        $this->cron_step($this->testenv, $recipients1);
    }

    /**
     * Tests appraisal activation with multiple jobs on in this sequence:
     * 1) activate appraisal, NO users assigned before activation
     * 2) add users after activation
     * 3) run cron tasks
     * 4) simulate learner viewing appraisal
     * 5) run cron tasks
     */
    public function test_multijob_no_before_with_after_no_update(): void {
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        $this->activate_step($this->testenv, []);

        // Step #2
        [$appraisee1, $manager1, $cohort1, $appraisee1ja] = $this->testenv->appraisee1;
        $this->assign_cohort_step($this->testenv, $cohort1);

        // Step #3
        $recipients1 = [
            'Learner' => $appraisee1->email
        ];
        $this->cron_step($this->testenv, $recipients1);

        // Step #4
        $recipients1 = [
            'Manager' => $manager1->email
        ];
        $this->view_appraisal_step($this->testenv, $recipients1, $appraisee1, $appraisee1ja);

        // Step #5
        $this->cron_step($this->testenv, []);
    }

    /**
     * Generates a (user, manager, cohort containing that user, appraisee ja)
     * tuple.
     *
     * @param string name user name.
     *
     * @return array the tuple.
     */
    private function appraisee_details(string $name): array {
        $generator = $this->getDataGenerator();
        $manager = $generator->create_user(['username' => $name . '_manager']);
        $mgrja = job_assignment::create_default($manager->id)->id;

        $appraisee = $generator->create_user(['username' => $name]);
        $appraiseeid = $appraisee->id;
        $appraiseeja = job_assignment::create_default(
            $appraiseeid, ['managerjaid' => $mgrja]
        );

        $cohorts = $generator->get_plugin_generator('totara_cohort');
        $cohort = $cohorts->create_cohort(['name' => $name . '_cohort']);
        $cohorts->cohort_assign_users($cohort->id, [$appraiseeid]);

        return [$appraisee, $manager, $cohort, $appraiseeja];
    }

    /**
     * Retrieves system configuration values that need to be restored after a
     * test runs.
     *
     * @return array[string=>mixed] values that must be restored.
     */
    private function restorable_config_values(): array {
        $keys = [
            'dynamicappraisals',
            'totara_job_allowmultiplejobs'
        ];

        return array_reduce(
            $keys,

            function (array $acc, string $key): array {
                $acc[$key] = get_config(null, $key);
                return $acc;
            },

            []
        );
    }

    /**
     * Convenience function assign a cohort to the test appraisal.
     *
     * @param stdClass $context test execution context.
     * @param stdClass $cohort cohort details.
     */
    private function assign_cohort_step(stdClass $context, stdClass $cohort): void {
        $context->generator->create_group_assignment(
            $context->appraisal, 'cohort', $cohort->id
        );
    }

    /**
     * Activates an appraisal.
     *
     * @param stdClass $context test execution context.
     * @param array[string=>string] $recipients expected notification recipients;
     *        a mapping of role names to email addresses.
     */
    private function activate_step(stdClass $context, array $recipients): void {
        $context->appraisal->activate();
        $this->check_email_step($context, $recipients);
    }

    /**
     * Convenience function to run the specified cron tasks.
     *
     * @param stdClass $context test execution context.
     * @param array[string=>string] $recipients expected notification recipients;
     *        a mapping of role names to email addresses.
     */
    private function cron_step(stdClass $context, array $recipients): void {
        foreach ($context->tasks as $task) {
            $task->execute();
        }

        $this->check_email_step($context, $recipients);
    }

    /**
     * Convenience function to simulate the pressing of the "update" button when
     * assign users after activation.
     *
     * @param stdClass $context test execution context.
     * @param array[string=>string] $recipients expected notification recipients;
     *        a mapping of role names to email addresses.
     */
    private function update_button_step(stdClass $context, array $recipients): void {
        $context->appraisal->check_assignment_changes();
        $this->check_email_step($context, $recipients);
    }

    /**
     * Convenience function to simulate when the appraisee first views the
     * appraisal.
     *
     * @param stdClass $context test execution context.
     * @param array[string=>string] $recipients expected notification recipients;
     *        a mapping of role names to email addresses.
     * @param stdClass $appraisee appraisee who "views the appraisal".
     * @param job_assignment $ja appraisee job assignment.
     */
    private function view_appraisal_step(
        stdClass $context,
        array $recipients,
        stdClass $appraisee,
        job_assignment $ja
    ): void {
        appraisal_role_assignment::get_role(
            $context->appraisal->id,
            $appraisee->id,
            $appraisee->id,
            appraisal::ROLE_LEARNER
        )->get_user_assignment()->with_job_assignment(
            $ja->id
        );

        $this->check_email_step($context, $recipients);
    }

    /**
     * Checks the correct emails are sent out.
     *
     * @param stdClass $context test execution context.
     * @param array[string=>string] $recipients expected notification recipients;
     *        a mapping of role names to email addresses.
     */
    private function check_email_step(stdClass $context, array $recipients): void {
        $emails = $context->sink->get_messages();
        $this->assertCount(count($recipients), $emails, 'wrong email count');

        $actual = array_reduce(
            $emails,

            function (array $result, stdClass $email): array {
                $addr = $email->to;
                $subject = $email->subject;

                if (strpos($subject, 'Learner') !== false) {
                    $result['Learner'] = $addr;
                }
                else if (strpos($subject, 'Manager') !== false) {
                    $result['Manager'] = $addr;
                }

                return $result;
            },

            []
        );
        $context->sink->clear();

        // Sort both array's the same way, we're not worried about the order, just the result.
        ksort($recipients);
        ksort($actual);

        $this->assertSame($recipients, $actual, "wrong email recipients");
    }
}
