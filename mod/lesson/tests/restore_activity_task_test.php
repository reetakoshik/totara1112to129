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
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/restore_activity_task.class.php');
require_once($CFG->dirroot . '/mod/lesson/backup/moodle2/restore_lesson_activity_task.class.php');


/**
 * Test the lesson backup activity task methods.
 */
class mod_lesson_restore_activity_task_testcase extends advanced_testcase {

    public function test_restore_decode_rules() {
        global $CFG;

        $this->resetAfterTest();

        $restoreid = 89765;
        $www = $CFG->wwwroot;

        /** @var restore_decode_rule[] $rules */
        $rules = \restore_lesson_activity_task::define_decode_rules();

        \restore_controller_dbops::create_restore_temp_tables($restoreid);

        $original  = "This is some test content.\n\n";
        $original .= "$@LESSONINDEX*7@$\n";
        $original .= "<a href='$@LESSONINDEX*9@$'>$@LESSONINDEX*9@$</a>\n\n";
        $original .= "$@LESSONVIEWBYID*6@$\n";
        $original .= "<a href='$@LESSONVIEWBYID*8@$'>$@LESSONVIEWBYID*8@$</a>\n";
        $original .= "$@LESSONVIEWBYID*5@$&name=value\n";
        $original .= "$@LESSONEDIT*6@$\n";
        $original .= "$@LESSONESSAY*7@$\n";
        $original .= "$@LESSONREPORT*8@$\n";
        $original .= "$@LESSONMEDIAFILE*9@$\n";
        $original .= "$@LESSONVIEWPAGE*10*11@$\n";
        $original .= "$@LESSONEDITPAGE*12*13@$\n";

        $expected  = "This is some test content.\n\n";
        $expected .= "{$www}/mod/lesson/index.php?id=7\n";
        $expected .= "<a href='{$www}/mod/lesson/index.php?id=9'>{$www}/mod/lesson/index.php?id=9</a>\n\n";
        $expected .= "{$www}/mod/lesson/view.php?id=6\n";
        $expected .= "<a href='{$www}/mod/lesson/view.php?id=8'>{$www}/mod/lesson/view.php?id=8</a>\n";
        $expected .= "{$www}/mod/lesson/view.php?id=5&name=value\n";
        $expected .= "{$www}/mod/lesson/edit.php?id=6\n";
        $expected .= "{$www}/mod/lesson/essay.php?id=7\n";
        $expected .= "{$www}/mod/lesson/report.php?id=8\n";
        $expected .= "{$www}/mod/lesson/mediafile.php?id=9\n";
        $expected .= "{$www}/mod/lesson/view.php?id=10&pageid=11\n";
        $expected .= "{$www}/mod/lesson/edit.php?id=12&pageid=13\n";

        $actual = $original;
        foreach ($rules as $rule) {
            $rule->set_restoreid($restoreid);
            $rule->set_wwwroots($www, $www);
            $actual = $rule->decode($actual);
        }

        \restore_controller_dbops::drop_restore_temp_tables($restoreid);

        $this->assertSame($expected, $actual);
    }
}
