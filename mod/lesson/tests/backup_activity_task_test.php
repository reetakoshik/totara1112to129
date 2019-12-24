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
 * @package mod_lesson
 * @category phpunit
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once($CFG->dirroot . '/mod/lesson/backup/moodle2/backup_lesson_activity_task.class.php');


/**
 * Test the lesson backup activity task methods.
 */
class mod_lesson_backup_activity_task_testcase extends advanced_testcase {

    /**
     * Tests calling encode_content_links without content that should have no matches.
     */
    public function test_encode_content_links_with_no_matches() {
        global $CFG;
        // First up test things that should not lead to replacement.
        $this->assertSame('', backup_lesson_activity_task::encode_content_links(''));
        $this->assertSame('Test', backup_lesson_activity_task::encode_content_links('Test'));
        $this->assertSame($CFG->wwwroot, backup_lesson_activity_task::encode_content_links($CFG->wwwroot));
        $this->assertSame(
            "<a href='{$CFG->wwwroot}'>wwwroot</a>",
            backup_lesson_activity_task::encode_content_links("<a href='{$CFG->wwwroot}'>wwwroot</a>")
        );
    }

    /**
     * Test encode_content_links can convert index links when called without a task.
     */
    public function test_encode_content_links_index_without_a_task() {
        global $CFG;

        // Test index.php links.
        $this->assertSame(
            '$@LESSONINDEX*3@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/index.php?id=3')
        );
        $this->assertSame(
            '$@LESSONINDEX*987654321@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/index.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@LESSONINDEX*987654321@$">$@LESSONINDEX*987654321@$</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/index.php?id=987654321">'.$CFG->wwwroot.'/mod/lesson/index.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/lesson/index.php?id=64">/mod/lesson/index.php?id=64</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="/mod/lesson/index.php?id=64">/mod/lesson/index.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONINDEX*987654321@$#anchor">$@LESSONINDEX*987654321@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/index.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/lesson/index.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONINDEX*987654321@$&arg=value">$@LESSONINDEX*987654321@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/index.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/lesson/index.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('lesson', array('course' => $course1));
        $module2 = $generator->create_module('lesson', array('course' => $course2));

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
            '$@LESSONINDEX*'.$course1->id.'@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/index.php?id='.$course2->id,
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/index.php?id=987654321',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/index.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/lesson/index.php?id='.$course1->id,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/index.php?id='.$course2->id,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/index.php?id=987654321',
            backup_lesson_activity_task::encode_content_links('/mod/lesson/index.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/lesson/index.php?id='.$course1->id.'#anchor';
        $this->assertSame(
            '<a href="$@LESSONINDEX*'.$course1->id.'@$#anchor">$@LESSONINDEX*'.$course1->id.'@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/index.php?id='.$course2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/index.php?id='.$course1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@LESSONINDEX*'.$course1->id.'@$&arg=value">$@LESSONINDEX*'.$course1->id.'@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/index.php?id='.$course2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@LESSONVIEWBYID*3@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id=3')
        );
        $this->assertSame(
            '$@LESSONVIEWBYID*987654321@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@LESSONVIEWBYID*987654321@$">$@LESSONVIEWBYID*987654321@$</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321">'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/lesson/view.php?id=64">/mod/lesson/view.php?id=64</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="/mod/lesson/view.php?id=64">/mod/lesson/view.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONVIEWBYID*987654321@$#anchor">$@LESSONVIEWBYID*987654321@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONVIEWBYID*987654321@$&arg=value">$@LESSONVIEWBYID*987654321@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('lesson', array('course' => $course1));
        $module2 = $generator->create_module('lesson', array('course' => $course2));

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
            '$@LESSONVIEWBYID*'.$module1->cmid.'@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/view.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/view.php?id=987654321',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/lesson/view.php?id='.$module1->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/view.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/view.php?id=987654321',
            backup_lesson_activity_task::encode_content_links('/mod/lesson/view.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@LESSONVIEWBYID*'.$module1->cmid.'@$#anchor">$@LESSONVIEWBYID*'.$module1->cmid.'@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@LESSONVIEWBYID*'.$module1->cmid.'@$&arg=value">$@LESSONVIEWBYID*'.$module1->cmid.'@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert edit links when called without a task.
     */
    public function test_encode_content_links_edit_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@LESSONEDIT*3@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/edit.php?id=3')
        );
        $this->assertSame(
            '$@LESSONEDIT*987654321@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/edit.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@LESSONEDIT*987654321@$">$@LESSONEDIT*987654321@$</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/edit.php?id=987654321">'.$CFG->wwwroot.'/mod/lesson/edit.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/lesson/edit.php?id=64">/mod/lesson/edit.php?id=64</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="/mod/lesson/edit.php?id=64">/mod/lesson/edit.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONEDIT*987654321@$#anchor">$@LESSONEDIT*987654321@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/edit.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/lesson/edit.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONEDIT*987654321@$&arg=value">$@LESSONEDIT*987654321@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/edit.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/lesson/edit.php?id=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert edit links when called with a valid task.
     */
    public function test_encode_content_links_edit_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('lesson', array('course' => $course1));
        $module2 = $generator->create_module('lesson', array('course' => $course2));

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
            '$@LESSONEDIT*'.$module1->cmid.'@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/edit.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/edit.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/edit.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/edit.php?id=987654321',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/edit.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/lesson/edit.php?id='.$module1->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/edit.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/edit.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/edit.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/edit.php?id=987654321',
            backup_lesson_activity_task::encode_content_links('/mod/lesson/edit.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/lesson/edit.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@LESSONEDIT*'.$module1->cmid.'@$#anchor">$@LESSONEDIT*'.$module1->cmid.'@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/edit.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/edit.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/edit.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@LESSONEDIT*'.$module1->cmid.'@$&arg=value">$@LESSONEDIT*'.$module1->cmid.'@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/edit.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/edit.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/lesson/edit.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert essay links when called without a task.
     */
    public function test_encode_content_links_essay_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@LESSONESSAY*3@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/essay.php?id=3')
        );
        $this->assertSame(
            '$@LESSONESSAY*987654321@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/essay.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@LESSONESSAY*987654321@$">$@LESSONESSAY*987654321@$</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/essay.php?id=987654321">'.$CFG->wwwroot.'/mod/lesson/essay.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/lesson/essay.php?id=64">/mod/lesson/essay.php?id=64</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="/mod/lesson/essay.php?id=64">/mod/lesson/essay.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONESSAY*987654321@$#anchor">$@LESSONESSAY*987654321@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/essay.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/lesson/essay.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONESSAY*987654321@$&arg=value">$@LESSONESSAY*987654321@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/essay.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/lesson/essay.php?id=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert essay links when called with a valid task.
     */
    public function test_encode_content_links_essay_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('lesson', array('course' => $course1));
        $module2 = $generator->create_module('lesson', array('course' => $course2));

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
            '$@LESSONESSAY*'.$module1->cmid.'@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/essay.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/essay.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/essay.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/essay.php?id=987654321',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/essay.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/lesson/essay.php?id='.$module1->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/essay.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/essay.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/essay.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/essay.php?id=987654321',
            backup_lesson_activity_task::encode_content_links('/mod/lesson/essay.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/lesson/essay.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@LESSONESSAY*'.$module1->cmid.'@$#anchor">$@LESSONESSAY*'.$module1->cmid.'@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/essay.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/essay.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/essay.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@LESSONESSAY*'.$module1->cmid.'@$&arg=value">$@LESSONESSAY*'.$module1->cmid.'@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/essay.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/essay.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/lesson/essay.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert report links when called without a task.
     */
    public function test_encode_content_links_report_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@LESSONREPORT*3@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/report.php?id=3')
        );
        $this->assertSame(
            '$@LESSONREPORT*987654321@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/report.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@LESSONREPORT*987654321@$">$@LESSONREPORT*987654321@$</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/report.php?id=987654321">'.$CFG->wwwroot.'/mod/lesson/report.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/lesson/report.php?id=64">/mod/lesson/report.php?id=64</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="/mod/lesson/report.php?id=64">/mod/lesson/report.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONREPORT*987654321@$#anchor">$@LESSONREPORT*987654321@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/report.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/lesson/report.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONREPORT*987654321@$&arg=value">$@LESSONREPORT*987654321@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/report.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/lesson/report.php?id=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert report links when called with a valid task.
     */
    public function test_encode_content_links_report_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('lesson', array('course' => $course1));
        $module2 = $generator->create_module('lesson', array('course' => $course2));

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
            '$@LESSONREPORT*'.$module1->cmid.'@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/report.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/report.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/report.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/report.php?id=987654321',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/report.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/lesson/report.php?id='.$module1->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/report.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/report.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/report.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/report.php?id=987654321',
            backup_lesson_activity_task::encode_content_links('/mod/lesson/report.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/lesson/report.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@LESSONREPORT*'.$module1->cmid.'@$#anchor">$@LESSONREPORT*'.$module1->cmid.'@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/report.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/report.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/report.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@LESSONREPORT*'.$module1->cmid.'@$&arg=value">$@LESSONREPORT*'.$module1->cmid.'@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/report.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/report.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/lesson/report.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert mediafile links when called without a task.
     */
    public function test_encode_content_links_mediafile_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@LESSONMEDIAFILE*3@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/mediafile.php?id=3')
        );
        $this->assertSame(
            '$@LESSONMEDIAFILE*987654321@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@LESSONMEDIAFILE*987654321@$">$@LESSONMEDIAFILE*987654321@$</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321">'.$CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/lesson/mediafile.php?id=64">/mod/lesson/mediafile.php?id=64</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="/mod/lesson/mediafile.php?id=64">/mod/lesson/mediafile.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONMEDIAFILE*987654321@$#anchor">$@LESSONMEDIAFILE*987654321@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONMEDIAFILE*987654321@$&arg=value">$@LESSONMEDIAFILE*987654321@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert mediafile links when called with a valid task.
     */
    public function test_encode_content_links_mediafile_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('lesson', array('course' => $course1));
        $module2 = $generator->create_module('lesson', array('course' => $course2));

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
            '$@LESSONMEDIAFILE*'.$module1->cmid.'@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/mediafile.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/mediafile.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/mediafile.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/mediafile.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/lesson/mediafile.php?id='.$module1->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/mediafile.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/mediafile.php?id='.$module2->cmid,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/mediafile.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/mediafile.php?id=987654321',
            backup_lesson_activity_task::encode_content_links('/mod/lesson/mediafile.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/lesson/mediafile.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@LESSONMEDIAFILE*'.$module1->cmid.'@$#anchor">$@LESSONMEDIAFILE*'.$module1->cmid.'@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/mediafile.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/mediafile.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/mediafile.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@LESSONMEDIAFILE*'.$module1->cmid.'@$&arg=value">$@LESSONMEDIAFILE*'.$module1->cmid.'@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/mediafile.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/mediafile.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/lesson/mediafile.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert mediafile links when called without a task.
     */
    public function test_encode_content_links_viewpage_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@LESSONVIEWPAGE*8*3@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id=8&pageid=3')
        );
        $this->assertSame(
            '$@LESSONVIEWPAGE*987654321*123456789@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id=987654321&pageid=123456789')
        );
        $this->assertSame(
            '<a href="$@LESSONVIEWPAGE*987654321*1@$">$@LESSONVIEWPAGE*987654321*1@$</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321&pageid=1">'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321&pageid=1</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/lesson/view.php?id=64&pageid=32">/mod/lesson/view.php?id=64&pageid=32</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="/mod/lesson/view.php?id=64&pageid=32">/mod/lesson/view.php?id=64&pageid=32</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONVIEWPAGE*987654321*7@$#anchor">$@LESSONVIEWPAGE*987654321*7@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321&pageid=7#anchor">'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321&pageid=7#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONVIEWPAGE*987654321*7@$&arg=value">$@LESSONVIEWPAGE*987654321*7@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321&pageid=7&arg=value">'.$CFG->wwwroot.'/mod/lesson/view.php?id=987654321&pageid=7&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert mediafile links when called with a valid task.
     */
    public function test_encode_content_links_viewpage_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var mod_lesson_generator $lessongenerator */
        $lessongenerator = $generator->get_plugin_generator('mod_lesson');

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('lesson', array('course' => $course1));
        $module2 = $generator->create_module('lesson', array('course' => $course2));

        $page1 = $lessongenerator->create_content($module1);
        $page2 = $lessongenerator->create_content($module2);

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
            '$@LESSONVIEWPAGE*'.$module1->cmid.'*'.$page1->id.'@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id='.$module1->cmid.'&pageid='.$page1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/view.php?id='.$module2->cmid.'&pageid='.$page1->id,
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id='.$module2->cmid.'&pageid='.$page1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/view.php?id=987654321',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/view.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/lesson/view.php?id='.$module1->cmid.'&pageid='.$page1->id,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/view.php?id='.$module1->cmid.'&pageid='.$page1->id, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/view.php?id='.$module2->cmid.'&pageid='.$page2->id,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/view.php?id='.$module2->cmid.'&pageid='.$page2->id, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/view.php?id=987654321&pageid=8',
            backup_lesson_activity_task::encode_content_links('/mod/lesson/view.php?id=987654321&pageid=8', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module1->cmid.'&pageid='.$page1->id.'#anchor';
        $this->assertSame(
            '<a href="$@LESSONVIEWPAGE*'.$module1->cmid.'*'.$page1->id.'@$#anchor">$@LESSONVIEWPAGE*'.$module1->cmid.'*'.$page1->id.'@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module2->cmid.'&pageid='.$page2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id=546*8#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module1->cmid.'&pageid='.$page1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@LESSONVIEWPAGE*'.$module1->cmid.'*'.$page1->id.'@$&arg=value">$@LESSONVIEWPAGE*'.$module1->cmid.'*'.$page1->id.'@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module2->cmid.'&pageid='.$page2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/view.php?id=546&pageid=9&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/lesson/view.php?id='.$module1->id.'&pageid='.$page1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert edit links when called without a task.
     */
    public function test_encode_content_links_editpage_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@LESSONEDITPAGE*3*8@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/editpage.php?id=3&pageid=8')
        );
        $this->assertSame(
            '$@LESSONEDITPAGE*987654321*8@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8')
        );
        $this->assertSame(
            '<a href="$@LESSONEDITPAGE*987654321*8@$">$@LESSONEDITPAGE*987654321*8@$</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8">'.$CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/lesson/editpage.php?id=64&pageid=8">/mod/lesson/editpage.php?id=64&pageid=8</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="/mod/lesson/editpage.php?id=64&pageid=8">/mod/lesson/editpage.php?id=64&pageid=8</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONEDITPAGE*987654321*8@$#anchor">$@LESSONEDITPAGE*987654321*8@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8#anchor">'.$CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@LESSONEDITPAGE*987654321*8@$&arg=value">$@LESSONEDITPAGE*987654321*8@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8&arg=value">'.$CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert edit links when called with a valid task.
     */
    public function test_encode_content_links_editpage_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var mod_lesson_generator $lessongenerator */
        $lessongenerator = $generator->get_plugin_generator('mod_lesson');

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('lesson', array('course' => $course1));
        $module2 = $generator->create_module('lesson', array('course' => $course2));

        $page1 = $lessongenerator->create_content($module1);
        $page2 = $lessongenerator->create_content($module2);

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
            '$@LESSONEDITPAGE*'.$module1->cmid.'*'.$page1->id.'@$',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/editpage.php?id='.$module1->cmid.'&pageid='.$page1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/editpage.php?id='.$module2->cmid.'&pageid='.$page1->id,
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/editpage.php?id='.$module2->cmid.'&pageid='.$page1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8',
            backup_lesson_activity_task::encode_content_links($CFG->wwwroot.'/mod/lesson/editpage.php?id=987654321&pageid=8', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/lesson/editpage.php?id='.$module1->cmid.'&pageid='.$page1->id,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/editpage.php?id='.$module1->cmid.'&pageid='.$page1->id, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/editpage.php?id='.$module2->cmid.'&pageid='.$page2->id,
            backup_lesson_activity_task::encode_content_links('/mod/lesson/editpage.php?id='.$module2->cmid.'&pageid='.$page2->id, $roottask)
        );
        $this->assertSame(
            '/mod/lesson/editpage.php?id=987654321&pageid=8',
            backup_lesson_activity_task::encode_content_links('/mod/lesson/editpage.php?id=987654321&pageid=8', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/lesson/editpage.php?id='.$module1->cmid.'&pageid='.$page1->id.'#anchor';
        $this->assertSame(
            '<a href="$@LESSONEDITPAGE*'.$module1->cmid.'*'.$page1->id.'@$#anchor">$@LESSONEDITPAGE*'.$module1->cmid.'*'.$page1->id.'@$#anchor</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/editpage.php?id='.$module2->cmid.'&pageid='.$page2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/editpage.php?id=546&pageid=8#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/editpage.php?id='.$module1->cmid.'&pageid='.$page1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@LESSONEDITPAGE*'.$module1->cmid.'*'.$page1->id.'@$&arg=value">$@LESSONEDITPAGE*'.$module1->cmid.'*'.$page1->id.'@$&arg=value</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/editpage.php?id='.$module2->cmid.'&pageid='.$page2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/lesson/editpage.php?id=546&pageid=8&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/lesson/editpage.php?id='.$module1->id.'&pageid='.$page1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_lesson_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }
}
