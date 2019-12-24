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
 * @package mod_glossary
 * @category phpunit
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once($CFG->dirroot . '/mod/glossary/backup/moodle2/backup_glossary_activity_task.class.php');


/**
 * Test the glossary backup activity task methods.
 */
class mod_glossary_backup_activity_task_testcase extends advanced_testcase {

    /**
     * Tests calling encode_content_links without content that should have no matches.
     */
    public function test_encode_content_links_with_no_matches() {
        global $CFG;
        // First up test things that should not lead to replacement.
        $this->assertSame('', backup_glossary_activity_task::encode_content_links(''));
        $this->assertSame('Test', backup_glossary_activity_task::encode_content_links('Test'));
        $this->assertSame($CFG->wwwroot, backup_glossary_activity_task::encode_content_links($CFG->wwwroot));
        $this->assertSame(
            "<a href='{$CFG->wwwroot}'>wwwroot</a>",
            backup_glossary_activity_task::encode_content_links("<a href='{$CFG->wwwroot}'>wwwroot</a>")
        );
    }

    /**
     * Test encode_content_links can convert index links when called without a task.
     */
    public function test_encode_content_links_index_without_a_task() {
        global $CFG;

        // Test index.php links.
        $this->assertSame(
            '$@GLOSSARYINDEX*3@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/index.php?id=3')
        );
        $this->assertSame(
            '$@GLOSSARYINDEX*987654321@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/index.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@GLOSSARYINDEX*987654321@$">$@GLOSSARYINDEX*987654321@$</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/index.php?id=987654321">'.$CFG->wwwroot.'/mod/glossary/index.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/glossary/index.php?id=64">/mod/glossary/index.php?id=64</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="/mod/glossary/index.php?id=64">/mod/glossary/index.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@GLOSSARYINDEX*987654321@$#anchor">$@GLOSSARYINDEX*987654321@$#anchor</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/index.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/glossary/index.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@GLOSSARYINDEX*987654321@$&arg=value">$@GLOSSARYINDEX*987654321@$&arg=value</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/index.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/glossary/index.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('glossary', array('course' => $course1));
        $module2 = $generator->create_module('glossary', array('course' => $course2));

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
            '$@GLOSSARYINDEX*'.$course1->id.'@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/glossary/index.php?id='.$course2->id,
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/glossary/index.php?id=987654321',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/index.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/glossary/index.php?id='.$course1->id,
            backup_glossary_activity_task::encode_content_links('/mod/glossary/index.php?id='.$course1->id, $roottask)
        );
        $this->assertSame(
            '/mod/glossary/index.php?id='.$course2->id,
            backup_glossary_activity_task::encode_content_links('/mod/glossary/index.php?id='.$course2->id, $roottask)
        );
        $this->assertSame(
            '/mod/glossary/index.php?id=987654321',
            backup_glossary_activity_task::encode_content_links('/mod/glossary/index.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/glossary/index.php?id='.$course1->id.'#anchor';
        $this->assertSame(
            '<a href="$@GLOSSARYINDEX*'.$course1->id.'@$#anchor">$@GLOSSARYINDEX*'.$course1->id.'@$#anchor</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/index.php?id='.$course2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/index.php?id='.$course1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@GLOSSARYINDEX*'.$course1->id.'@$&arg=value">$@GLOSSARYINDEX*'.$course1->id.'@$&arg=value</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/index.php?id='.$course2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@GLOSSARYVIEWBYID*3@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?id=3')
        );
        $this->assertSame(
            '$@GLOSSARYVIEWBYID*987654321@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?id=987654321')
        );
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYID*987654321@$">$@GLOSSARYVIEWBYID*987654321@$</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/view.php?id=987654321">'.$CFG->wwwroot.'/mod/glossary/view.php?id=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/glossary/view.php?id=64">/mod/glossary/view.php?id=64</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="/mod/glossary/view.php?id=64">/mod/glossary/view.php?id=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYID*987654321@$#anchor">$@GLOSSARYVIEWBYID*987654321@$#anchor</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/view.php?id=987654321#anchor">'.$CFG->wwwroot.'/mod/glossary/view.php?id=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYID*987654321@$&arg=value">$@GLOSSARYVIEWBYID*987654321@$&arg=value</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/view.php?id=987654321&arg=value">'.$CFG->wwwroot.'/mod/glossary/view.php?id=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('glossary', array('course' => $course1));
        $module2 = $generator->create_module('glossary', array('course' => $course2));

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
            '$@GLOSSARYVIEWBYID*'.$module1->cmid.'@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/glossary/view.php?id='.$module2->cmid,
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/glossary/view.php?id=987654321',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?id=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/glossary/view.php?id='.$module1->cmid,
            backup_glossary_activity_task::encode_content_links('/mod/glossary/view.php?id='.$module1->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/glossary/view.php?id='.$module2->cmid,
            backup_glossary_activity_task::encode_content_links('/mod/glossary/view.php?id='.$module2->cmid, $roottask)
        );
        $this->assertSame(
            '/mod/glossary/view.php?id=987654321',
            backup_glossary_activity_task::encode_content_links('/mod/glossary/view.php?id=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/glossary/view.php?id='.$module1->cmid.'#anchor';
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYID*'.$module1->cmid.'@$#anchor">$@GLOSSARYVIEWBYID*'.$module1->cmid.'@$#anchor</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?id='.$module2->cmid.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?id=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?id='.$module1->cmid.'&arg=value';
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYID*'.$module1->cmid.'@$&arg=value">$@GLOSSARYVIEWBYID*'.$module1->cmid.'@$&arg=value</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?id='.$module2->cmid.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?id=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct activity id doesn't get converted (it should only convert the cmid).
        // $url = $CFG->wwwroot.'/mod/glossary/view.php?id='.$module1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_view_by_activity_id_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@GLOSSARYVIEWBYG*3@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?g=3')
        );
        $this->assertSame(
            '$@GLOSSARYVIEWBYG*987654321@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?g=987654321')
        );
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYG*987654321@$">$@GLOSSARYVIEWBYG*987654321@$</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/view.php?g=987654321">'.$CFG->wwwroot.'/mod/glossary/view.php?g=987654321</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/glossary/view.php?g=64">/mod/glossary/view.php?g=64</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="/mod/glossary/view.php?g=64">/mod/glossary/view.php?g=64</a>'
            )
        );
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYG*987654321@$#anchor">$@GLOSSARYVIEWBYG*987654321@$#anchor</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/view.php?g=987654321#anchor">'.$CFG->wwwroot.'/mod/glossary/view.php?g=987654321#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYG*987654321@$&arg=value">$@GLOSSARYVIEWBYG*987654321@$&arg=value</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/view.php?g=987654321&arg=value">'.$CFG->wwwroot.'/mod/glossary/view.php?g=987654321&arg=value</a>'
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

        $module1 = $generator->create_module('glossary', array('course' => $course1));
        $module2 = $generator->create_module('glossary', array('course' => $course2));

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
            '$@GLOSSARYVIEWBYG*'.$module1->id.'@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?g='.$module1->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/glossary/view.php?g='.$module2->id,
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?g='.$module2->id, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/glossary/view.php?g=987654321',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/view.php?g=987654321', $roottask)
        );

        // Test no relative URL's get encoded.
        $this->assertSame(
            '/mod/glossary/view.php?g='.$module1->id,
            backup_glossary_activity_task::encode_content_links('/mod/glossary/view.php?g='.$module1->id, $roottask)
        );
        $this->assertSame(
            '/mod/glossary/view.php?g='.$module2->id,
            backup_glossary_activity_task::encode_content_links('/mod/glossary/view.php?g='.$module2->id, $roottask)
        );
        $this->assertSame(
            '/mod/glossary/view.php?g=987654321',
            backup_glossary_activity_task::encode_content_links('/mod/glossary/view.php?g=987654321', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/glossary/view.php?g='.$module1->id.'#anchor';
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYG*'.$module1->id.'@$#anchor">$@GLOSSARYVIEWBYG*'.$module1->id.'@$#anchor</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?g='.$module2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?g=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?g='.$module1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@GLOSSARYVIEWBYG*'.$module1->id.'@$&arg=value">$@GLOSSARYVIEWBYG*'.$module1->id.'@$&arg=value</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?g='.$module2->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/view.php?g=546&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct cmid doesn't get converted (it should only convert the activity id).
        // $url = $CFG->wwwroot.'/mod/glossary/view.php?g='.$module1->cmid;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Test encode_content_links can convert view links when called without a task.
     */
    public function test_encode_content_links_show_entry_without_a_task() {
        global $CFG;

        // Test view.php links.
        $this->assertSame(
            '$@GLOSSARYSHOWENTRY*3*7@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/showentry.php?courseid=3&eid=7')
        );
        $this->assertSame(
            '$@GLOSSARYSHOWENTRY*987654321*123456789@$',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=123456789')
        );
        $this->assertSame(
            '<a href="$@GLOSSARYSHOWENTRY*987654321*123456789@$">$@GLOSSARYSHOWENTRY*987654321*123456789@$</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=123456789">'.$CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=123456789</a>'
            )
        );
        $this->assertSame(
            '<a href="/mod/glossary/showentry.php?courseid=64&eid=75">/mod/glossary/showentry.php?courseid=64&eid=75</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="/mod/glossary/showentry.php?courseid=64&eid=75">/mod/glossary/showentry.php?courseid=64&eid=75</a>'
            )
        );
        $this->assertSame(
            '<a href="$@GLOSSARYSHOWENTRY*987654321*9@$#anchor">$@GLOSSARYSHOWENTRY*987654321*9@$#anchor</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=9#anchor">'.$CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=9#anchor</a>'
            )
        );
        $this->assertSame(
            '<a href="$@GLOSSARYSHOWENTRY*987654321*9@$&arg=value">$@GLOSSARYSHOWENTRY*987654321*9@$&arg=value</a>',
            backup_glossary_activity_task::encode_content_links(
                '<a href="'.$CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=9&arg=value">'.$CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=9&arg=value</a>'
            )
        );
    }

    /**
     * Test encode_content_links can convert view links when called with a valid task.
     */
    public function test_encode_content_links_show_entry_with_a_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var mod_glossary_generator $glossarygenerator */
        $glossarygenerator = $this->getDataGenerator()->get_plugin_generator('mod_glossary');

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $module1 = $generator->create_module('glossary', array('course' => $course1));
        $module2 = $generator->create_module('glossary', array('course' => $course2));

        $entry1 = $glossarygenerator->create_content($module1);
        $entry2 = $glossarygenerator->create_content($module2);

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
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$course1->id.'&eid='.$entry1->id;
        $this->assertSame(
            '$@GLOSSARYSHOWENTRY*'.$course1->id.'*'.$entry1->id.'@$',
            backup_glossary_activity_task::encode_content_links($url, $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$course2->id.'&eid='.$entry2->id;
        $this->assertSame(
            $url,
            backup_glossary_activity_task::encode_content_links($url, $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$course1->id.'&eid='.$entry2->id;
        $this->assertSame(
            $url,
            backup_glossary_activity_task::encode_content_links($url, $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$course2->id.'&eid='.$entry1->id;
        $this->assertSame(
            $url,
            backup_glossary_activity_task::encode_content_links($url, $roottask)
        );
        $this->assertSame(
            $CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=7',
            backup_glossary_activity_task::encode_content_links($CFG->wwwroot.'/mod/glossary/showentry.php?courseid=987654321&eid=7', $roottask)
        );

        // Test no relative URL's get encoded.
        $url = '/mod/glossary/showentry.php?courseid='.$course1->id.'&eid='.$entry1->id;
        $this->assertSame(
            $url,
            backup_glossary_activity_task::encode_content_links($url, $roottask)
        );
        $url = '/mod/glossary/showentry.php?courseid='.$course2->id.'&eid='.$entry2->id;
        $this->assertSame(
            $url,
            backup_glossary_activity_task::encode_content_links($url, $roottask)
        );
        $url = '/mod/glossary/showentry.php?courseid='.$course1->id.'&eid='.$entry2->id;
        $this->assertSame(
            $url,
            backup_glossary_activity_task::encode_content_links($url, $roottask)
        );
        $url = '/mod/glossary/showentry.php?courseid='.$course2->id.'&eid='.$entry1->id;
        $this->assertSame(
            $url,
            backup_glossary_activity_task::encode_content_links($url, $roottask)
        );
        $this->assertSame(
            '/mod/glossary/showentry.php?courseid=987654321&eid=7',
            backup_glossary_activity_task::encode_content_links('/mod/glossary/showentry.php?courseid=987654321&eid=7', $roottask)
        );

        // Now test the correct URL's with additional anchors and arguments.
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$course1->id.'&eid='.$entry1->id.'#anchor';
        $this->assertSame(
            '<a href="$@GLOSSARYSHOWENTRY*'.$course1->id.'*'.$entry1->id.'@$#anchor">$@GLOSSARYSHOWENTRY*'.$course1->id.'*'.$entry1->id.'@$#anchor</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$course2->id.'&eid='.$entry2->id.'#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid=546&eid=546#anchor';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$course1->id.'&eid='.$entry1->id.'&arg=value';
        $this->assertSame(
            '<a href="$@GLOSSARYSHOWENTRY*'.$course1->id.'*'.$entry1->id.'@$&arg=value">$@GLOSSARYSHOWENTRY*'.$course1->id.'*'.$entry1->id.'@$&arg=value</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$course2->id.'&eid='.$entry1->id.'&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );
        $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid=546&eid=9&arg=value';
        $this->assertSame(
            '<a href="'.$url.'">'.$url.'</a>',
            backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        );

        // Now check that the correct cm id doesn't get converted (it should only convert the course id).
        // $url = $CFG->wwwroot.'/mod/glossary/showentry.php?courseid='.$module1->id.'&eid='.$entry1->id;
        // $this->assertSame(
        //     '<a href="'.$url.'">'.$url.'</a>',
        //     backup_glossary_activity_task::encode_content_links('<a href="'.$url.'">'.$url.'</a>', $roottask)
        // );
    }

    /**
     * Tests that a coding exception isn't thrown when a course task is passed to the 'encode_content_links' method but it doesn't find any glossary task
     */
    public function test_coding_exception_is_not_thrown_when_course_cannot_find_glossary_task() {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Do backup with default settings. MODE_IMPORT means it will just create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
        $tasks = $bc->get_plan()->get_tasks();

        // We need a course task so a coding exception would be thrown if a check for empty($activityids) didn't exist in the 'encode_content_links' method.
        $coursetask = null;
        foreach ($tasks as $task) {
            if ($task instanceof backup_course_task) {
                $coursetask = $task;
                break;
            }
        }
        $this->assertNotEmpty($coursetask, 'Unable to find a course backup task');
        $content = "$CFG->wwwroot/mod/glossary/showentry.php?courseid=$course->id&eid=1&displayformat=dictionary";
        $encoded_content = backup_glossary_activity_task::encode_content_links($content, $coursetask);
        $this->assertSame($content, $encoded_content);
    }
}
