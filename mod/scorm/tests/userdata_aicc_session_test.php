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
 * @author Valerii Kuznetsov <valerii.kuznetsov@@totaralearning.com>
 * @package mod_scorm
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_userdata
 */
class mod_scorm_userdata_aicc_session_testcase extends advanced_testcase {
    /**
     * Test that purging still occurs when the module is not visible.
     *
     * There's no need to repeat this test across different contexts as it is expected to either
     * occur in this scenario or not at all.
     */
    public function test_purge_scorm_module_not_visible() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();
        $scorm = $this->create_scorm($course);
        $sco = $this->create_sco($scorm);

        $this->create_aicc_session($user, $sco);
        $this->create_aicc_session($otheruser, $sco);
        $this->assertEquals(2, $DB->count_records('scorm_aicc_session', ['scormid' => $scorm->id]));


        $DB->set_field('modules', 'visible', '0', ['name' => 'scorm']);

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($user);
        \mod_scorm\userdata\aicc_session::execute_purge($target_user, context_system::instance());

        $this->assertEquals(1, $DB->count_records('scorm_aicc_session', ['scormid' => $scorm->id]));

        $aicc = $DB->get_record('scorm_aicc_session', ['scormid' => $scorm->id]);
        $this->assertEquals($scorm->id, $aicc->scormid);
        $this->assertEquals($sco->id, $aicc->scoid);
        $this->assertEquals($otheruser->id, $aicc->userid);
    }


    /**
     * Create scorm module
     * @param stdClass $scorm
     * @return stdClass
     */
    private function create_sco(stdClass $scorm) {
        global $DB;
        static $count = 0;

        $identifier = 'eXenewPackage35ab';
        $sco = new stdClass();
        $sco->scorm = $scorm->id;
        $sco->manifest = $identifier;
        $sco->organization = '';
        $sco->parent = '/';
        $sco->identifier = $identifier;
        $sco->launch = '';
        $sco->scormtype = '';
        $sco->title = 'SCO ' . $count;
        $sco->sortorder = $count;

        $sco->id = $DB->insert_record('scorm_scoes', $sco);
        $count++;
        return $sco;
    }

    /**
     * Create scorm module
     * @param $course
     * @return stdClass
     */
    private function create_scorm($course) {

        $scorm = $this->getDataGenerator()->create_module(
            'scorm',
            ['course' => $course]
        );

        return $scorm;
    }


    /**
     * Create record for scorm_aicc_session
     * @param stdClass $user
     * @param stdClass $scorm
     * @param int $time
     */
    private function create_aicc_session(stdClass $user, stdClass $sco, $time = 0) {
        global $DB;
        $time = empty($time) ? time() : $time;

        $aicc = new stdClass();
        $aicc->userid = $user->id;
        $aicc->scormid = $sco->scorm;
        $aicc->scoid = $sco->id;
        $aicc->attempt = 1;
        $aicc->hacpsession = 'hacpsession';
        $aicc->scormmode = 'normal';
        $aicc->lessonstatus = 'passed';
        $aicc->sessiontime = '02:34:05';
        $aicc->timecreated = $time;
        $aicc->timemodified = $time;

        $aicc->id = $DB->insert_record('scorm_aicc_session', $aicc);
        return $aicc;
    }

    /**
     * Create scorms with the following scheme:
     * - Two users (user and otheruser)
     * - In default (misc) category one course (c1)
     * - Course Category (cat2) with two courses (c2, c3)
     * - 4 SCORMs (s1 in c1, s2 in c2, s3 and s4 in c3)
     * - 4 SCOs (one per each SCORM)
     * - 16 aicc_session record (2 for each user for each SCO)
     * @return stdClass with created instances
     */
    private function create_scorm_data_for_multiple_contexts() {
        global $DB;

        $that = new stdClass();
        $that->user = $this->getDataGenerator()->create_user();
        $that->otheruser = $this->getDataGenerator()->create_user();

        $that->courses['c1'] = $this->getDataGenerator()->create_course();

        $that->cat2 = $this->getDataGenerator()->create_category();
        $that->courses['c2'] = $this->getDataGenerator()->create_course(['category' => $that->cat2->id]);
        $that->courses['c3'] = $this->getDataGenerator()->create_course(['category' => $that->cat2->id]);

        /**
         * @var mod_scorm_generator $scormgenerator
         */
        $scormgenerator = $this->getDataGenerator()->get_plugin_generator('mod_scorm');

        $that->scorms = [];

        $that->scorms['scorm1'] = $this->create_scorm($that->courses['c1']);
        $that->scorms['scorm2'] = $this->create_scorm($that->courses['c2']);
        $that->scorms['scorm3'] = $this->create_scorm($that->courses['c3']);
        $that->scorms['scorm4'] = $this->create_scorm($that->courses['c3']);

        $that->scos['sco1'] = $this->create_sco($that->scorms['scorm1']);
        $that->scos['sco2'] = $this->create_sco($that->scorms['scorm2']);
        $that->scos['sco3'] = $this->create_sco($that->scorms['scorm3']);
        $that->scos['sco4'] = $this->create_sco($that->scorms['scorm4']);

        for ($i = 1; $i <= 4; $i++) {
            $that->useraiccs['s' . $i . '_1'] = $this->create_aicc_session($that->user, $that->scos['sco' . $i]);
            $that->useraiccs['s' . $i . '_2'] = $this->create_aicc_session($that->user, $that->scos['sco' . $i]);
            $that->otheruseraiccs['s' . $i . '_1'] = $this->create_aicc_session($that->otheruser, $that->scos['sco' . $i]);
            $that->otheruseraiccs['s' . $i . '_2'] = $this->create_aicc_session($that->otheruser, $that->scos['sco' . $i]);
        }

        $this->assertEquals(4, $DB->count_records('scorm'));
        $this->assertEquals(16, $DB->count_records('scorm_aicc_session'));

        return $that;
    }

    public function test_purge_aicc_system_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_scorm\userdata\aicc_session::execute_purge($target_user, context_system::instance());

        $this->assertEquals(8, $DB->count_records('scorm_aicc_session'));
        $this->assertEquals(0, $DB->count_records('scorm_aicc_session', ['userid' => $that->user->id]));
    }

    public function test_purge_aicc_coursecat_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_scorm\userdata\aicc_session::execute_purge(
            $target_user,
            context_coursecat::instance($that->cat2->id)
        );

        $this->assertEquals(8, $DB->count_records('scorm_aicc_session', ['userid' => $that->otheruser->id]));
        $this->assertEquals(2, $DB->count_records('scorm_aicc_session', ['userid' => $that->user->id]));

        // Make sure that the correct scorm aicc_session were removed.
        $remainingaiccsession = $DB->get_records('scorm_aicc_session', ['userid' => $that->user->id]);
        foreach ($remainingaiccsession as $remainingaicc) {
            $this->assertEquals($that->scorms['scorm1']->id, $remainingaicc->scormid);
        }

    }

    public function test_purge_aicc_course_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();


        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_scorm\userdata\aicc_session::execute_purge(
            $target_user,
            context_course::instance($that->courses['c3']->id)
        );

        $this->assertEquals(8, $DB->count_records('scorm_aicc_session', ['userid' => $that->otheruser->id]));
        $this->assertEquals(4, $DB->count_records('scorm_aicc_session', ['userid' => $that->user->id]));

        // Make sure that the correct scorm aicc_session were removed.
        $remainingaiccsession = $DB->get_records('scorm_aicc_session', ['userid' => $that->user->id]);
        foreach ($remainingaiccsession as $remainingaicc) {
            $this->assertContains($remainingaicc->scormid, [$that->scorms['scorm1']->id, $that->scorms['scorm2']->id]);
        }

    }

    public function test_purge_aicc_module_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_scorm\userdata\aicc_session::execute_purge(
            $target_user,
            context_module::instance($that->scorms['scorm4']->cmid)
        );

        $this->assertEquals(8, $DB->count_records('scorm_aicc_session', ['userid' => $that->otheruser->id]));
        $this->assertEquals(6, $DB->count_records('scorm_aicc_session', ['userid' => $that->user->id]));

        // Make sure that the correct scorm aicc_session were removed.
        $remainingaiccsession = $DB->get_records('scorm_aicc_session', ['userid' => $that->user->id]);
        foreach ($remainingaiccsession as $remainingaicc) {
            $this->assertContains($remainingaicc->scormid, [$that->scorms['scorm1']->id, $that->scorms['scorm2']->id,
                $that->scorms['scorm3']->id]);
        }
    }
}