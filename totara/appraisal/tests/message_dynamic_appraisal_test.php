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
require_once($CFG->dirroot.'/totara/appraisal/tests/message_appraisal_testcase.php');

/**
 * Tests the sending of activation notifications for dynamic appraisals.
 */
class totara_appraisal_messages_dynamic_test  extends totara_appraisal_messages_testcase {
    /**
     * Sets up the test environment.
     *
     * @return \stdClass parent::setup_test_env() generated test environment.
     */
    private function setup_dynamic_appraisal_test_env(): \stdClass {
        $test_env = $this->setup_test_env();

        // setup_test_env() records the current $CFG/system config settings. So
        // set_config() needs to be done after that.
        set_config('dynamicappraisals', true);

        return $test_env;
    }

    /**
     * Cleans up the test environment after a test.
     *
     * @param \stdClass $context setup_static_appraisal_test_env() generated test
     *        context.
     */
    final protected function cleanup_dynamic_appraisal_test_env(
        \stdClass $context
    ): void {
        // Restores the original $CFG/system config settings.
        $this->cleanup_test_env($context);
    }

    /**
     * Tests appraisal activation with multiple jobs off in this sequence:
     * 1) add users before activation
     * 2) activate appraisal
     * 3) run cron tasks
     * 4) run cron tasks again
     */
    public function test_no_multijob_with_before_no_after(): void {
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        $appraisees = $test_env->pre_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $appraisees
        );

        // Step #2
        $this->activate_step($test_env, []);

        // Step #3
        // Appraisal activation sorts out appraisee job assignments. Execution
        // ultimately reaches appraisal::send_appraisal_wide_message() which does
        // a BULK send of emails for ALL managers in the appraisals. Duplicates
        // are prevented because the system keeps track of emails *within* the
        // bulk invocation.
        $emails = array_reduce(
            $appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                $to_send = array_merge(
                    $acc, $this->emails_for_appraisee($test_env, $appraisee)
                );

                return $this->emails_for_manager(
                    $to_send, $test_env, $appraisee, $manager, true
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);

        // Step #4
        $this->cron_step($test_env, []);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
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
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        $pre_activation_appraisees = $test_env->pre_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $pre_activation_appraisees
        );

        // Step #2
        $this->activate_step($test_env, []);

        // Step #3
        // Appraisal activation sorts out appraisee job assignments. Execution
        // ultimately reaches appraisal::send_appraisal_wide_message() which does
        // a BULK send of emails for ALL managers in the appraisals. Duplicates
        // are prevented because the system keeps track of emails *within* the
        // bulk invocation.
        $emails = array_reduce(
            $pre_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                $to_send = array_merge(
                    $acc, $this->emails_for_appraisee($test_env, $appraisee)
                );

                return $this->emails_for_manager(
                    $to_send, $test_env, $appraisee, $manager, true
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);

        // Step #4
        $post_activation_appraisees = $test_env->post_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $post_activation_appraisees
        );

        // Step #5
        // Appraisee job assignment happens upon pressing the update button but
        // this time, it is appraisal::check_assignment_changes() that handles
        // it. This is NOT a bulk operation and since emails are not tracked in
        // *between* invocations, it is possible for duplicate emails to occur.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                $to_send = array_merge(
                    $acc, $this->emails_for_appraisee($test_env, $appraisee)
                );

                return $this->emails_for_manager(
                    $to_send, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->update_button_step($test_env, $emails);

        // Step #6
        $this->cron_step($test_env, []);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
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
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        $pre_activation_appraisees = $test_env->pre_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $pre_activation_appraisees
        );

        // Step #2
        $this->activate_step($test_env, []);

        // Step #3
        // Appraisal activation sorts out appraisee job assignments. Execution
        // ultimately reaches appraisal::send_appraisal_wide_message() which does
        // a BULK send of emails for ALL managers in the appraisals. Duplicates
        // are prevented because the system keeps track of emails *within* the
        // bulk invocation.
        $emails = array_reduce(
            $pre_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                $to_send = array_merge(
                    $acc, $this->emails_for_appraisee($test_env, $appraisee)
                );

                return $this->emails_for_manager(
                    $to_send, $test_env, $appraisee, $manager, true
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);

        // Step #4
        $post_activation_appraisees = $test_env->post_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $post_activation_appraisees
        );

        // Step #5
        // appraisal::check_assignment_changes() is called from the cron task;
        // This is NOT a bulk operation and it is possible for duplicate emails
        // to occur.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                $to_send = array_merge(
                    $acc, $this->emails_for_appraisee($test_env, $appraisee)
                );

                return $this->emails_for_manager(
                    $to_send, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
    }

    /**
     * Tests appraisal activation with multiple jobs off in this sequence:
     * 1) activate appraisal, NO users assigned before activation
     * 2) add users after activation, NO "pressing" of update button
     * 3) run cron tasks
     */
    public function test_no_multijob_no_before_with_after_no_update(): void {
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        $this->activate_step($test_env, []);

        // Step #2
        $post_activation_appraisees = $test_env->post_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $post_activation_appraisees
        );

        // Step #3
        // appraisal::check_assignment_changes() is called from the cron task;
        // This is NOT a bulk operation and it is possible for duplicate emails
        // to occur.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                $to_send = array_merge(
                    $acc, $this->emails_for_appraisee($test_env, $appraisee)
                );

                return $this->emails_for_manager(
                    $to_send, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
    }

    /**
     * Tests appraisal activation with multiple jobs off in this sequence:
     * 1) activate appraisal, NO users assigned before activation
     * 2) add users after activation
     * 3) simulate pressing of 'update' button
     * 4) run cron tasks
     */
    public function test_no_multijob_no_before_with_after_with_update(): void {
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', false);

        // Step #1
        $this->activate_step($test_env, []);

        // Step #2
        $post_activation_appraisees = $test_env->post_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $post_activation_appraisees
        );

        // Step #3
        // On the UI, it is possible to press the "update" button at this time;
        // Incredibly, it has no effect because *there were no appraisees before
        // activation*. Unlike test_no_multijob_with_before_with_after_with_update(),
        // no emails will be sent out at this time; cron must run first in order
        // to assign the appraisees!
        $this->update_button_step($test_env, []);

        // Step #4
        // Even though the appraisal was activated, the appraisal wide activated
        // message was not triggered because *there were no appraisees before*.
        // Which means it is still pending. And because the appraisal messages
        // cron task runs first in this test harness, that means a BULK send of
        // messages occurs. Duplicates are prevented because it is a bulk send,
        // very unlike test_no_multijob_with_before_with_after_with_update().
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                $to_send = array_merge(
                    $acc, $this->emails_for_appraisee($test_env, $appraisee)
                );

                return $this->emails_for_manager(
                    $to_send, $test_env, $appraisee, $manager, true
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
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
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        $pre_activation_appraisees = $test_env->pre_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $pre_activation_appraisees
        );


        // Step #2
        $this->activate_step($test_env, []);

        // Step #3
        // Appraisal activation DOES NOT sort out appraisee job assignments; that
        // only happens when appraisees viewing an appraisal. So only emails to
        // appraisees go out now.
        $emails = array_reduce(
            $pre_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, , , ] = $tuple;

                return array_merge(
                    $acc,
                    $this->emails_for_appraisee($test_env, $appraisee)
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);

        // Step #4
        // Unlike activation, appraisal_user_assignment::with_job_assignment()
        // is the method handling job assignment changes for the appraisee. This
        // is NOT a bulk operation and since emails are not tracked *between*
        // invocations, it is possible for duplicate emails to occur.
        $emails = array_reduce(
            $pre_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                return $this->emails_for_manager(
                    $acc, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->view_appraisal_step($test_env, $pre_activation_appraisees, $emails);

        // Step #5
        $this->cron_step($test_env, []);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
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
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        $pre_activation_appraisees = $test_env->pre_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $pre_activation_appraisees
        );

        // Step #2
        $this->activate_step($test_env, []);

        // Step #3
        // Appraisal activation DOES NOT sort out appraisee job assignments; that
        // only happens when appraisees viewing an appraisal. So only emails to
        // appraisees go out now.
        $emails = array_reduce(
            $pre_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, , , ] = $tuple;

                return array_merge(
                    $acc,
                    $this->emails_for_appraisee($test_env, $appraisee)
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);

        // Step #4
        // Unlike activation, appraisal_user_assignment::with_job_assignment()
        // is the method handling job assignment changes for the appraisee. This
        // is NOT a bulk operation and since emails are not tracked *between*
        // invocations, it is possible for duplicate emails to occur.
        $emails = array_reduce(
            $pre_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                return $this->emails_for_manager(
                    $acc, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->view_appraisal_step($test_env, $pre_activation_appraisees, $emails);

        // Step #5
        $post_activation_appraisees = $test_env->post_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $post_activation_appraisees
        );

        // Step #6
        // Appraisees still need to view the appraisal first before their managers
        // know about it. So only appraisee emails at this time. And because there
        // were appraisee assigned before activation, the update method itself
        // sends emails, not the appraisal message sending cron task.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, , , ] = $tuple;

                return array_merge(
                    $acc,
                    $this->emails_for_appraisee($test_env, $appraisee)
                );
            },
            []
        );
        $this->update_button_step($test_env, $emails);

        // Step #7
        // Appraisees view the appraisal and their managers get emails. But this
        // is NOT a bulk operation and since emails are not tracked *between*
        // invocations, it is possible for duplicate emails to occur.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                return $this->emails_for_manager(
                    $acc, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->view_appraisal_step($test_env, $post_activation_appraisees, $emails);

        // Step #8
        $this->cron_step($test_env, []);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
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
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        $pre_activation_appraisees = $test_env->pre_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $pre_activation_appraisees
        );

        // Step #2
        $this->activate_step($test_env, []);

        // Step #3
        // Appraisal activation DOES NOT sort out appraisee job assignments; that
        // only happens when appraisees viewing an appraisal. So only emails to
        // appraisees go out now.
        $emails = array_reduce(
            $pre_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, , , ] = $tuple;

                return array_merge(
                    $acc,
                    $this->emails_for_appraisee($test_env, $appraisee)
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);

        // Step #4
        // Appraisees view the appraisal and their managers get emails. But this
        // is NOT a bulk operation and since emails are not tracked *between*
        // invocations, it is possible for duplicate emails to occur.
        $emails = array_reduce(
            $pre_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                return $this->emails_for_manager(
                    $acc, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->view_appraisal_step($test_env, $pre_activation_appraisees, $emails);

        // Step #5
        $post_activation_appraisees = $test_env->post_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $post_activation_appraisees
        );

        // Step #6
        // Only appraisee emails now.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, , , ] = $tuple;

                return array_merge(
                    $acc,
                    $this->emails_for_appraisee($test_env, $appraisee)
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);

        // Step #7
        // Appraisees view the appraisal and their managers get emails. But this
        // is NOT a bulk operation and since emails are not tracked *between*
        // invocations, it is possible for duplicate emails to occur.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                return $this->emails_for_manager(
                    $acc, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->view_appraisal_step($test_env, $post_activation_appraisees, $emails);

        // Step #8
        $this->cron_step($test_env, []);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
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
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        $this->activate_step($test_env, []);

        // Step #2
        $post_activation_appraisees = $test_env->post_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $post_activation_appraisees
        );

        // Step #3
        // On the UI, it is possible to press the "update" button at this time;
        // Incredibly, it has no effect because *there were no appraisees before
        // activation*. Unlike test_no_multijob_with_before_with_after_with_update(),
        // no emails will be sent out at this time.
        $this->update_button_step($test_env, []);

        // Step #4
        // The appraisee can actually view the appraisal after assignment even
        // though he does not get any notification. The system now knows which
        // job assignment to use; but since cron did not run and there were no
        // appraisees before activation, the system wide activation message is
        // *still pending*. And that means no emails here either!
        $this->view_appraisal_step($test_env, $post_activation_appraisees, []);

        // Step #5
        // Only now do the recipients get emails; because this is a bulk send,
        // there are no duplicates.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                $to_send = array_merge(
                    $acc, $this->emails_for_appraisee($test_env, $appraisee)
                );

                return $this->emails_for_manager(
                    $to_send, $test_env, $appraisee, $manager, true
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
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
        $test_env = $this->setup_dynamic_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', true);

        // Step #1
        $this->activate_step($test_env, []);

        // Step #2
        $post_activation_appraisees = $test_env->post_activation_appraisees;
        array_map(
            function (array $tuple) use ($test_env): void {
                [, , $cohort, ] = $tuple;
                $this->assign_cohort_step($test_env, $cohort);
            },
            $post_activation_appraisees
        );

        // Step #3
        // Appraisal activation DOES NOT sort out appraisee job assignments; that
        // only happens when appraisees viewing an appraisal. So only emails to
        // appraisees go out now.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, , , ] = $tuple;

                return array_merge(
                    $acc,
                    $this->emails_for_appraisee($test_env, $appraisee)
                );
            },
            []
        );
        $this->cron_step($test_env, $emails);

        // Step #4
        // Appraisees view the appraisal and their managers get emails. But this
        // is NOT a bulk operation and since emails are not tracked *between*
        // invocations, it is possible for duplicate emails to occur.
        $emails = array_reduce(
            $post_activation_appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                return $this->emails_for_manager(
                    $acc, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->view_appraisal_step($test_env, $post_activation_appraisees, $emails);

        // Step #5
        $this->cron_step($test_env, []);
        $this->cleanup_dynamic_appraisal_test_env($test_env);
    }
}
