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
 * Tests the sending of activation notifications for static appraisals.
 */
class totara_appraisal_messages_static_test extends totara_appraisal_messages_testcase {
    /**
     * Sets up the test environment.
     *
     * @return \stdClass parent::setup_test_env() generated test environment.
     */
    private function setup_static_appraisal_test_env(): \stdClass {
        $test_env = $this->setup_test_env();

        // setup_test_env() records the current $CFG/system config settin`gs. So
        // set_config() needs to be done after that.
        set_config('dynamicappraisals', false);

        return $test_env;
    }

    /**
     * Cleans up the test environment after a test.
     *
     * @param \stdClass $context setup_static_appraisal_test_env() generated test
     *        context.
     */
    final protected function cleanup_static_appraisal_test_env(
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
    public function test_no_multijob(): void {
        $test_env = $this->setup_static_appraisal_test_env();
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
        $this->cleanup_static_appraisal_test_env($test_env);
    }

    /**
     * Tests appraisal activation with multiple jobs on in this sequence:
     * 1) add users before activation
     * 2) activate appraisal
     * 3) run cron tasks
     * 4) simulate learner viewing appraisal
     * 5) run cron tasks
     */
    public function test_multijob(): void {
        $test_env = $this->setup_static_appraisal_test_env();
        set_config('totara_job_allowmultiplejobs', true);

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
        // Appraisal activation DOES NOT sort out appraisee job assignments; that
        // only happens when appraisees viewing an appraisal. So only emails to
        // appraisees go out now.
        $emails = array_reduce(
            $appraisees,
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
            $appraisees,
            function (array $acc, array $tuple) use ($test_env): array {
                [$appraisee, $manager, , ] = $tuple;

                return $this->emails_for_manager(
                    $acc, $test_env, $appraisee, $manager, false
                );
            },
            []
        );
        $this->view_appraisal_step($test_env, $appraisees, $emails);

        // Step #5
        $this->cron_step($test_env, []);
        $this->cleanup_static_appraisal_test_env($test_env);
    }
}
