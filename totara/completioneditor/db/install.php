<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_completioneditor
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_totara_completioneditor_install() {
    // I hope this only gets run once, because repeating it will create additional logs. But at least
    // they're benign and would still be accurate.
    totara_completioneditor_install_log_existing_module_completions();
    totara_completioneditor_install_log_existing_criteria_completions();
    totara_completioneditor_install_log_existing_history_completions();
    totara_completioneditor_install_log_existing_current_completions();
}

/**
 * Write transaction logs for all existing current completion records. Does not check if the logs are needed or not!!!
 */
function totara_completioneditor_install_log_existing_current_completions() {
    global $DB, $USER;

    $now = time();

    $description = $DB->sql_concat(
        "'Log existing current completion during upgrade<br/><ul>'",
        "'<li>Status: '",
        $DB->sql_cast_2char("status"),
        "'</li>'",
        "'<li>Time enrolled: '",
        $DB->sql_cast_2char("timeenrolled"),
        "'</li>'",
        "'<li>Time started: '",
        $DB->sql_cast_2char("timestarted"),
        "'</li>'",
        "'<li>Time completed: '",
        "COALESCE(" . $DB->sql_cast_2char("timecompleted") . ", '')",
        "'</li>'",
        "'<li>RPL: '",
        "COALESCE(" . $DB->sql_cast_2char("rpl") . ", '')",
        "'</li>'",
        "'<li>RPL Grade: '",
        "COALESCE(" . $DB->sql_cast_2char("rplgrade") . ", '')",
        "'</li>'",
        "'<li>Reaggregate: '",
        $DB->sql_cast_2char("reaggregate"),
        "'</li>'",
        "'</ul>'"
    );

    $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
            SELECT course, userid, :changeuserid, {$description}, :now
              FROM {course_completions}";
    $params = array('changeuserid' => $USER->id, 'now' => $now);

    $DB->execute($sql, $params);
}

/**
 * Write transaction logs for all existing history completion records. Does not check if the logs are needed or not!!!
 */
function totara_completioneditor_install_log_existing_history_completions() {
    global $DB, $USER;

    $now = time();

    $description = $DB->sql_concat(
        "'Log existing history completion during upgrade<br/><ul>'",
        "'<li>CCHID: '",
        $DB->sql_cast_2char("id"),
        "'</li>'",
        "'<li>Time completed: '",
        "COALESCE(" . $DB->sql_cast_2char("timecompleted") . ", '')",
        "'</li>'",
        "'<li>Grade: '",
        "COALESCE(" . $DB->sql_cast_2char("grade") . ", '')",
        "'</li>'",
        "'</ul>'"
    );

    $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
            SELECT courseid, userid, :changeuserid, {$description}, :now
              FROM {course_completion_history}";
    $params = array('changeuserid' => $USER->id, 'now' => $now);

    $DB->execute($sql, $params);
}

/**
 * Write transaction logs for all existing crit compl records. Does not check if the logs are needed or not!!!
 */
function totara_completioneditor_install_log_existing_criteria_completions() {
    global $DB, $USER;

    $now = time();

    $description = $DB->sql_concat(
        "'Log existing crit compl during upgrade<br/><ul>'",
        "'<li>CCCCID: '",
        $DB->sql_cast_2char("id"),
        "'</li>'",
        "'<li>Grade final: '",
        "COALESCE(" . $DB->sql_cast_2char("gradefinal") . ", '')",
        "'</li>'",
        "'<li>Unenroled: '",
        "COALESCE(" . $DB->sql_cast_2char("unenroled") . ", '')",
        "'</li>'",
        "'<li>RPL: '",
        "COALESCE(" . $DB->sql_cast_2char("rpl") . ", '')",
        "'</li>'",
        "'<li>Time completed: '",
        "COALESCE(" . $DB->sql_cast_2char("timecompleted") . ", '')",
        "'</li>'",
        "'</ul>'"
    );

    $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
            SELECT course, userid, :changeuserid, {$description}, :now
              FROM {course_completion_crit_compl}";
    $params = array('changeuserid' => $USER->id, 'now' => $now);

    $DB->execute($sql, $params);
}

/**
 * Write transaction logs for all existing module completion records. Does not check if the logs are needed or not!!!
 */
function totara_completioneditor_install_log_existing_module_completions() {
    global $DB, $USER;

    $now = time();

    $description = $DB->sql_concat(
        "'Log existing module completion during upgrade<br/><ul>'",
        "'<li>CMCID: '",
        $DB->sql_cast_2char("cmc.id"),
        "'</li>'",
        "'<li>Completion state: '",
        $DB->sql_cast_2char("cmc.completionstate"),
        "'</li>'",
        "'<li>Viewed: '",
        "COALESCE(" . $DB->sql_cast_2char("cmc.viewed") . ", '')",
        "'</li>'",
        "'<li>Time modified: '",
        $DB->sql_cast_2char("cmc.timemodified"),
        "'</li>'",
        "'<li>Time completed: '",
        "COALESCE(" . $DB->sql_cast_2char("cmc.timecompleted") . ", '')",
        "'</li>'",
        "'<li>Reaggregate: '",
        $DB->sql_cast_2char("cmc.reaggregate"),
        "'</li>'",
        "'</ul>'"
    );

    $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
            SELECT cm.course, cmc.userid, :changeuserid, {$description}, :now
              FROM {course_modules_completion} cmc
              JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid";
    $params = array('changeuserid' => $USER->id, 'now' => $now);

    $DB->execute($sql, $params);
}
