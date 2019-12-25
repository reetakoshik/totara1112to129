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
class mod_scorm_userdata_scoes_track_testcase extends advanced_testcase {
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

        $this->create_scoes_track($user, $sco);
        $this->create_scoes_track($otheruser, $sco);
        $this->assertEquals(6, $DB->count_records('scorm_scoes_track', ['scormid' => $scorm->id]));


        $DB->set_field('modules', 'visible', '0', ['name' => 'scorm']);

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($user);
        \mod_scorm\userdata\scoes_track::execute_purge($target_user, context_system::instance());

        $this->assertEquals(3, $DB->count_records('scorm_scoes_track', ['scormid' => $scorm->id]));
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
     * Create two records (score and status) for scorm_scoes_track
     * @param stdClass $user
     * @param stdClass $scorm
     * @param int $time
     */
    private function create_scoes_track(stdClass $user, stdClass $sco, $time = 0) {
        global $DB;
        $time = empty($time) ? time() : $time;

        $score = new stdClass();
        $score->userid = $user->id;
        $score->scormid = $sco->scorm;
        $score->scoid = $sco->id;
        $score->attempt = 1;
        $score->element = 'cmi.core.score.raw';
        $score->value = rand(0,100);
        $score->timemodified = $time;
        $score->id = $DB->insert_record('scorm_scoes_track', $score);

        $status = new stdClass();
        $status->userid = $user->id;
        $status->scormid = $sco->scorm;
        $status->scoid = $sco->id;
        $status->attempt = 1;
        $status->element = 'cmi.core.lesson_status';
        $status->value = 'complete';
        $status->timemodified = $time;

        $status->id = $DB->insert_record('scorm_scoes_track', $status);
        $status = new stdClass();
        $status->userid = $user->id;
        $status->scormid = $sco->scorm;
        $status->scoid = $sco->id;
        $status->attempt = 1;
        $status->element = 'cmi.core.exit';
        $status->value = 'suspended';
        $status->timemodified = $time;

        $status->id = $DB->insert_record('scorm_scoes_track', $status);

        return [$score, $status];
    }

    /**
     * Create scorms with the following scheme:
     * - Two users (user and otheruser)
     * - In default (misc) category one course (c1)
     * - Course Category (cat2) with two courses (c2, c3)
     * - 4 SCORMs (s1 in c1, s2 in c2, s3 and s4 in c3)
     * - 4 SCOs (one per each SCORM)
     * - 24 scoes_track record (3 for each user for each SCO)
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
            $that->usertracks[$i] = $this->create_scoes_track($that->user, $that->scos['sco' . $i]);
            $that->otherusertracks[$i] = $this->create_scoes_track($that->otheruser, $that->scos['sco' . $i]);
        }

        $this->assertEquals(4, $DB->count_records('scorm'));
        $this->assertEquals(24, $DB->count_records('scorm_scoes_track'));

        return $that;
    }

    /**
     * Test purge on system context
     */
    public function test_purge_track_system_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_scorm\userdata\scoes_track::execute_purge($target_user, context_system::instance());

        $this->assertEquals(12, $DB->count_records('scorm_scoes_track'));
        $this->assertEquals(0, $DB->count_records('scorm_scoes_track', ['userid' => $that->user->id]));
    }

    /**
     * Test purge on course category context
     */
    public function test_purge_track_coursecat_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_scorm\userdata\scoes_track::execute_purge(
            $target_user,
            context_coursecat::instance($that->cat2->id)
        );

        $this->assertEquals(12, $DB->count_records('scorm_scoes_track', ['userid' => $that->otheruser->id]));
        $this->assertEquals(3, $DB->count_records('scorm_scoes_track', ['userid' => $that->user->id]));

        // Make sure that the correct scorm scoes_track were removed.
        $remainingscoestrack = $DB->get_records('scorm_scoes_track', ['userid' => $that->user->id]);
        foreach ($remainingscoestrack as $remainingtrack) {
            $this->assertEquals($that->scorms['scorm1']->id, $remainingtrack->scormid);
        }

    }

    /**
     * Test purge on course context
     */
    public function test_purge_track_course_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();


        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_scorm\userdata\scoes_track::execute_purge(
            $target_user,
            context_course::instance($that->courses['c3']->id)
        );

        $this->assertEquals(12, $DB->count_records('scorm_scoes_track', ['userid' => $that->otheruser->id]));
        $this->assertEquals(6, $DB->count_records('scorm_scoes_track', ['userid' => $that->user->id]));

        // Make sure that the correct scorm scoes_track were removed.
        $remainingscoestrack = $DB->get_records('scorm_scoes_track', ['userid' => $that->user->id]);
        foreach ($remainingscoestrack as $remainingtrack) {
            $this->assertContains($remainingtrack->scormid, [$that->scorms['scorm1']->id, $that->scorms['scorm2']->id]);
        }

    }

    /**
     * Test purge on track module context
     */
    public function test_purge_track_module_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_scorm\userdata\scoes_track::execute_purge(
            $target_user,
            context_module::instance($that->scorms['scorm4']->cmid)
        );

        $this->assertEquals(12, $DB->count_records('scorm_scoes_track', ['userid' => $that->otheruser->id]));
        $this->assertEquals(9, $DB->count_records('scorm_scoes_track', ['userid' => $that->user->id]));

        // Make sure that the correct scorm scoes_track were removed.
        $remainingscoestrack = $DB->get_records('scorm_scoes_track', ['userid' => $that->user->id]);
        foreach ($remainingscoestrack as $remainingtrack) {
            $this->assertContains($remainingtrack->scormid, [$that->scorms['scorm1']->id, $that->scorms['scorm2']->id,
                    $that->scorms['scorm3']->id]);
        }
    }

    /**
     * Check that count is correct to export, when scorm has no matching for export SCOs
     */
    public function test_export_count_other_tracks() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $scorm = $this->create_scorm($course);
        $sco = $this->create_sco($scorm);
        $this->create_scoes_track($user, $sco);

        $scorm2 = $this->create_scorm($course);
        $sco2 = $this->create_sco($scorm);

        // Create unexported scoes track
        $status = new stdClass();
        $status->userid = $user->id;
        $status->scormid = $sco2->scorm;
        $status->scoid = $sco2->id;
        $status->attempt = 1;
        $status->element = 'cmi.interactions_0.id';
        $status->value = 'key0b0';
        $status->timemodified = time();
        $status->id = $DB->insert_record('scorm_scoes_track', $status);

        // Set up and execute the export item.
        $target_user = new \totara_userdata\userdata\target_user($user);
        $export = \mod_scorm\userdata\scoes_track::execute_export(
            $target_user,
            context_system::instance()
        );
        $count = \mod_scorm\userdata\scoes_track::execute_count(
            $target_user,
            context_system::instance()
        );

        $this->assertEquals(1, $count);
        $this->assertCount(1, $export->data);
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export_track_system_context() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the export item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \mod_scorm\userdata\scoes_track::execute_export(
            $target_user,
            context_system::instance()
        );
        $count = \mod_scorm\userdata\scoes_track::execute_count(
            $target_user,
            context_system::instance()
        );

        $this->assertEquals($that->scos['sco1']->title, $export->data[$that->scorms['scorm1']->id]['sco'][$that->scos['sco1']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm1']->id]['sco'][$that->scos['sco1']->id]['track']);

        $this->assertEquals($that->scos['sco2']->title, $export->data[$that->scorms['scorm2']->id]['sco'][$that->scos['sco2']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm2']->id]['sco'][$that->scos['sco2']->id]['track']);

        $this->assertEquals($that->scos['sco3']->title, $export->data[$that->scorms['scorm3']->id]['sco'][$that->scos['sco3']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm3']->id]['sco'][$that->scos['sco3']->id]['track']);

        $this->assertEquals($that->scos['sco4']->title, $export->data[$that->scorms['scorm4']->id]['sco'][$that->scos['sco4']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm4']->id]['sco'][$that->scos['sco4']->id]['track']);

        $this->assertCount(4, $export->data);
        $this->assertEquals(4, $count);
    }

    public function test_export_track_coursecat_context() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \mod_scorm\userdata\scoes_track::execute_export(
            $target_user,
            context_coursecat::instance($that->cat2->id)
        );
        $count = \mod_scorm\userdata\scoes_track::execute_count(
            $target_user,
            context_coursecat::instance($that->cat2->id)
        );

        $this->assertEquals($that->scos['sco2']->title, $export->data[$that->scorms['scorm2']->id]['sco'][$that->scos['sco2']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm2']->id]['sco'][$that->scos['sco2']->id]['track']);

        $this->assertEquals($that->scos['sco3']->title, $export->data[$that->scorms['scorm3']->id]['sco'][$that->scos['sco3']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm3']->id]['sco'][$that->scos['sco3']->id]['track']);

        $this->assertEquals($that->scos['sco4']->title, $export->data[$that->scorms['scorm4']->id]['sco'][$that->scos['sco4']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm4']->id]['sco'][$that->scos['sco4']->id]['track']);


        $this->assertCount(3, $export->data);
        $this->assertEquals(3, $count);
    }

    public function test_export_track_course_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the export item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \mod_scorm\userdata\scoes_track::execute_export(
            $target_user,
            context_course::instance($that->courses['c3']->id)
        );
        $count = \mod_scorm\userdata\scoes_track::execute_count(
            $target_user,
            context_course::instance($that->courses['c3']->id)
        );

        $this->assertEquals($that->scos['sco3']->title, $export->data[$that->scorms['scorm3']->id]['sco'][$that->scos['sco3']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm3']->id]['sco'][$that->scos['sco3']->id]['track']);

        $this->assertEquals($that->scos['sco4']->title, $export->data[$that->scorms['scorm4']->id]['sco'][$that->scos['sco4']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm4']->id]['sco'][$that->scos['sco4']->id]['track']);

        $this->assertCount(2, $export->data);
        $this->assertEquals(2, $count);
    }

    public function test_export_track_module_context() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_scorm_data_for_multiple_contexts();

        // Set up and execute the export item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \mod_scorm\userdata\scoes_track::execute_export(
            $target_user,
            context_module::instance($that->scorms['scorm4']->cmid)
        );
        $count = \mod_scorm\userdata\scoes_track::execute_count(
            $target_user,
            context_module::instance($that->scorms['scorm4']->cmid)
        );

        $this->assertEquals($that->scos['sco4']->title, $export->data[$that->scorms['scorm4']->id]['sco'][$that->scos['sco4']->id]['title']);
        $this->assertCount(2, $export->data[$that->scorms['scorm4']->id]['sco'][$that->scos['sco4']->id]['track']);

        $this->assertCount(1, $export->data);
        $this->assertEquals(1, $count);
    }
}