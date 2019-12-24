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
 * @package mod_book
 * @category phpunit
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once($CFG->dirroot . '/mod/book/backup/moodle2/backup_book_activity_task.class.php');


/**
 * Test the book backup activity task methods.
 */
class mod_book_backup_activity_task_testcase extends advanced_testcase {

    /**
     * Tests calling encode_content_links without content that should have no matches.
     */
    public function test_encode_content_links_with_no_matches() {
        global $CFG;
        // First up test things that should not lead to replacement.
        $this->assertSame('', backup_book_activity_task::encode_content_links(''));
        $this->assertSame('Test', backup_book_activity_task::encode_content_links('Test'));
        $this->assertSame($CFG->wwwroot, backup_book_activity_task::encode_content_links($CFG->wwwroot));
        $this->assertSame(
            "<a href='{$CFG->wwwroot}'>wwwroot</a>",
            backup_book_activity_task::encode_content_links("<a href='{$CFG->wwwroot}'>wwwroot</a>")
        );
    }

    /**
     * Test encode_content_links can convert index links when called without a task.
     */
    public function test_encode_content_links_index_without_a_task() {
        global $CFG;

        // Test index.php links.
        $this->assertSame(
            '$@BOOKINDEX*3@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/index.php?id=3')
        );
        $this->assertSame(
            '$@BOOKINDEX*987654321@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/index.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@BOOKINDEX*987654321@$">$@BOOKINDEX*987654321@$</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/index.php?id=987654321">'.$CFG->wwwroot.'/mod/book/index.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/book/index.php?id=64">/mod/book/index.php?id=64</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="/mod/book/index.php?id=64">/mod/book/index.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKINDEX*987654321@$#anchor">$@BOOKINDEX*987654321@$#anchor</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/index.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/book/index.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKINDEX*987654321@$&arg=value">$@BOOKINDEX*987654321@$&arg=value</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/index.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/book/index.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('book', array('course' => $course1));
        $module2 = $generator->create_module('book', array('course' => $course2));

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
            '$@BOOKINDEX*'.$course1->id.'@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/book/index.php?id='.$course2->id,
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/book/index.php?id=987654321',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/index.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/book/index.php?id='.$course1->id,
            backup_book_activity_task::encode_content_links('/mod/book/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            '/mod/book/index.php?id='.$course2->id,
            backup_book_activity_task::encode_content_links('/mod/book/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            '/mod/book/index.php?id=987654321',
            backup_book_activity_task::encode_content_links('/mod/book/index.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/book/index.php?id='.$course1->id.'#anchor';
        $this->assertSame(
            '<a href="$@BOOKINDEX*'.$course1->id.'@$#anchor">$@BOOKINDEX*'.$course1->id.'@$#anchor</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/index.php?id='.$course2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/index.php?id='.$course1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@BOOKINDEX*'.$course1->id.'@$&arg=value">$@BOOKINDEX*'.$course1->id.'@$&arg=value</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/index.php?id='.$course2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@BOOKVIEWBYID*3@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id=3')
        );
        $this->assertSame(
            '$@BOOKVIEWBYID*987654321@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYID*987654321@$">$@BOOKVIEWBYID*987654321@$</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?id=987654321">'.$CFG->wwwroot.'/mod/book/view.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/book/view.php?id=64">/mod/book/view.php?id=64</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="/mod/book/view.php?id=64">/mod/book/view.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYID*987654321@$#anchor">$@BOOKVIEWBYID*987654321@$#anchor</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/book/view.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYID*987654321@$&arg=value">$@BOOKVIEWBYID*987654321@$&arg=value</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/book/view.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('book', array('course' => $course1));
        $module2 = $generator->create_module('book', array('course' => $course2));

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
            '$@BOOKVIEWBYID*'.$module1->cmid.'@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/book/view.php?id='.$module2->cmid,
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/book/view.php?id=987654321',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/book/view.php?id='.$module1->cmid,
            backup_book_activity_task::encode_content_links('/mod/book/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/book/view.php?id='.$module2->cmid,
            backup_book_activity_task::encode_content_links('/mod/book/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/book/view.php?id=987654321',
            backup_book_activity_task::encode_content_links('/mod/book/view.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@BOOKVIEWBYID*'.$module1->cmid.'@$#anchor">$@BOOKVIEWBYID*'.$module1->cmid.'@$#anchor</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@BOOKVIEWBYID*'.$module1->cmid.'@$&arg=value">$@BOOKVIEWBYID*'.$module1->cmid.'@$&arg=value</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_by_cmid_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@BOOKVIEWBYB*3@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b=3')
        );
        $this->assertSame(
            '$@BOOKVIEWBYB*987654321@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b=987654321')
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYB*987654321@$">$@BOOKVIEWBYB*987654321@$</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?b=987654321">'.$CFG->wwwroot.'/mod/book/view.php?b=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/book/view.php?b=64">/mod/book/view.php?b=64</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="/mod/book/view.php?b=64">/mod/book/view.php?b=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYB*987654321@$#anchor">$@BOOKVIEWBYB*987654321@$#anchor</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?b=987654321#anchor">'.$CFG->wwwroot.'/mod/book/view.php?b=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYB*987654321@$&arg=value">$@BOOKVIEWBYB*987654321@$&arg=value</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?b=987654321&arg=value">'.$CFG->wwwroot.'/mod/book/view.php?b=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert view links when called with a valid task.
     */
    public function test_encode_content_links_view_by_cmid_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('book', array('course' => $course1));
        $module2 = $generator->create_module('book', array('course' => $course2));

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
            '$@BOOKVIEWBYB*'.$module1->id.'@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b='.$module1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/book/view.php?b='.$module2->id,
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b='.$module2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/book/view.php?b=987654321',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/book/view.php?b='.$module1->id,
            backup_book_activity_task::encode_content_links('/mod/book/view.php?b='.$module1->id, $roottask)
        );
        $this->assertSame(
            '/mod/book/view.php?b='.$module2->id,
            backup_book_activity_task::encode_content_links('/mod/book/view.php?b='.$module2->id, $roottask)
        );
        $this->assertSame(
            '/mod/book/view.php?b=987654321',
            backup_book_activity_task::encode_content_links('/mod/book/view.php?b=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module1->id.'#anchor';
        $this->assertSame(
            '<a href="$@BOOKVIEWBYB*'.$module1->id.'@$#anchor">$@BOOKVIEWBYB*'.$module1->id.'@$#anchor</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@BOOKVIEWBYB*'.$module1->id.'@$&arg=value">$@BOOKVIEWBYB*'.$module1->id.'@$&arg=value</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module1->cmid;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert view chapter links when called without a task.
     */
    public function test_encode_content_links_view_chapter_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@BOOKVIEWBYIDCH*3*17@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id=3&chapterid=17')
        );
        $this->assertSame(
            '$@BOOKVIEWBYIDCH*987654321*123456789@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=123456789')
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYIDCH*987654321*9@$">$@BOOKVIEWBYIDCH*987654321*8@$</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=9">'.$CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=8</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/book/view.php?id=64&chapterid=64">/mod/book/view.php?id=64&chapterid=7</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="/mod/book/view.php?id=64&chapterid=64">/mod/book/view.php?id=64&chapterid=7</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYIDCH*987654321*5@$#anchor">$@BOOKVIEWBYIDCH*987654321*5@$#anchor</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=5#anchor">'.$CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=5#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYIDCH*987654321*4@$&arg=value">$@BOOKVIEWBYIDCH*987654321*4@$&arg=value</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=4&arg=value">'.$CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=4&arg=value</a>'
            )
        );
    }


    /**
     * Test encode_content_links can convert view links when called with a valid task.
     */
    public function test_encode_content_links_view_chapter_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var mod_book_generator $bookgenerator */
        $bookgenerator = $generator->get_plugin_generator('mod_book');

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('book', array('course' => $course1));
        $module2 = $generator->create_module('book', array('course' => $course2));

        $chapter1 = $bookgenerator->create_chapter(array('bookid' => $module1->id));
        $chapter2 = $bookgenerator->create_chapter(array('bookid' => $module2->id));

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
            '$@BOOKVIEWBYIDCH*'.$module1->cmid.'*'.$chapter1->id.'@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id='.$module1->cmid.'&chapterid='.$chapter1->id, $roottask)
        );
        $this->assertSame(
            '$@BOOKVIEWBYIDCH*'.$module1->cmid.'*'.$chapter2->id.'@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id='.$module1->cmid.'&chapterid='.$chapter2->id, $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module2->cmid.'&chapterid='.$chapter1->id;
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module2->cmid.'&chapterid='.$chapter2->id;
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=123456789',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?id=987654321&chapterid=123456789', $roottask)
        );

        // Test no relative URL's get encoded.
        $url = '/mod/book/view.php?id='.$module1->cmid.'&chapterid='.$chapter1->id;
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );
        $url = '/mod/book/view.php?id='.$module2->cmid.'&chapterid='.$chapter2->id;
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );
        $this->assertSame(
            '/mod/book/view.php?id=987654321&chapterid=1',
            backup_book_activity_task::encode_content_links('/mod/book/view.php?id=987654321&chapterid=1', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module1->cmid.'&chapterid='.$chapter1->id.'#anchor';
        $this->assertSame(
            '<a href="$@BOOKVIEWBYIDCH*'.$module1->cmid.'*'.$chapter1->id.'@$#anchor">$@BOOKVIEWBYIDCH*'.$module1->cmid.'*'.$chapter1->id.'@$#anchor</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module2->cmid.'&chapterid='.$chapter2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id=546&chapterid=7#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module1->cmid.'&chapterid='.$chapter1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@BOOKVIEWBYIDCH*'.$module1->cmid.'*'.$chapter1->id.'@$&arg=value">$@BOOKVIEWBYIDCH*'.$module1->cmid.'*'.$chapter1->id.'@$&arg=value</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module2->cmid.'&chapterid='.$chapter2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?id=546&chapterid=9&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/book/view.php?id='.$module1->id.'&chapterid='.$chapter1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert view chapter links by cmid when called without a task.
     */
    public function test_encode_content_links_view_chapter_by_cmid_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@BOOKVIEWBYBCH*3*17@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b=3&chapterid=17')
        );
        $this->assertSame(
            '$@BOOKVIEWBYBCH*987654321*123456789@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b=987654321&chapterid=123456789')
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYBCH*987654321*9@$">$@BOOKVIEWBYBCH*987654321*8@$</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?b=987654321&chapterid=9">'.$CFG->wwwroot.'/mod/book/view.php?b=987654321&chapterid=8</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/book/view.php?b=64&chapterid=64">/mod/book/view.php?b=64&chapterid=7</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="/mod/book/view.php?b=64&chapterid=64">/mod/book/view.php?b=64&chapterid=7</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYBCH*987654321*5@$#anchor">$@BOOKVIEWBYBCH*987654321*5@$#anchor</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?b=987654321&chapterid=5#anchor">'.$CFG->wwwroot.'/mod/book/view.php?b=987654321&chapterid=5#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@BOOKVIEWBYBCH*987654321*4@$&arg=value">$@BOOKVIEWBYBCH*987654321*4@$&arg=value</a>',
            backup_book_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/book/view.php?b=987654321&chapterid=4&arg=value">'.$CFG->wwwroot.'/mod/book/view.php?b=987654321&chapterid=4&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert view links when called with a valid task.
     */
    public function test_encode_content_links_view_chapter_by_cmid_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var mod_book_generator $bookgenerator */
        $bookgenerator = $generator->get_plugin_generator('mod_book');

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('book', array('course' => $course1));
        $module2 = $generator->create_module('book', array('course' => $course2));

        $chapter1 = $bookgenerator->create_chapter(array('bookid' => $module1->id));
        $chapter2 = $bookgenerator->create_chapter(array('bookid' => $module2->id));

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
            '$@BOOKVIEWBYBCH*'.$module1->id.'*'.$chapter1->id.'@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b='.$module1->id.'&chapterid='.$chapter1->id, $roottask)
        );
        $this->assertSame(
            '$@BOOKVIEWBYBCH*'.$module1->id.'*'.$chapter2->id.'@$',
            backup_book_activity_task::encode_content_links($CFG->wwwroot.'/mod/book/view.php?b='.$module1->id.'&chapterid='.$chapter2->id, $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module2->id.'&chapterid='.$chapter1->id;
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module2->id.'&chapterid='.$chapter2->id;
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b=987654321&chapterid=123456789';
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );

        // Test no relative URL's get encoded.
        $url = '/mod/book/view.php?b='.$module1->id.'&chapterid='.$chapter1->id;
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );
        $url = '/mod/book/view.php?b='.$module2->id.'&chapterid='.$chapter2->id;
        $this->assertSame(
            $url,
            backup_book_activity_task::encode_content_links($url, $roottask)
        );
        $this->assertSame(
            '/mod/book/view.php?b=987654321&chapterid=1',
            backup_book_activity_task::encode_content_links('/mod/book/view.php?b=987654321&chapterid=1', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module1->id.'&chapterid='.$chapter1->id.'#anchor';
        $this->assertSame(
            '<a href="$@BOOKVIEWBYBCH*'.$module1->id.'*'.$chapter1->id.'@$#anchor">$@BOOKVIEWBYBCH*'.$module1->id.'*'.$chapter1->id.'@$#anchor</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module2->id.'&chapterid='.$chapter2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b=546&chapterid=7#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module1->id.'&chapterid='.$chapter1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@BOOKVIEWBYBCH*'.$module1->id.'*'.$chapter1->id.'@$&arg=value">$@BOOKVIEWBYBCH*'.$module1->id.'*'.$chapter1->id.'@$&arg=value</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module2->id.'&chapterid='.$chapter2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/book/view.php?b=546&chapterid=9&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/book/view.php?b='.$module1->cmid.'&chapterid='.$chapter1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_book_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }
}
