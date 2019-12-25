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
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');
require_once($CFG->dirroot . '/backup/moodle2/restore_activity_task.class.php');
require_once($CFG->dirroot . '/mod/feedback/backup/moodle2/restore_feedback_activity_task.class.php');


/**
 * Test the feedback backup activity task methods.
 */
class mod_feedback_restore_activity_task_testcase extends advanced_testcase {

    public function test_restore_decode_rules() {
        global $CFG;

        $this->resetAfterTest();

        $restoreid = 89765;
        $www = $CFG->wwwroot;

        /** @var restore_decode_rule[] $rules */
        $rules = \restore_feedback_activity_task::define_decode_rules();

        \restore_controller_dbops::create_restore_temp_tables($restoreid);

        $original  = "This is some test content.\n\n";
        $original .= "$@FEEDBACKINDEX*7@$\n";
        $original .= "<a href='$@FEEDBACKINDEX*9@$'>$@FEEDBACKINDEX*9@$</a>\n\n";
        $original .= "$@FEEDBACKVIEWBYID*6@$\n";
        $original .= "<a href='$@FEEDBACKVIEWBYID*8@$'>$@FEEDBACKVIEWBYID*8@$</a>\n";
        $original .= "$@FEEDBACKVIEWBYID*5@$&name=value\n";
        $original .= "<a href='$@FEEDBACKANALYSISBYID*8@$'>$@FEEDBACKANALYSISBYID*8@$</a>\n";
        $original .= "<a href='$@FEEDBACKSHOWENTRIESBYID*8@$'>$@FEEDBACKSHOWENTRIESBYID*8@$</a>\n";

        $expected  = "This is some test content.\n\n";
        $expected .= "{$www}/mod/feedback/index.php?id=7\n";
        $expected .= "<a href='{$www}/mod/feedback/index.php?id=9'>{$www}/mod/feedback/index.php?id=9</a>\n\n";
        $expected .= "{$www}/mod/feedback/view.php?id=6\n";
        $expected .= "<a href='{$www}/mod/feedback/view.php?id=8'>{$www}/mod/feedback/view.php?id=8</a>\n";
        $expected .= "{$www}/mod/feedback/view.php?id=5&name=value\n";
        $expected .= "<a href='{$www}/mod/feedback/analysis.php?id=8'>{$www}/mod/feedback/analysis.php?id=8</a>\n";
        $expected .= "<a href='{$www}/mod/feedback/show_entries.php?id=8'>{$www}/mod/feedback/show_entries.php?id=8</a>\n";

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
