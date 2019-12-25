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
 * @package    mod_quiz
 * @author     David Curry <david.curry@totaralearning.com>
 */

/**
 * Function for Totara specific DB changes to core Moodle plugins.
 *
 * Put code here rather than in db/upgrade.php if you need to change core
 * Moodle database schema for Totara-specific changes.
 *
 * This is executed during EVERY upgrade. Make sure your code can be
 * re-executed EVERY upgrade without problems.
 *
 * You need to increment the upstream plugin version by .01 to get
 * this code executed!
 *
 * Do not use savepoints in this code!
 *
 * @param string $version the plugin version
 */
function xmldb_quiz_totara_postupgrade($version) {
    global $DB;

    $dbman = $DB->get_manager();

    // TODO - Remove this if moodle ever fix passgrade completions.
    mod_quiz_fix_passgrade_settings();
}

/**
 * previously (before TL-14750) quizzes using the "require passing grade"
 * completion setting were not setting the completiongradeitemnumber column
 * in the course module, leading to the completion status only being marked as
 * COMPLETION_COMPLETE instead of COMPLETION_COMPLETE_PASS/FAIL. This fixes the settings
 * of all of the quizzes currently affected so all future completions will be marked correctly,
 * and previously existing completions should be checked with the completion editor.
 */
function mod_quiz_fix_passgrade_settings() {
    global $DB;

    $sql = " SELECT cm.*, gi.itemnumber
               FROM {course_modules} cm
               JOIN {modules} m
                 ON cm.module = m.id
                AND m.name = :mod
               JOIN {quiz} q
                 ON  cm.instance = q.id
               JOIN {grade_items} gi
                 ON q.id = gi.iteminstance
                AND gi.itemmodule = m.name
                AND gi.itemtype = :type
              WHERE q.completionpass = 1
                AND cm.completiongradeitemnumber IS NULL";
    $quizzes = $DB->get_records_sql($sql, array('mod'=>'quiz', 'type'=>'mod'));

    foreach ($quizzes as $quiz) {
        $quiz->completiongradeitemnumber = $quiz->itemnumber;
        unset($quiz->itemnumber);

        $DB->update_record('course_modules', $quiz);
    }
}
