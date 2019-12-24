<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara
 * @subpackage cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot . '/totara/core/utils.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir  . '/coursecatlib.php');

/**
 * Test audience visibility in courses.
 */
class totara_cohort_course_audiencevisibility_testcase extends reportcache_advanced_testcase {
    /** @var stdClass $user1 */
    private $user1 = null;
    /** @var stdClass $user2 */
    private $user2 = null;
    /** @var stdClass $user3 */
    private $user3 = null;
    /** @var stdClass $user4 */
    private $user4 = null;
    /** @var stdClass $user5 */
    private $user5 = null;
    /** @var stdClass $user6 */
    private $user6 = null;
    /** @var stdClass $user7 */
    private $user7 = null;
    /** @var stdClass $user8 */
    private $user8 = null;
    /** @var stdClass $user9 */
    private $user9 = null;
    /** @var stdClass $user10 */
    private $user10 = null;
    /** @var stdClass $course1 */
    private $course1 = null;
    /** @var stdClass $course2 */
    private $course2 = null;
    /** @var stdClass $course3 */
    private $course3 = null;
    /** @var stdClass $course4 */
    private $course4 = null;
    /** @var stdClass $course5 */
    private $course5 = null;
    /** @var stdClass $course6 */
    private $course6 = null;
    /** @var stdClass $audience1 */
    private $audience1 = null;
    /** @var stdClass $audience2 */
    private $audience2 = null;

    protected function tearDown() {
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->user4 = null;
        $this->user5 = null;
        $this->user6 = null;
        $this->user7 = null;
        $this->user8 = null;
        $this->user9 = null;
        $this->user10 = null;
        $this->course1 = null;
        $this->course2 = null;
        $this->course3 = null;
        $this->course4 = null;
        $this->course5 = null;
        $this->course6 = null;
        $this->audience1 = null;
        $this->audience2 = null;
        parent::tearDown();
    }

    /**
     * Setup.
     */
    protected function setUp() {
        global $DB;
        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create some users.
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();
        $this->user4 = $this->getDataGenerator()->create_user();
        $this->user5 = $this->getDataGenerator()->create_user();
        $this->user6 = $this->getDataGenerator()->create_user();
        $this->user7 = $this->getDataGenerator()->create_user();
        $this->user8 = $this->getDataGenerator()->create_user(); // User with manage audience visibility cap in syscontext.
        $this->user9 = $this->getDataGenerator()->create_user(); // User with view hidden courses cap in syscontext.
        $this->user10 = $this->getDataGenerator()->create_user(); // User with view hidden courses cap in the course context.

        // Create audience1.
        $this->audience1 = $this->getDataGenerator()->create_cohort();
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->audience1->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->audience1->id)));

        // Assign user3 and user4 to the audience1.
        cohort_add_member($this->audience1->id, $this->user3->id);
        cohort_add_member($this->audience1->id, $this->user4->id);
        $this->assertEquals(2, $DB->count_records('cohort_members', array('cohortid' => $this->audience1->id)));

        // Create audience2.
        $this->audience2 = $this->getDataGenerator()->create_cohort();
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->audience2->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->audience2->id)));

        // Assign user5 and user6 to the audience2.
        cohort_add_member($this->audience2->id, $this->user5->id);
        cohort_add_member($this->audience2->id, $this->user6->id);
        $this->assertEquals(2, $DB->count_records('cohort_members', array('cohortid' => $this->audience2->id)));

        // Create 4 courses.
        $paramscourse1 = array('fullname' => 'Visall', 'summary' => '', 'visible' => 0, 'audiencevisible' => COHORT_VISIBLE_ALL);
        $paramscourse2 = array('fullname' => 'Visenronly', 'summary' => '', 'audiencevisible' => COHORT_VISIBLE_ENROLLED);
        $paramscourse3 = array('fullname' => 'Visenrandmemb', 'summary' => '', 'visible' => 0,
                                'audiencevisible' => COHORT_VISIBLE_AUDIENCE);
        $paramscourse4 = array('fullname' => 'Visnousers', 'summary' => '', 'audiencevisible' => COHORT_VISIBLE_NOUSERS);
        $this->course1 = $this->getDataGenerator()->create_course($paramscourse1); // Visibility all.
        $this->course2 = $this->getDataGenerator()->create_course($paramscourse2); // Visibility enrolled users only.
        $this->course3 = $this->getDataGenerator()->create_course($paramscourse3); // Visibility enrolled users and members.
        $this->course4 = $this->getDataGenerator()->create_course($paramscourse4); // Visibility no users.

        // Enrol user1 into course1 visible to all.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id);

        // Enrol user1 and user2 into course2 visible to enrolled users only.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course2->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course2->id);

        // Enrol user2 into course3 visible to enrolled and members.
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course3->id);

        // Enrol user1 and user2 into course3 visible to no users.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course4->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course4->id);

        // Assign capabilities for user8, user9 and user10.
        $syscontext = context_system::instance();
        $rolestaffmanager = $DB->get_record('role', array('shortname'=>'staffmanager'));
        role_assign($rolestaffmanager->id, $this->user8->id, $syscontext->id);
        assign_capability('totara/coursecatalog:manageaudiencevisibility', CAP_ALLOW, $rolestaffmanager->id, $syscontext);
        unassign_capability('moodle/course:viewhiddencourses', $rolestaffmanager->id, $syscontext->id);

        $roletrainer = $DB->get_record('role', array('shortname'=>'teacher'));
        role_assign($roletrainer->id, $this->user9->id, $syscontext->id);
        assign_capability('moodle/course:viewhiddencourses', CAP_ALLOW, $roletrainer->id, $syscontext);

        $roleeditingtrainer = $DB->get_record('role', array('shortname'=>'editingteacher'));
        $manualplugin = enrol_get_plugin('manual');
        $maninstance = $DB->get_record('enrol', array('courseid'=>$this->course3->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $manualplugin->enrol_user($maninstance, $this->user10->id, $roleeditingtrainer->id);

        // Assign audience1 and audience2 to course2.
        totara_cohort_add_association($this->audience1->id, $this->course2->id,
                                        COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
        totara_cohort_add_association($this->audience2->id, $this->course2->id,
                                        COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);

        // Assign audience2 to course3 and course4.
        totara_cohort_add_association($this->audience2->id, $this->course3->id,
                                        COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
        totara_cohort_add_association($this->audience2->id, $this->course4->id,
                                        COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);

        // Check the assignments were created correctly.
        $params = array('cohortid' => $this->audience1->id, 'instanceid' => $this->course2->id,
                            'instancetype' => COHORT_ASSN_ITEMTYPE_COURSE);
        $this->assertTrue($DB->record_exists('cohort_visibility', $params));
        $params = array('cohortid' => $this->audience2->id, 'instanceid' => $this->course2->id,
                            'instancetype' => COHORT_ASSN_ITEMTYPE_COURSE);
        $this->assertTrue($DB->record_exists('cohort_visibility', $params));
        $params = array('cohortid' => $this->audience2->id, 'instanceid' => $this->course3->id,
                            'instancetype' => COHORT_ASSN_ITEMTYPE_COURSE);
        $this->assertTrue($DB->record_exists('cohort_visibility', $params));
        $params = array('cohortid' => $this->audience2->id, 'instanceid' => $this->course4->id,
                            'instancetype' => COHORT_ASSN_ITEMTYPE_COURSE);
        $this->assertTrue($DB->record_exists('cohort_visibility', $params));
    }

    /**
     * Data provider for the audiencevisibility function.
     *
     * @return array $data Data to be used by test_audiencevisibility.
     */
    public function users_audience_visibility() {
        $data = array(
            array('user' => 'user1', array('course1', 'course2'), array('course3', 'course4'), 1),
            array('user' => 'user2', array('course1', 'course2', 'course3'), array('course4'), 1),
            array('user' => 'user3', array('course1'), array('course2', 'course3', 'course4'), 1),
            array('user' => 'user4', array('course1'), array('course2', 'course3', 'course4'), 1),
            array('user' => 'user5', array('course1', 'course3'), array('course2', 'course4'), 1),
            array('user' => 'user6', array('course1', 'course3'), array('course2', 'course4'), 1),
            array('user' => 'user7', array('course1'), array('course2', 'course3', 'course4'), 1),
            array('user' => 'user8', array('course1', 'course2', 'course3', 'course4'), array(), 1),
            array('user' => 'user9', array('course1', 'course2', 'course3', 'course4'), array(), 1),
            array('user' => 'user10', array('course1', 'course3'), array('course2', 'course4'), 1),
            array('user' => 'user1', array('course2', 'course4', 'course5'), array('course1', 'course3', 'course6'), 0),
            array('user' => 'user2', array('course2', 'course4', 'course5'), array('course1', 'course3', 'course6'), 0),
            array('user' => 'user3', array('course2', 'course4', 'course5'), array('course1', 'course3', 'course6'), 0),
            array('user' => 'user5', array('course2', 'course4', 'course5'), array('course1', 'course3', 'course6'), 0),
            array('user' => 'user7', array('course2', 'course4', 'course5'), array('course1', 'course3', 'course6'), 0),
            array('user' => 'user8', array('course2', 'course4'), array('course1', 'course3', 'course6'), 0),
            array('user' => 'user9', array('course2', 'course4', 'course1', 'course3', 'course6'), array(), 0),
            array('user' => 'user10', array('course2', 'course4', 'course3'), array('course1', 'course6'), 0),
        );
        return $data;
    }

    /**
     * Test Audicence visibility.
     * @param string $user User that will login to see the courses
     * @param array $coursesvisible Array of courses visible to the user
     * @param array $coursesnotvisible Array of courses not visible to the user
     * @param bool $audvisibilityon Setting for audience visibility (1 => ON, 0 => OFF)
     * @dataProvider users_audience_visibility
     */
    public function test_audiencevisibility($user, $coursesvisible, $coursesnotvisible, $audvisibilityon) {
        global $PAGE, $CFG;
        $this->resetAfterTest(true);

        // Set audiencevisibility setting.
        set_config('audiencevisibility', $audvisibilityon);
        $this->assertEquals($CFG->audiencevisibility, $audvisibilityon);

        if (!$audvisibilityon) {
            // Create new courses and enrol users to them.
            $this->create_courses_old_visibility();
        }

        $user = $this->{$user};

        // Make the test toggling the new catalog.
        for ($i = 1; $i <= 2; $i++) {
            // Toggle enhanced catalog.
            $newvalue = ($CFG->enhancedcatalog == 1) ? 0 : 1;
            set_config('enhancedcatalog', $newvalue);
            $this->assertEquals($CFG->enhancedcatalog, $newvalue);

            // Test #1: Login as $user and see what courses he can see.
            self::setUser($user);
            if ($CFG->enhancedcatalog) {
                $content = $this->get_report_result('catalogcourses', array(), false, array());
            } else {
                /** @var core_course_renderer $courserenderer */
                $courserenderer = $PAGE->get_renderer('core', 'course');
                $content = $courserenderer->course_category(0);
            }

            // Courses visible to the user.
            foreach ($coursesvisible as $course) {
                list($visible, $access, $search) = $this->get_visible_info($CFG->audiencevisibility, $content, $this->{$course});
                $this->assertTrue($visible);
                // Test #2: Try to access them.
                $this->assertTrue($access);
                // Test #3: Try to do a search for courses.
                if ($CFG->enhancedcatalog) {
                    $this->assertCount(1, $search);
                    $r = array_shift($search);
                    $this->assertEquals($this->{$course}->fullname, $r->course_courseexpandlink);
                } else {
                    $this->assertInternalType('int', strpos($search, $this->{$course}->fullname));
                }
            }

            // Courses not visible to the user.
            foreach ($coursesnotvisible as $course) {
                list($visible, $access, $search) = $this->get_visible_info($CFG->audiencevisibility, $content, $this->{$course});
                $this->assertFalse($visible);
                // Test #2: Try to access them.
                $this->assertFalse($access);
                // Test #3: Try to do a search for courses.
                if ($CFG->enhancedcatalog) {
                    $this->assertCount(0, $search);
                } else {
                    $this->assertInternalType('int', strpos($search, 'No courses were found'));
                }
            }

            // Repeat as different user.
            $this->setGuestUser();
            // Courses visible to the user.
            foreach ($coursesvisible as $course) {
                list($visible, $access, $search) = $this->get_visible_info($CFG->audiencevisibility, $content, $this->{$course}, $user->id);
                $this->assertTrue($visible);
                // Test #2: Try to access them.
                $this->assertTrue($access);
                // Test #3: Try to do a search for courses.
                if ($CFG->enhancedcatalog) {
                    $this->assertCount(1, $search);
                    $r = array_shift($search);
                    $this->assertEquals($this->{$course}->fullname, $r->course_courseexpandlink);
                } else {
                    $this->assertInternalType('int', strpos($search, $this->{$course}->fullname));
                }
            }

            // Courses not visible to the user.
            foreach ($coursesnotvisible as $course) {
                list($visible, $access, $search) = $this->get_visible_info($CFG->audiencevisibility, $content, $this->{$course}, $user->id);
                $this->assertFalse($visible);
                // Test #2: Try to access them.
                $this->assertFalse($access);
                // Test #3: Try to do a search for courses.
                if ($CFG->enhancedcatalog) {
                    $this->assertCount(0, $search);
                } else {
                    $this->assertInternalType('int', strpos($search, 'No courses were found'));
                }
            }
        }
    }

    /**
     * Determine visibility of a course based on the content.
     * @param bool $audiencevisibility
     * @param array $content Content when a user access to find certifications
     * @param stdClass $course The course to evaluate
     * @param int $userid
     * @return array Array that contains values related to the visibility of the course
     */
    protected function get_visible_info($audiencevisibility, $content, $course, $userid = null) {
        global $PAGE, $CFG;
        $visible = false;

        if ($audiencevisibility) {
            $access = check_access_audience_visibility('course', $course, $userid);
        } else {
            $access = $course->visible ||
                has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id), $userid);
        }

        if ($CFG->enhancedcatalog) { // New catalog.
            $search = array();
            if (is_array($content)) {
                $search = totara_search_for_value($content, 'course_courseexpandlink', TOTARA_SEARCH_OP_EQUAL, $course->fullname);
                $visible = !empty($search);
            }
        } else { // Old Catalog.
            $visible = (strpos($content, $course->fullname) != false);
            /** @var core_course_renderer $courserenderer */
            $courserenderer = $PAGE->get_renderer('core', 'course');
            $search = $courserenderer->search_courses(array('search' => $course->fullname));
        }

        return array($visible, $access, $search);
    }

    /**
     * Create courses with old visibility.
     */
    protected function create_courses_old_visibility() {
        // Create course with old visibility.
        $paramscourse1 = array('fullname' => 'course5', 'summary' => '', 'visible' => 1);
        $paramscourse2 = array('fullname' => 'course6', 'summary' => '', 'visible' => 0);
        $this->course5 = $this->getDataGenerator()->create_course($paramscourse1); // Visible.
        $this->course6 = $this->getDataGenerator()->create_course($paramscourse2); // Invisible.
        // Enrol users to the courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course5->id);
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course6->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course6->id);
        // Assign audience1 and audience2 to course6 and course 5 respectively.
        totara_cohort_add_association($this->audience2->id, $this->course6->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
        totara_cohort_add_association($this->audience1->id, $this->course5->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
    }
}
