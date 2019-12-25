<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package mod_feedback
 * @category phpunit
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once($CFG->dirroot . '/mod/feedback/backup/moodle2/backup_feedback_activity_task.class.php');


/**
 * Test the feedback backup activity task methods.
 */
class mod_feedback_backup_activity_task_testcase extends advanced_testcase {

    /**
     * Tests calling encode_content_links without content that should have no matches.
     */
    public function test_encode_content_links_with_no_matches() {
        global $CFG;
        // First up test things that should not lead to replacement.
        $this->assertSame('', backup_feedback_activity_task::encode_content_links(''));
        $this->assertSame('Test', backup_feedback_activity_task::encode_content_links('Test'));
        $this->assertSame($CFG->wwwroot, backup_feedback_activity_task::encode_content_links($CFG->wwwroot));
        $this->assertSame(
            "<a href='{$CFG->wwwroot}'>wwwroot</a>",
            backup_feedback_activity_task::encode_content_links("<a href='{$CFG->wwwroot}'>wwwroot</a>")
        );
    }

    /**
     * Test encode_content_links can convert index links when called without a task.
     */
    public function test_encode_content_links_index_without_a_task() {
        global $CFG;

        // Test index.php links.
        $this->assertSame(
            '$@FEEDBACKINDEX*3@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/index.php?id=3')
        );
        $this->assertSame(
            '$@FEEDBACKINDEX*987654321@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/index.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@FEEDBACKINDEX*987654321@$">$@FEEDBACKINDEX*987654321@$</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/index.php?id=987654321">'.$CFG->wwwroot.'/mod/feedback/index.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/feedback/index.php?id=64">/mod/feedback/index.php?id=64</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="/mod/feedback/index.php?id=64">/mod/feedback/index.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FEEDBACKINDEX*987654321@$#anchor">$@FEEDBACKINDEX*987654321@$#anchor</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/index.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/feedback/index.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FEEDBACKINDEX*987654321@$&arg=value">$@FEEDBACKINDEX*987654321@$&arg=value</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/index.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/feedback/index.php?id=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert index links when called with a valid backup task.
     */
    public function test_encode_content_links_index_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('feedback', array('course' => $course1->id));
        $module2 = $generator->create_module('feedback', array('course' => $course2->id));

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course1->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
                                    backup::MODE_IMPORT, $USER->id);
        $tasks = $bc->get_plan()->get_tasks();

        // We need a task to test with, it doesn't matter which, but we'll use the root task.
        $roottask = null;
        foreach ($tasks as $task) {
            if ($task instanceof backup_root_task) {
                $roottask = $task;
                break;
            }
        }
        $this->assertNotEmpty($roottask, 'Unable to find the root backup task');

        // We expect the module in course 1 to be encoded, but not the module in course 2.
        $this->assertSame(
            '$@FEEDBACKINDEX*'.$course1->id.'@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/feedback/index.php?id='.$course2->id,
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/feedback/index.php?id=987654321',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/index.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/feedback/index.php?id='.$course1->id,
            backup_feedback_activity_task::encode_content_links('/mod/feedback/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            '/mod/feedback/index.php?id='.$course2->id,
            backup_feedback_activity_task::encode_content_links('/mod/feedback/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            '/mod/feedback/index.php?id=987654321',
            backup_feedback_activity_task::encode_content_links('/mod/feedback/index.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/feedback/index.php?id='.$course1->id.'#anchor';
        $this->assertSame(
            '<a href="$@FEEDBACKINDEX*'.$course1->id.'@$#anchor">$@FEEDBACKINDEX*'.$course1->id.'@$#anchor</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/index.php?id='.$course2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/index.php?id='.$course1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@FEEDBACKINDEX*'.$course1->id.'@$&arg=value">$@FEEDBACKINDEX*'.$course1->id.'@$&arg=value</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/index.php?id='.$course2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@FEEDBACKVIEWBYID*3@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/view.php?id=3')
        );
        $this->assertSame(
            '$@FEEDBACKVIEWBYID*987654321@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/view.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@FEEDBACKVIEWBYID*987654321@$">$@FEEDBACKVIEWBYID*987654321@$</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/view.php?id=987654321">'.$CFG->wwwroot.'/mod/feedback/view.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/feedback/view.php?id=64">/mod/feedback/view.php?id=64</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="/mod/feedback/view.php?id=64">/mod/feedback/view.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FEEDBACKVIEWBYID*987654321@$#anchor">$@FEEDBACKVIEWBYID*987654321@$#anchor</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/view.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/feedback/view.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FEEDBACKVIEWBYID*987654321@$&arg=value">$@FEEDBACKVIEWBYID*987654321@$&arg=value</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/view.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/feedback/view.php?id=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert view links when called with a valid task.
     */
    public function test_encode_content_links_view_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('feedback', array('course' => $course1->id));
        $module2 = $generator->create_module('feedback', array('course' => $course2->id));

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course1->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
            backup::MODE_IMPORT, $USER->id);
        $tasks = $bc->get_plan()->get_tasks();

        // We need a task to test with, it doesn't matter which, but we'll use the root task.
        $roottask = null;
        foreach ($tasks as $task) {
            if ($task instanceof backup_root_task) {
                $roottask = $task;
                break;
            }
        }
        $this->assertNotEmpty($roottask, 'Unable to find the root backup task');

        // We expect the module in course 1 to be encoded, but not the module in course 2.
        $this->assertSame(
            '$@FEEDBACKVIEWBYID*'.$module1->cmid.'@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/feedback/view.php?id='.$module2->cmid,
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/feedback/view.php?id=987654321',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/view.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/feedback/view.php?id='.$module1->cmid,
            backup_feedback_activity_task::encode_content_links('/mod/feedback/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/feedback/view.php?id='.$module2->cmid,
            backup_feedback_activity_task::encode_content_links('/mod/feedback/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/feedback/view.php?id=987654321',
            backup_feedback_activity_task::encode_content_links('/mod/feedback/view.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/feedback/view.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@FEEDBACKVIEWBYID*'.$module1->cmid.'@$#anchor">$@FEEDBACKVIEWBYID*'.$module1->cmid.'@$#anchor</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/view.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/view.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/view.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@FEEDBACKVIEWBYID*'.$module1->cmid.'@$&arg=value">$@FEEDBACKVIEWBYID*'.$module1->cmid.'@$&arg=value</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/view.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/view.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/feedback/view.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert analysis links when called without a task.
     */
    public function test_encode_content_links_analysis_without_a_task() {
        global $CFG;

        // Test analysis.php links.
        $this->assertSame(
            '$@FEEDBACKANALYSISBYID*3@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/analysis.php?id=3')
        );
        $this->assertSame(
            '$@FEEDBACKANALYSISBYID*987654321@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@FEEDBACKANALYSISBYID*987654321@$">$@FEEDBACKANALYSISBYID*987654321@$</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321">'.$CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/feedback/analysis.php?id=64">/mod/feedback/analysis.php?id=64</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="/mod/feedback/analysis.php?id=64">/mod/feedback/analysis.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FEEDBACKANALYSISBYID*987654321@$#anchor">$@FEEDBACKANALYSISBYID*987654321@$#anchor</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FEEDBACKANALYSISBYID*987654321@$&arg=value">$@FEEDBACKANALYSISBYID*987654321@$&arg=value</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert analysis links when called with a valid task.
     */
    public function test_encode_content_links_analysis_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('feedback', array('course' => $course1->id));
        $module2 = $generator->create_module('feedback', array('course' => $course2->id));

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course1->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
            backup::MODE_IMPORT, $USER->id);
        $tasks = $bc->get_plan()->get_tasks();

        // We need a task to test with, it doesn't matter which, but we'll use the root task.
        $roottask = null;
        foreach ($tasks as $task) {
            if ($task instanceof backup_root_task) {
                $roottask = $task;
                break;
            }
        }
        $this->assertNotEmpty($roottask, 'Unable to find the root backup task');

        // We expect the module in course 1 to be encoded, but not the module in course 2.
        $this->assertSame(
            '$@FEEDBACKANALYSISBYID*'.$module1->cmid.'@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/analysis.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/feedback/analysis.php?id='.$module2->cmid,
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/analysis.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/analysis.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/feedback/analysis.php?id='.$module1->cmid,
            backup_feedback_activity_task::encode_content_links('/mod/feedback/analysis.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/feedback/analysis.php?id='.$module2->cmid,
            backup_feedback_activity_task::encode_content_links('/mod/feedback/analysis.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/feedback/analysis.php?id=987654321',
            backup_feedback_activity_task::encode_content_links('/mod/feedback/analysis.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/feedback/analysis.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@FEEDBACKANALYSISBYID*'.$module1->cmid.'@$#anchor">$@FEEDBACKANALYSISBYID*'.$module1->cmid.'@$#anchor</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/analysis.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/analysis.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/analysis.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@FEEDBACKANALYSISBYID*'.$module1->cmid.'@$&arg=value">$@FEEDBACKANALYSISBYID*'.$module1->cmid.'@$&arg=value</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/analysis.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/analysis.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/feedback/analysis.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert show_entries links when called without a task.
     */
    public function test_encode_content_links_show_entries_without_a_task() {
        global $CFG;

        // Test show_entries.php links.
        $this->assertSame(
            '$@FEEDBACKSHOWENTRIESBYID*3@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/show_entries.php?id=3')
        );
        $this->assertSame(
            '$@FEEDBACKSHOWENTRIESBYID*987654321@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@FEEDBACKSHOWENTRIESBYID*987654321@$">$@FEEDBACKSHOWENTRIESBYID*987654321@$</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321">'.$CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/feedback/show_entries.php?id=64">/mod/feedback/show_entries.php?id=64</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="/mod/feedback/show_entries.php?id=64">/mod/feedback/show_entries.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FEEDBACKSHOWENTRIESBYID*987654321@$#anchor">$@FEEDBACKSHOWENTRIESBYID*987654321@$#anchor</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FEEDBACKSHOWENTRIESBYID*987654321@$&arg=value">$@FEEDBACKSHOWENTRIESBYID*987654321@$&arg=value</a>',
            backup_feedback_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert show_entries links when called with a valid task.
     */
    public function test_encode_content_links_show_entries_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('feedback', array('course' => $course1->id));
        $module2 = $generator->create_module('feedback', array('course' => $course2->id));

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course1->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
            backup::MODE_IMPORT, $USER->id);
        $tasks = $bc->get_plan()->get_tasks();

        // We need a task to test with, it doesn't matter which, but we'll use the root task.
        $roottask = null;
        foreach ($tasks as $task) {
            if ($task instanceof backup_root_task) {
                $roottask = $task;
                break;
            }
        }
        $this->assertNotEmpty($roottask, 'Unable to find the root backup task');

        // We expect the module in course 1 to be encoded, but not the module in course 2.
        $this->assertSame(
            '$@FEEDBACKSHOWENTRIESBYID*'.$module1->cmid.'@$',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$module2->cmid,
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321',
            backup_feedback_activity_task::encode_content_links($CFG->wwwroot.'/mod/feedback/show_entries.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/feedback/show_entries.php?id='.$module1->cmid,
            backup_feedback_activity_task::encode_content_links('/mod/feedback/show_entries.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/feedback/show_entries.php?id='.$module2->cmid,
            backup_feedback_activity_task::encode_content_links('/mod/feedback/show_entries.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/feedback/show_entries.php?id=987654321',
            backup_feedback_activity_task::encode_content_links('/mod/feedback/show_entries.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@FEEDBACKSHOWENTRIESBYID*'.$module1->cmid.'@$#anchor">$@FEEDBACKSHOWENTRIESBYID*'.$module1->cmid.'@$#anchor</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/show_entries.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@FEEDBACKSHOWENTRIESBYID*'.$module1->cmid.'@$&arg=value">$@FEEDBACKSHOWENTRIESBYID*'.$module1->cmid.'@$&arg=value</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/feedback/show_entries.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_feedback_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }
}
