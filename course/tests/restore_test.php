<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course restore tests.
 *
 * @package    core_course
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Course restore testcase.
 *
 * @package    core_course
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_course_restore_testcase extends advanced_testcase {

    /**
     * Backup a course and return its backup ID.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user doing the backup.
     * @return string
     */
    protected function backup_course($courseid, $userid = 2) {
        globaL $CFG;
        $packer = get_file_packer('application/vnd.moodle.backup');

        $bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, $userid);
        $bc->execute_plan();

        $results = $bc->get_results();
        $results['backup_destination']->extract_to_pathname($packer, "$CFG->tempdir/backup/core_course_testcase");

        $bc->destroy();
        unset($bc);
        return 'core_course_testcase';
    }

    /**
     * Create a role with capabilities and permissions.
     *
     * @param string|array $caps Capability names.
     * @param int $perm Constant CAP_* to apply to the capabilities.
     * @return int The new role ID.
     */
    protected function create_role_with_caps($caps, $perm) {
        $caps = (array) $caps;
        $dg = $this->getDataGenerator();
        $roleid = $dg->create_role();
        foreach ($caps as $cap) {
            assign_capability($cap, $perm, $roleid, context_system::instance()->id, true);
        }
        accesslib_clear_all_caches_for_unit_testing();
        return $roleid;
    }

    /**
     * Restore a course.
     *
     * @param int $backupid The backup ID.
     * @param int $courseid The course ID to restore in, or 0.
     * @param int $userid The ID of the user performing the restore.
     * @return stdClass The updated course object.
     */
    protected function restore_course($backupid, $courseid, $userid) {
        global $DB;

        $target = backup::TARGET_CURRENT_ADDING;
        if (!$courseid) {
            $target = backup::TARGET_NEW_COURSE;
            $categoryid = $DB->get_field_sql("SELECT MIN(id) FROM {course_categories}");
            $courseid = restore_dbops::create_new_course('Tmp', 'tmp', $categoryid);
        }

        $rc = new restore_controller($backupid, $courseid, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userid, $target);
        $target == backup::TARGET_NEW_COURSE ?: $rc->get_plan()->get_setting('overwrite_conf')->set_value(true);
        $rc->execute_precheck();
        $rc->execute_plan();

        $course = $DB->get_record('course', array('id' => $rc->get_courseid()));

        $rc->destroy();
        unset($rc);
        return $course;
    }

    /**
     * Restore a course to an existing course.
     *
     * @param int $backupid The backup ID.
     * @param int $courseid The course ID to restore in.
     * @param int $userid The ID of the user performing the restore.
     * @return stdClass The updated course object.
     */
    protected function restore_to_existing_course($backupid, $courseid, $userid = 2) {
        return $this->restore_course($backupid, $courseid, $userid);
    }

    /**
     * Restore a course to a new course.
     *
     * @param int $backupid The backup ID.
     * @param int $userid The ID of the user performing the restore.
     * @return stdClass The new course object.
     */
    protected function restore_to_new_course($backupid, $userid = 2) {
        return $this->restore_course($backupid, 0, $userid);
    }

    public function test_restore_existing_idnumber_in_new_course() {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $backupid = $this->backup_course($c1->id);
        $c2 = $this->restore_to_new_course($backupid);

        // The ID number is set empty.
        $this->assertEquals('', $c2->idnumber);
    }

    public function test_restore_non_existing_idnumber_in_new_course() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $backupid = $this->backup_course($c1->id);

        $c1->idnumber = 'BCD';
        $DB->update_record('course', $c1);

        // The ID number changed.
        $c2 = $this->restore_to_new_course($backupid);
        $this->assertEquals('ABC', $c2->idnumber);
    }

    public function test_restore_existing_idnumber_in_existing_course() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $c2 = $dg->create_course(['idnumber' => 'DEF']);
        $backupid = $this->backup_course($c1->id);

        // The ID number does not change.
        $c2 = $this->restore_to_existing_course($backupid, $c2->id);
        $this->assertEquals('DEF', $c2->idnumber);

        $c1 = $DB->get_record('course', array('id' => $c1->id));
        $this->assertEquals('ABC', $c1->idnumber);
    }

    public function test_restore_non_existing_idnumber_in_existing_course() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $c2 = $dg->create_course(['idnumber' => 'DEF']);
        $backupid = $this->backup_course($c1->id);

        $c1->idnumber = 'XXX';
        $DB->update_record('course', $c1);

        // The ID number has changed.
        $c2 = $this->restore_to_existing_course($backupid, $c2->id);
        $this->assertEquals('ABC', $c2->idnumber);
    }

    public function test_restore_idnumber_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();

        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/course:changeidnumber', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        $c1 = $dg->create_course(['idnumber' => 'ABC']);
        $c2 = $dg->create_course(['idnumber' => 'DEF']);
        $backupid = $this->backup_course($c1->id);

        $c1->idnumber = 'XXX';
        $DB->update_record('course', $c1);

        // The ID number does not change.
        $c2 = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals('DEF', $c2->idnumber);
    }

    public function test_restore_course_info_in_new_course() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $backupid = $this->backup_course($c1->id);

        // The information is restored but adapted because names are already taken.
        $c2 = $this->restore_to_new_course($backupid);
        $this->assertEquals('SN_1', $c2->shortname);
        $this->assertEquals('FN copy 1', $c2->fullname);
        $this->assertEquals('DESC', $c2->summary);
        $this->assertEquals(FORMAT_MOODLE, $c2->summaryformat);
    }

    public function test_restore_course_info_in_existing_course() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $c2 = $dg->create_course(['shortname' => 'A', 'fullname' => 'B', 'summary' => 'C', 'summaryformat' => FORMAT_PLAIN]);
        $backupid = $this->backup_course($c1->id);

        // The information is restored but adapted because names are already taken.
        $c2 = $this->restore_to_existing_course($backupid, $c2->id);
        $this->assertEquals('SN_1', $c2->shortname);
        $this->assertEquals('FN copy 1', $c2->fullname);
        $this->assertEquals('DESC', $c2->summary);
        $this->assertEquals(FORMAT_MOODLE, $c2->summaryformat);
    }

    public function test_restore_course_shortname_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();

        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/course:changeshortname', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $c2 = $dg->create_course(['shortname' => 'A1', 'fullname' => 'B1', 'summary' => 'C1', 'summaryformat' => FORMAT_PLAIN]);

        // The shortname does not change.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals($c2->shortname, $restored->shortname);
        $this->assertEquals('FN copy 1', $restored->fullname);
        $this->assertEquals('DESC', $restored->summary);
        $this->assertEquals(FORMAT_MOODLE, $restored->summaryformat);
    }

    public function test_restore_course_fullname_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();

        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/course:changefullname', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $c2 = $dg->create_course(['shortname' => 'A1', 'fullname' => 'B1', 'summary' => 'C1', 'summaryformat' => FORMAT_PLAIN]);

        // The fullname does not change.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals('SN_1', $restored->shortname);
        $this->assertEquals($c2->fullname, $restored->fullname);
        $this->assertEquals('DESC', $restored->summary);
        $this->assertEquals(FORMAT_MOODLE, $restored->summaryformat);
    }

    public function test_restore_course_summary_in_existing_course_without_permissions() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $u1 = $dg->create_user();

        $managers = get_archetype_roles('manager');
        $manager = array_shift($managers);
        $roleid = $this->create_role_with_caps('moodle/course:changesummary', CAP_PROHIBIT);
        $dg->role_assign($manager->id, $u1->id);
        $dg->role_assign($roleid, $u1->id);

        $c1 = $dg->create_course(['shortname' => 'SN', 'fullname' => 'FN', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $c2 = $dg->create_course(['shortname' => 'A1', 'fullname' => 'B1', 'summary' => 'C1', 'summaryformat' => FORMAT_PLAIN]);

        // The summary and format do not change.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id, $u1->id);
        $this->assertEquals('SN_1', $restored->shortname);
        $this->assertEquals('FN copy 1', $restored->fullname);
        $this->assertEquals($c2->summary, $restored->summary);
        $this->assertEquals($c2->summaryformat, $restored->summaryformat);
    }

    // TOTARA - Test rpl is correctly backed up and restored.
    public function test_restore_course_completion_data() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');

        // Set up some courses.
        $c1 = $generator->create_course(['shortname' => 'origin', 'fullname' => 'Original Course', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $completion_generator->enable_completion_tracking($c1);
        $compinfo1 = new completion_info($c1);
        $c2 = $generator->create_course(['shortname' => 'restore', 'fullname' => 'Restored Course', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $completion_generator->enable_completion_tracking($c2);
        $compinfo2 = new completion_info($c2);
        $this->assertEquals(COMPLETION_ENABLED, $compinfo1->is_enabled());
        $this->assertEquals(COMPLETION_ENABLED, $compinfo2->is_enabled());

        // Set up and enrol some users.
        $u1 = $generator->create_user(); // Course Complete
        $u2 = $generator->create_user(); // Course RPL
        $u3 = $generator->create_user(); // Activity Complete
        $u4 = $generator->create_user(); // Activity RPL
        $u5 = $generator->create_user(); // Control
        $generator->enrol_user($u1->id, $c1->id);
        $generator->enrol_user($u2->id, $c1->id);
        $generator->enrol_user($u3->id, $c1->id);
        $generator->enrol_user($u4->id, $c1->id);
        $generator->enrol_user($u5->id, $c1->id);

        // Create an activity with completion and set it as a course criteria.
        $completiondefaults = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED
        );
        $act1 = $generator->create_module('certificate', array('course' => $c1->id), $completiondefaults);
        $cm1 = get_coursemodule_from_instance('certificate', $act1->id, $c1->id);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $compinfo1->is_enabled($cm1));

        $data = new stdClass();
        $data->course = $c1->id;
        $data->id = $c1->id;
        $data->overall_aggregation = COMPLETION_AGGREGATION_ANY;
        $data->criteria_activity_value = array($act1->id => 1);
        $criterion = new completion_criteria_activity();
        $criterion->update_config($data);
        $criterion->id = $DB->get_field('course_completion_criteria', 'id', array('course' => $c1->id));

        // Create some dummy completion and RPL data for the course.
        $now = time();
        $comp1 = $DB->get_record('course_completions', array('userid' => $u1->id, 'course' => $c1->id));
        $comp1->status = COMPLETION_STATUS_COMPLETE; # completion/completion_completion.php
        $comp1->timecompleted = $now;
        $DB->update_record('course_completions', $comp1);
        $comp2 = $DB->get_record('course_completions', array('userid' => $u2->id, 'course' => $c1->id));
        $comp2->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $comp2->timecompleted = $now;
        $comp2->rpl = 'RippleCrs';
        $comp2->rplgrade = 7.5;
        $DB->update_record('course_completions', $comp2);

        // Create some dummy completion and RPL data for the activity.
        $compinfo1->set_module_viewed($cm1, $u3->id);
        $crit1 = new stdClass();
        $crit1->userid = $u3->id;
        $crit1->course = $c1->id;
        $crit1->criteriaid = $criterion->id;
        $crit1->timecompleted = $now;
        $DB->insert_record('course_completion_crit_compl', $crit1);

        $crit2 = new stdClass();
        $crit2->userid = $u4->id;
        $crit2->course = $c1->id;
        $crit2->criteriaid = $criterion->id;
        $crit2->timecompleted = $now;
        $crit2->rpl = 'RippleAct';
        $DB->insert_record('course_completion_crit_compl', $crit2);

        // Backup and restore course 1 into course 2.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id);

        // Test the data.
        $compcrs1 = $DB->get_record('course_completions', array('userid' => $u1->id, 'course' => $c2->id));
        $this->assertEquals(COMPLETION_STATUS_COMPLETE, $compcrs1->status);
        $this->assertEquals($now, $compcrs1->timecompleted);
        $this->assertEmpty($compcrs1->rpl);
        $this->assertEmpty($compcrs1->rplgrade);

        $compcrs2 = $DB->get_record('course_completions', array('userid' => $u2->id, 'course' => $c2->id));
        $this->assertEquals(COMPLETION_STATUS_COMPLETEVIARPL, $compcrs2->status);
        $this->assertEquals($now, $compcrs2->timecompleted);
        $this->assertEquals('RippleCrs', $compcrs2->rpl);
        $this->assertEquals(7.5, $compcrs2->rplgrade);

        $compact3 = $DB->get_record('course_completion_crit_compl', array('userid' => $u3->id, 'course' => $c2->id));
        $this->assertEquals($now, $compact3->timecompleted);
        $this->assertEmpty($compact3->rpl);

        $compact4 = $DB->get_record('course_completion_crit_compl', array('userid' => $u4->id, 'course' => $c2->id));
        $this->assertEquals($now, $compact4->timecompleted);
        $this->assertEquals('RippleAct', $compact4->rpl);

        $compcrs5 = $DB->get_record('course_completions', array('userid' => $u5->id, 'course' => $c2->id));
        $this->assertEmpty($compcrs5->timecompleted);
        $this->assertEmpty($compcrs5->rpl);
        $compact5 = $DB->get_record('course_completion_crit_compl', array('userid' => $u5->id, 'course' => $c2->id));
        $this->assertFalse($compact5);
    }

    public function test_restore_course_completion_history_data() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');

        // Set up some courses.
        $c1 = $generator->create_course(['shortname' => 'origin', 'fullname' => 'Original Course', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $completion_generator->enable_completion_tracking($c1);
        $compinfo1 = new completion_info($c1);
        $c2 = $generator->create_course(['shortname' => 'restore', 'fullname' => 'Restored Course', 'summary' => 'DESC', 'summaryformat' => FORMAT_MOODLE]);
        $completion_generator->enable_completion_tracking($c2);
        $compinfo2 = new completion_info($c2);
        $this->assertEquals(COMPLETION_ENABLED, $compinfo1->is_enabled());
        $this->assertEquals(COMPLETION_ENABLED, $compinfo2->is_enabled());

        // Set up and enrol some users.
        $u1 = $generator->create_user(); // Course Complete
        $u2 = $generator->create_user(); // Course Complete + History
        $u3 = $generator->create_user(); // Course Complete + Multiple History
        $u4 = $generator->create_user(); // Control
        $generator->enrol_user($u1->id, $c1->id);
        $generator->enrol_user($u2->id, $c1->id);
        $generator->enrol_user($u3->id, $c1->id);
        $generator->enrol_user($u4->id, $c1->id);

        // Create some dummy completion and RPL data for the course.
        $now = time();
        $comp1 = $DB->get_record('course_completions', array('userid' => $u1->id, 'course' => $c1->id));
        $comp1->status = COMPLETION_STATUS_COMPLETE; # completion/completion_completion.php
        $comp1->timecompleted = $now;
        $DB->update_record('course_completions', $comp1);
        $comp2 = $DB->get_record('course_completions', array('userid' => $u2->id, 'course' => $c1->id));
        $comp2->status = COMPLETION_STATUS_COMPLETE;
        $comp2->timecompleted = $now;
        $DB->update_record('course_completions', $comp2);
        $hist1 = ['userid' => $u2->id, 'courseid' => $c1->id, 'timecompleted' => 1234567890, 'grade' => 6.4];
        $DB->insert_record('course_completion_history', $hist1);
        $comp3 = $DB->get_record('course_completions', array('userid' => $u3->id, 'course' => $c1->id));
        $comp3->status = COMPLETION_STATUS_COMPLETE;
        $comp3->timecompleted = $now;
        $DB->update_record('course_completions', $comp3);
        $hist2 = ['userid' => $u3->id, 'courseid' => $c1->id, 'timecompleted' => 1234567890, 'grade' => 5.4];
        $DB->insert_record('course_completion_history', $hist2);
        $hist3 = ['userid' => $u3->id, 'courseid' => $c1->id, 'timecompleted' => 1324567890, 'grade' => 7.4];
        $DB->insert_record('course_completion_history', $hist3);

        // Backup and restore course 1 into course 2.
        $backupid = $this->backup_course($c1->id);
        $restored = $this->restore_to_existing_course($backupid, $c2->id);

        // Test the data.
        $compcrs1 = $DB->get_record('course_completions', array('userid' => $u1->id, 'course' => $c2->id));
        $this->assertEquals(COMPLETION_STATUS_COMPLETE, $compcrs1->status);
        $this->assertEquals($now, $compcrs1->timecompleted);
        $this->assertEmpty($compcrs1->rpl);
        $this->assertEmpty($compcrs1->rplgrade);
        $u1hist = $DB->get_records('course_completion_history', array('courseid' => $c2->id, 'userid' => $u1->id));
        $this->assertCount(0, $u1hist);

        $compcrs2 = $DB->get_record('course_completions', array('userid' => $u2->id, 'course' => $c2->id));
        $this->assertEquals(COMPLETION_STATUS_COMPLETE, $compcrs2->status);
        $this->assertEquals($now, $compcrs2->timecompleted);
        $this->assertEmpty($compcrs2->rpl);
        $this->assertEmpty($compcrs2->rplgrade);
        $u2hist = $DB->get_records('course_completion_history', array('courseid' => $c2->id, 'userid' => $u2->id));
        $this->assertCount(1, $u2hist);
        $u2hist = array_pop($u2hist);
        $this->assertEquals('1234567890', $u2hist->timecompleted);
        $this->assertEquals(6.4, $u2hist->grade);

        $compcrs3 = $DB->get_record('course_completions', array('userid' => $u3->id, 'course' => $c2->id));
        $this->assertEquals(COMPLETION_STATUS_COMPLETE, $compcrs3->status);
        $this->assertEquals($now, $compcrs3->timecompleted);
        $this->assertEmpty($compcrs3->rpl);
        $this->assertEmpty($compcrs3->rplgrade);
        $u3hist = $DB->get_records('course_completion_history', array('courseid' => $c2->id, 'userid' => $u3->id));
        $this->assertCount(2, $u3hist);
        foreach ($u3hist as $histrec) {
            if ($histrec->grade == 5.4) {
                $this->assertEquals('1234567890', $histrec->timecompleted);
            } else if ($histrec->grade == 7.4) {
                $this->assertEquals('1324567890', $histrec->timecompleted);
            } else {
                $this->assertTrue(false, 'Unexpected history record for user3');
            }
        }

        $compcrs4 = $DB->get_record('course_completions', array('userid' => $u4->id, 'course' => $c2->id));
        $this->assertEquals(COMPLETION_STATUS_NOTYETSTARTED, $compcrs4->status);
        $this->assertEmpty($compcrs4->timecompleted);
        $this->assertEmpty($compcrs4->rpl);
        $this->assertEmpty($compcrs4->rplgrade);
        $u4hist = $DB->get_records('course_completion_history', array('courseid' => $c2->id, 'userid' => $u4->id));
        $this->assertCount(0, $u4hist);
    }

    public function test_restore_custom_role_names() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        // Set up a course.
        $course = $generator->create_course(array(
            'shortname' => 'origin',
            'fullname' => 'Original Course',
            'summary' => 'DESC',
            'summaryformat' => FORMAT_MOODLE,
        ));
        $coursecontext = context_course::instance($course->id);

        // Customise the names.
        $rolemanager = $DB->get_record('role', array('shortname' => 'manager'));
        $roleteacher = $DB->get_record('role', array('shortname' => 'teacher'));
        $rolestudent = $DB->get_record('role', array('shortname' => 'student'));
        save_local_role_names($course->id, array(
            'role_' . $rolemanager->id => 'custom manager name',
            'role_' . $roleteacher->id => 'custom teacher name',
            'role_' . $rolestudent->id => 'custom student name',
        ));

        $this->assertEquals(3, $DB->count_records('role_names'));

        // Backup.
        $backupid = $this->backup_course($course->id);

        // Change the custom role names of the original course.
        $rolemanager = $DB->get_record('role', array('shortname' => 'manager'));
        $roleteacher = $DB->get_record('role', array('shortname' => 'teacher'));
        $rolestudent = $DB->get_record('role', array('shortname' => 'student'));
        save_local_role_names($course->id, array(
            'role_' . $rolemanager->id => 'renamed manager name',
            'role_' . $roleteacher->id => 'renamed teacher name',
            'role_' . $rolestudent->id => 'renamed student name',
        ));

        // Restore into a new course.
        $newcourse = $this->restore_to_new_course($backupid);
        $newcoursecontext = context_course::instance($newcourse->id);

        // Test the data.
        $this->assertEquals(6, $DB->count_records('role_names'));

        $this->assertEquals(3, $DB->count_records('role_names',
            array('contextid' => $newcoursecontext->id)));

        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $newcoursecontext->id, 'roleid' => $rolemanager->id, 'name' => 'custom manager name')));
        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $newcoursecontext->id, 'roleid' => $roleteacher->id, 'name' => 'custom teacher name')));
        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $newcoursecontext->id, 'roleid' => $rolestudent->id, 'name' => 'custom student name')));

        $this->assertEquals(3, $DB->count_records('role_names',
            array('contextid' => $coursecontext->id)));

        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $coursecontext->id, 'roleid' => $rolemanager->id, 'name' => 'renamed manager name')));
        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $coursecontext->id, 'roleid' => $roleteacher->id, 'name' => 'renamed teacher name')));
        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $coursecontext->id, 'roleid' => $rolestudent->id, 'name' => 'renamed student name')));

        // Restore over the first course.
        $backupid = $this->backup_course($newcourse->id);
        $this->restore_to_existing_course($backupid, $course->id);

        // Test the data - the 'renamed' have NOT been changed, because we are restoring into an existing course.
        $this->assertEquals(6, $DB->count_records('role_names'));

        $this->assertEquals(3, $DB->count_records('role_names',
            array('contextid' => $coursecontext->id)));

        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $coursecontext->id, 'roleid' => $rolemanager->id, 'name' => 'renamed manager name')));
        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $coursecontext->id, 'roleid' => $roleteacher->id, 'name' => 'renamed teacher name')));
        $this->assertEquals(1, $DB->count_records('role_names',
            array('contextid' => $coursecontext->id, 'roleid' => $rolestudent->id, 'name' => 'renamed student name')));
    }
}
