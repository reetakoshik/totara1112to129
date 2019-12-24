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
 * @package mod_forum
 * @category phpunit
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once($CFG->dirroot . '/mod/forum/backup/moodle2/backup_forum_activity_task.class.php');


/**
 * Test the forum backup activity task methods.
 */
class mod_forum_backup_activity_task_testcase extends advanced_testcase {

    /**
     * Tests calling encode_content_links without content that should have no matches.
     */
    public function test_encode_content_links_with_no_matches() {
        global $CFG;
        // First up test things that should not lead to replacement.
        $this->assertSame('', backup_forum_activity_task::encode_content_links(''));
        $this->assertSame('Test', backup_forum_activity_task::encode_content_links('Test'));
        $this->assertSame($CFG->wwwroot, backup_forum_activity_task::encode_content_links($CFG->wwwroot));
        $this->assertSame(
            "<a href='{$CFG->wwwroot}'>wwwroot</a>",
            backup_forum_activity_task::encode_content_links("<a href='{$CFG->wwwroot}'>wwwroot</a>")
        );
    }

    /**
     * Test encode_content_links can convert index links when called without a task.
     */
    public function test_encode_content_links_index_without_a_task() {
        global $CFG;

        // Test index.php links.
        $this->assertSame(
            '$@FORUMINDEX*3@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/index.php?id=3')
        );
        $this->assertSame(
            '$@FORUMINDEX*987654321@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/index.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@FORUMINDEX*987654321@$">$@FORUMINDEX*987654321@$</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/index.php?id=987654321">'.$CFG->wwwroot.'/mod/forum/index.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/forum/index.php?id=64">/mod/forum/index.php?id=64</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="/mod/forum/index.php?id=64">/mod/forum/index.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FORUMINDEX*987654321@$#anchor">$@FORUMINDEX*987654321@$#anchor</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/index.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/forum/index.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FORUMINDEX*987654321@$&arg=value">$@FORUMINDEX*987654321@$&arg=value</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/index.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/forum/index.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('forum', array('course' => $course1));
        $module2 = $generator->create_module('forum', array('course' => $course2));

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
            '$@FORUMINDEX*'.$course1->id.'@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/index.php?id='.$course2->id,
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/index.php?id=987654321',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/index.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/forum/index.php?id='.$course1->id,
            backup_forum_activity_task::encode_content_links('/mod/forum/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            '/mod/forum/index.php?id='.$course2->id,
            backup_forum_activity_task::encode_content_links('/mod/forum/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            '/mod/forum/index.php?id=987654321',
            backup_forum_activity_task::encode_content_links('/mod/forum/index.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/forum/index.php?id='.$course1->id.'#anchor';
        $this->assertSame(
            '<a href="$@FORUMINDEX*'.$course1->id.'@$#anchor">$@FORUMINDEX*'.$course1->id.'@$#anchor</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/index.php?id='.$course2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/index.php?id='.$course1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@FORUMINDEX*'.$course1->id.'@$&arg=value">$@FORUMINDEX*'.$course1->id.'@$&arg=value</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/index.php?id='.$course2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@FORUMVIEWBYID*3@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?id=3')
        );
        $this->assertSame(
            '$@FORUMVIEWBYID*987654321@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@FORUMVIEWBYID*987654321@$">$@FORUMVIEWBYID*987654321@$</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/view.php?id=987654321">'.$CFG->wwwroot.'/mod/forum/view.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/forum/view.php?id=64">/mod/forum/view.php?id=64</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="/mod/forum/view.php?id=64">/mod/forum/view.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FORUMVIEWBYID*987654321@$#anchor">$@FORUMVIEWBYID*987654321@$#anchor</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/view.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/forum/view.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FORUMVIEWBYID*987654321@$&arg=value">$@FORUMVIEWBYID*987654321@$&arg=value</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/view.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/forum/view.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('forum', array('course' => $course1));
        $module2 = $generator->create_module('forum', array('course' => $course2));

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
            '$@FORUMVIEWBYID*'.$module1->cmid.'@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/view.php?id='.$module2->cmid,
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/view.php?id=987654321',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/forum/view.php?id='.$module1->cmid,
            backup_forum_activity_task::encode_content_links('/mod/forum/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/forum/view.php?id='.$module2->cmid,
            backup_forum_activity_task::encode_content_links('/mod/forum/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/forum/view.php?id=987654321',
            backup_forum_activity_task::encode_content_links('/mod/forum/view.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/forum/view.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@FORUMVIEWBYID*'.$module1->cmid.'@$#anchor">$@FORUMVIEWBYID*'.$module1->cmid.'@$#anchor</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@FORUMVIEWBYID*'.$module1->cmid.'@$&arg=value">$@FORUMVIEWBYID*'.$module1->cmid.'@$&arg=value</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/forum/view.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_by_activity_id_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@FORUMVIEWBYF*3@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?f=3')
        );
        $this->assertSame(
            '$@FORUMVIEWBYF*987654321@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?f=987654321')
        );
        $this->assertSame(
            '<a href="$@FORUMVIEWBYF*987654321@$">$@FORUMVIEWBYF*987654321@$</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/view.php?f=987654321">'.$CFG->wwwroot.'/mod/forum/view.php?f=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/forum/view.php?f=64">/mod/forum/view.php?f=64</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="/mod/forum/view.php?f=64">/mod/forum/view.php?f=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FORUMVIEWBYF*987654321@$#anchor">$@FORUMVIEWBYF*987654321@$#anchor</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/view.php?f=987654321#anchor">'.$CFG->wwwroot.'/mod/forum/view.php?f=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FORUMVIEWBYF*987654321@$&arg=value">$@FORUMVIEWBYF*987654321@$&arg=value</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/view.php?f=987654321&arg=value">'.$CFG->wwwroot.'/mod/forum/view.php?f=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert view links when called with a valid task.
     */
    public function test_encode_content_links_view_by_activity_id_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('forum', array('course' => $course1));
        $module2 = $generator->create_module('forum', array('course' => $course2));

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
            '$@FORUMVIEWBYF*'.$module1->id.'@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?f='.$module1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/view.php?f='.$module2->id,
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?f='.$module2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/view.php?f=987654321',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/view.php?f=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/forum/view.php?f='.$module1->id,
            backup_forum_activity_task::encode_content_links('/mod/forum/view.php?f='.$module1->id, $roottask)
        );
        $this->assertSame(
            '/mod/forum/view.php?f='.$module2->id,
            backup_forum_activity_task::encode_content_links('/mod/forum/view.php?f='.$module2->id, $roottask)
        );
        $this->assertSame(
            '/mod/forum/view.php?f=987654321',
            backup_forum_activity_task::encode_content_links('/mod/forum/view.php?f=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/forum/view.php?f='.$module1->id.'#anchor';
        $this->assertSame(
            '<a href="$@FORUMVIEWBYF*'.$module1->id.'@$#anchor">$@FORUMVIEWBYF*'.$module1->id.'@$#anchor</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?f='.$module2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?f=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?f='.$module1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@FORUMVIEWBYF*'.$module1->id.'@$&arg=value">$@FORUMVIEWBYF*'.$module1->id.'@$&arg=value</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?f='.$module2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/view.php?f=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct cmid doesn't get converted (it should only convert the activity id).
        // $url = $CFG->wwwroot.'/mod/forum/view.php?f='.$module1->cmid;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert discussion links when called without a task.
     */
    public function test_encode_content_links_discussion_without_a_task() {
        global $CFG;

        // Test discuss.php links.
        $this->assertSame(
            '$@FORUMDISCUSSIONVIEW*3@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/discuss.php?d=3')
        );
        $this->assertSame(
            '$@FORUMDISCUSSIONVIEW*987654321@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/discuss.php?d=987654321')
        );
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEW*987654321@$">$@FORUMDISCUSSIONVIEW*987654321@$</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321">'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/forum/discuss.php?d=64">/mod/forum/discuss.php?d=64</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="/mod/forum/discuss.php?d=64">/mod/forum/discuss.php?d=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEW*987654321@$#anchor">$@FORUMDISCUSSIONVIEW*987654321@$#anchor</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321#anchor">'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEW*987654321@$&arg=value">$@FORUMDISCUSSIONVIEW*987654321@$&arg=value</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321&arg=value">'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321&arg=value</a>'
            )
        );
        // Now try for discussions with a post hash.
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEWINSIDE*987654321*7@$">$@FORUMDISCUSSIONVIEWINSIDE*987654321*7@$</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321#7">'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321#7</a>'
            )
        );
        // Now try for discussions with a parent.
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEWPARENT*987654321*7@$">$@FORUMDISCUSSIONVIEWPARENT*987654321*7@$</a>',
            backup_forum_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321&parent=7">'.$CFG->wwwroot.'/mod/forum/discuss.php?d=987654321&parent=7</a>'
            )
        );

    }

    /**
     * Test encode_content_links can convert discuss links when called with a valid task.
     */
    public function test_encode_content_links_discussion_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var mod_forum_generator $forumgenerator */
        $forumgenerator = $generator->get_plugin_generator('mod_forum');

        $user = $generator->create_user();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('forum', array('course' => $course1));
        $module2 = $generator->create_module('forum', array('course' => $course2));

        // Add a few discussions.
        $discussion1 = $forumgenerator->create_discussion(['course' => $course1->id, 'forum' => $module1->id, 'userid' => $user->id]);
        $discussion2 = $forumgenerator->create_discussion(['course' => $course2->id, 'forum' => $module2->id, 'userid' => $user->id]);

        // Add some posts.
        $post1 = $forumgenerator->create_post(['discussion' => $discussion1->id, 'userid' => $user->id]);
        $post2 = $forumgenerator->create_post(['discussion' => $discussion2->id, 'userid' => $user->id]);

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
            '$@FORUMDISCUSSIONVIEW*'.$discussion1->id.'@$',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion2->id,
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/discuss.php?d=987654321',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/discuss.php?d=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/forum/discuss.php?d='.$discussion1->id,
            backup_forum_activity_task::encode_content_links('/mod/forum/discuss.php?d='.$discussion1->id, $roottask)
        );
        $this->assertSame(
            '/mod/forum/discuss.php?d='.$discussion2->id,
            backup_forum_activity_task::encode_content_links('/mod/forum/discuss.php?d='.$discussion2->id, $roottask)
        );
        $this->assertSame(
            '/mod/forum/discuss.php?d=987654321',
            backup_forum_activity_task::encode_content_links('/mod/forum/discuss.php?d=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion1->id.'#anchor';
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEW*'.$discussion1->id.'@$#anchor">$@FORUMDISCUSSIONVIEW*'.$discussion1->id.'@$#anchor</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEW*'.$discussion1->id.'@$&arg=value">$@FORUMDISCUSSIONVIEW*'.$discussion1->id.'@$&arg=value</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now try for discussions with a post hash.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion1->id.'#'.$post1->id;
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEWINSIDE*'.$discussion1->id.'*'.$post1->id.'@$">$@FORUMDISCUSSIONVIEWINSIDE*'.$discussion1->id.'*'.$post1->id.'@$</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        // You may note that this isn't strictly correct, currently we don't validate that the post belongs to the discussion.
        // Doing so costs us another query per link, and in this case we will just rely on the people using correct links, rather
        // than trying to handle poorly formed links.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion1->id.'#'.$post2->id;
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEWINSIDE*'.$discussion1->id.'*'.$post2->id.'@$">$@FORUMDISCUSSIONVIEWINSIDE*'.$discussion1->id.'*'.$post2->id.'@$</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion2->id.'#'.$post1->id;
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion2->id.'#'.$post2->id;
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now try for discussions with a parent.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion1->id.'&parent='.$post1->id;
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEWPARENT*'.$discussion1->id.'*'.$post1->id.'@$">$@FORUMDISCUSSIONVIEWPARENT*'.$discussion1->id.'*'.$post1->id.'@$</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        // As above we don't validate the post belongs to the discussion, we only deal with "correct" links.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion1->id.'&parent='.$post2->id;
        $this->assertSame(
            '<a href="$@FORUMDISCUSSIONVIEWPARENT*'.$discussion1->id.'*'.$post2->id.'@$">$@FORUMDISCUSSIONVIEWPARENT*'.$discussion1->id.'*'.$post2->id.'@$</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion2->id.'&parent='.$post1->id;
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion2->id.'&parent='.$post2->id;
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct cmid doesn't get converted (it should only convert the activity id).
        // $url = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert discuss links when called with a valid task.
     */
    public function test_encode_content_links_discussion_with_a_task_without_modules() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var mod_forum_generator $forumgenerator */
        $forumgenerator = $generator->get_plugin_generator('mod_forum');

        $user = $generator->create_user();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

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
            $CFG->wwwroot.'/mod/forum/discuss.php?d=3',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/discuss.php?d=3', $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/discuss.php?d=3',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/discuss.php?d=3', $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/forum/discuss.php?d=987654321',
            backup_forum_activity_task::encode_content_links($CFG->wwwroot.'/mod/forum/discuss.php?d=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/forum/discuss.php?d=3',
            backup_forum_activity_task::encode_content_links('/mod/forum/discuss.php?d=3', $roottask)
        );
        $this->assertSame(
            '/mod/forum/discuss.php?d=4',
            backup_forum_activity_task::encode_content_links('/mod/forum/discuss.php?d=4', $roottask)
        );
        $this->assertSame(
            '/mod/forum/discuss.php?d=987654321',
            backup_forum_activity_task::encode_content_links('/mod/forum/discuss.php?d=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=7#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=8#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=7&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=8&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now try for discussions with a post hash.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=7#4';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        // You may note that this isn't strictly correct, currently we don't validate that the post belongs to the discussion.
        // Doing so costs us another query per link, and in this case we will just rely on the people using correct links, rather
        // than trying to handle poorly formed links.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=7#5';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=8#4';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=8#5';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now try for discussions with a parent.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=7&parent=4';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        // As above we don't validate the post belongs to the discussion, we only deal with "correct" links.
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=7&parent=5';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=8&parent=4';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/forum/discuss.php?d=8&parent=5';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_forum_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
    }
}
