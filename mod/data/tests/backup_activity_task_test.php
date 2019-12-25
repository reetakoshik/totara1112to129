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
 * @package mod_data
 * @category phpunit
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once($CFG->dirroot . '/mod/data/backup/moodle2/backup_data_activity_task.class.php');


/**
 * Test the data backup activity task methods.
 */
class mod_data_backup_activity_task_testcase extends advanced_testcase {

    /**
     * Tests calling encode_content_links without content that should have no matches.
     */
    public function test_encode_content_links_with_no_matches() {
        global $CFG;
        // First up test things that should not lead to replacement.
        $this->assertSame('', backup_data_activity_task::encode_content_links(''));
        $this->assertSame('Test', backup_data_activity_task::encode_content_links('Test'));
        $this->assertSame($CFG->wwwroot, backup_data_activity_task::encode_content_links($CFG->wwwroot));
        $this->assertSame(
            "<a href='{$CFG->wwwroot}'>wwwroot</a>",
            backup_data_activity_task::encode_content_links("<a href='{$CFG->wwwroot}'>wwwroot</a>")
        );
    }

    /**
     * Test encode_content_links can convert index links when called without a task.
     */
    public function test_encode_content_links_index_without_a_task() {
        global $CFG;

        // Test index.php links.
        $this->assertSame(
            '$@DATAINDEX*3@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/index.php?id=3')
        );
        $this->assertSame(
            '$@DATAINDEX*987654321@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/index.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@DATAINDEX*987654321@$">$@DATAINDEX*987654321@$</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/index.php?id=987654321">'.$CFG->wwwroot.'/mod/data/index.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/data/index.php?id=64">/mod/data/index.php?id=64</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="/mod/data/index.php?id=64">/mod/data/index.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@DATAINDEX*987654321@$#anchor">$@DATAINDEX*987654321@$#anchor</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/index.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/data/index.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@DATAINDEX*987654321@$&arg=value">$@DATAINDEX*987654321@$&arg=value</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/index.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/data/index.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('data', array('course' => $course1));
        $module2 = $generator->create_module('data', array('course' => $course2));

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
            '$@DATAINDEX*'.$course1->id.'@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/index.php?id='.$course2->id,
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/index.php?id=987654321',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/index.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/data/index.php?id='.$course1->id,
            backup_data_activity_task::encode_content_links('/mod/data/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            '/mod/data/index.php?id='.$course2->id,
            backup_data_activity_task::encode_content_links('/mod/data/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            '/mod/data/index.php?id=987654321',
            backup_data_activity_task::encode_content_links('/mod/data/index.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/data/index.php?id='.$course1->id.'#anchor';
        $this->assertSame(
            '<a href="$@DATAINDEX*'.$course1->id.'@$#anchor">$@DATAINDEX*'.$course1->id.'@$#anchor</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/index.php?id='.$course2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/index.php?id='.$course1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@DATAINDEX*'.$course1->id.'@$&arg=value">$@DATAINDEX*'.$course1->id.'@$&arg=value</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/index.php?id='.$course2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@DATAVIEWBYID*3@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?id=3')
        );
        $this->assertSame(
            '$@DATAVIEWBYID*987654321@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@DATAVIEWBYID*987654321@$">$@DATAVIEWBYID*987654321@$</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?id=987654321">'.$CFG->wwwroot.'/mod/data/view.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/data/view.php?id=64">/mod/data/view.php?id=64</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="/mod/data/view.php?id=64">/mod/data/view.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@DATAVIEWBYID*987654321@$#anchor">$@DATAVIEWBYID*987654321@$#anchor</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/data/view.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@DATAVIEWBYID*987654321@$&arg=value">$@DATAVIEWBYID*987654321@$&arg=value</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/data/view.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('data', array('course' => $course1));
        $module2 = $generator->create_module('data', array('course' => $course2));

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
            '$@DATAVIEWBYID*'.$module1->cmid.'@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/view.php?id='.$module2->cmid,
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/view.php?id=987654321',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/data/view.php?id='.$module1->cmid,
            backup_data_activity_task::encode_content_links('/mod/data/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/data/view.php?id='.$module2->cmid,
            backup_data_activity_task::encode_content_links('/mod/data/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/data/view.php?id=987654321',
            backup_data_activity_task::encode_content_links('/mod/data/view.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/data/view.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@DATAVIEWBYID*'.$module1->cmid.'@$#anchor">$@DATAVIEWBYID*'.$module1->cmid.'@$#anchor</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@DATAVIEWBYID*'.$module1->cmid.'@$&arg=value">$@DATAVIEWBYID*'.$module1->cmid.'@$&arg=value</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/data/view.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_by_data_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@DATAVIEWBYD*3@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d=3')
        );
        $this->assertSame(
            '$@DATAVIEWBYD*987654321@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d=987654321')
        );
        $this->assertSame(
            '<a href="$@DATAVIEWBYD*987654321@$">$@DATAVIEWBYD*987654321@$</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?d=987654321">'.$CFG->wwwroot.'/mod/data/view.php?d=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/data/view.php?d=64">/mod/data/view.php?d=64</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="/mod/data/view.php?d=64">/mod/data/view.php?d=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@DATAVIEWBYD*987654321@$#anchor">$@DATAVIEWBYD*987654321@$#anchor</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?d=987654321#anchor">'.$CFG->wwwroot.'/mod/data/view.php?d=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@DATAVIEWBYD*987654321@$&arg=value">$@DATAVIEWBYD*987654321@$&arg=value</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?d=987654321&arg=value">'.$CFG->wwwroot.'/mod/data/view.php?d=987654321&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert view links when called with a valid task.
     */
    public function test_encode_content_links_view_by_data_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('data', array('course' => $course1));
        $module2 = $generator->create_module('data', array('course' => $course2));

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
            '$@DATAVIEWBYD*'.$module1->id.'@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d='.$module1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/view.php?d='.$module2->id,
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d='.$module2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/view.php?d=987654321',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/data/view.php?d='.$module1->id,
            backup_data_activity_task::encode_content_links('/mod/data/view.php?d='.$module1->id, $roottask)
        );
        $this->assertSame(
            '/mod/data/view.php?d='.$module2->id,
            backup_data_activity_task::encode_content_links('/mod/data/view.php?d='.$module2->id, $roottask)
        );
        $this->assertSame(
            '/mod/data/view.php?d=987654321',
            backup_data_activity_task::encode_content_links('/mod/data/view.php?d=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module1->id.'#anchor';
        $this->assertSame(
            '<a href="$@DATAVIEWBYD*'.$module1->id.'@$#anchor">$@DATAVIEWBYD*'.$module1->id.'@$#anchor</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@DATAVIEWBYD*'.$module1->id.'@$&arg=value">$@DATAVIEWBYD*'.$module1->id.'@$&arg=value</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct cmid doesn't get converted (it should only convert the activity id).
        // $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module1->cmid;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_by_data_record_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@DATAVIEWRECORD*3*7@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d=3&rid=7')
        );
        $this->assertSame(
            '$@DATAVIEWRECORD*987654321*123456789@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=123456789')
        );
        $this->assertSame(
            '<a href="$@DATAVIEWRECORD*987654321*8@$">$@DATAVIEWRECORD*987654321*8@$</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=8">'.$CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=8</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/data/view.php?d=64&rid=8">/mod/data/view.php?d=64&rid=8</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="/mod/data/view.php?d=64&rid=8">/mod/data/view.php?d=64&rid=8</a>'
            )
        );
        $this->assertSame(
            '<a href="$@DATAVIEWRECORD*987654321*5@$#anchor">$@DATAVIEWRECORD*987654321*5@$#anchor</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=5#anchor">'.$CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=5#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@DATAVIEWRECORD*987654321*1@$&arg=value">$@DATAVIEWRECORD*987654321*1@$&arg=value</a>',
            backup_data_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=1&arg=value">'.$CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=1&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert view links when called with a valid task.
     */
    public function test_encode_content_links_view_by_data_record_with_a_task() {
        global $CFG, $USER, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('data', array('course' => $course1));
        $module2 = $generator->create_module('data', array('course' => $course2));

        $field = data_get_field_new('text', $module1);

        $fielddetail = new stdClass();
        $fielddetail->d = $module1->id;
        $fielddetail->mode = 'add';
        $fielddetail->type = 'text';
        $fielddetail->sesskey = sesskey();
        $fielddetail->name = 'Name';
        $fielddetail->description = 'Some name';

        $field->define_field($fielddetail);
        $field->insert_field();
        $record1id = data_add_record($module1);

        $content1id = $DB->insert_record('data_content', ['fieldid' => $field->field->id, 'recordid' => $record1id, 'content' => 'Asterix']);

        $field = data_get_field_new('text', $module2);

        $fielddetail->d = $module2->id;
        $fielddetail->name = 'Name 2';
        $fielddetail->description = 'Some name 2';

        $field->define_field($fielddetail);
        $field->insert_field();
        $record2id = data_add_record($module1);

        $content2id = $DB->insert_record('data_content', ['fieldid' => $field->field->id, 'recordid' => $record2id, 'content' => 'Colon']);

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
            '$@DATAVIEWRECORD*'.$module1->id.'*'.$record1id.'@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d='.$module1->id.'&rid='.$record1id, $roottask)
        );
        // We don't validate that the record belongs to the data activity.
        // We only deal with correct links, trying to validate incorrect links is a costly process, and in normal situations
        // we would not expect incorrect links.
        $this->assertSame(
            '$@DATAVIEWRECORD*'.$module1->id.'*'.$record2id.'@$',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d='.$module1->id.'&rid='.$record2id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/view.php?d='.$module2->id.'&rid='.$record1id,
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d='.$module2->id.'&rid='.$record1id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/view.php?d='.$module2->id.'&rid='.$record2id,
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d='.$module2->id.'&rid='.$record2id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=123456789',
            backup_data_activity_task::encode_content_links($CFG->wwwroot.'/mod/data/view.php?d=987654321&rid=123456789', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/data/view.php?d='.$module1->id.'&rid='.$record1id,
            backup_data_activity_task::encode_content_links('/mod/data/view.php?d='.$module1->id.'&rid='.$record1id, $roottask)
        );
        $this->assertSame(
            '/mod/data/view.php?d='.$module2->id.'&rid='.$record1id,
            backup_data_activity_task::encode_content_links('/mod/data/view.php?d='.$module2->id.'&rid='.$record1id, $roottask)
        );
        $this->assertSame(
            '/mod/data/view.php?d=987654321&rid=8',
            backup_data_activity_task::encode_content_links('/mod/data/view.php?d=987654321&rid=8', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module1->id.'&rid='.$record1id.'#anchor';
        $this->assertSame(
            '<a href="$@DATAVIEWRECORD*'.$module1->id.'*'.$record1id.'@$#anchor">$@DATAVIEWRECORD*'.$module1->id.'*'.$record1id.'@$#anchor</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module2->id.'&rid='.$record2id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d=546&rid=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module1->id.'&rid='.$record1id.'&arg=value';
        $this->assertSame(
            '<a href="$@DATAVIEWRECORD*'.$module1->id.'*'.$record1id.'@$&arg=value">$@DATAVIEWRECORD*'.$module1->id.'*'.$record1id.'@$&arg=value</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d='.$module2->id.'&rid='.$record2id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/data/view.php?d=546&rid=8&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_data_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
    }
}
