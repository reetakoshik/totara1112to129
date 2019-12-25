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
 * @package totara_certification
 */

// TL-12606 Recalculate non-zero course set group completion records.
function totara_certification_upgrade_non_zero_prog_completions() {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/totara/program/lib.php');

    // Magic number 2 is STATUS_COURSESET_INCOMPLETE.
    $sql = "DELETE FROM {prog_completion}
             WHERE status = 2
               AND timestarted = 0
               AND timedue = 0
               AND timecompleted = 0
               AND coursesetid <> 0
               AND programid IN (SELECT id
                                   FROM {prog}
                                  WHERE certifid IS NOT NULL)";
    $DB->execute($sql);
}

// TL-16521 Reset cert messages which were not reset when TL-10979 was added.
function totara_certification_upgrade_reset_messages() {
    global $DB;

    // 1) Reset all message types where the window is open, and the messages were sent before window open.
    $messagetypes = array(
        MESSAGETYPE_PROGRAM_COMPLETED,
        MESSAGETYPE_PROGRAM_DUE,
        MESSAGETYPE_PROGRAM_OVERDUE,
        MESSAGETYPE_COURSESET_DUE,
        MESSAGETYPE_COURSESET_OVERDUE,
        MESSAGETYPE_COURSESET_COMPLETED,
        MESSAGETYPE_RECERT_WINDOWOPEN,
        MESSAGETYPE_RECERT_WINDOWDUECLOSE,
        MESSAGETYPE_RECERT_FAILRECERT,
        MESSAGETYPE_LEARNER_FOLLOWUP,
    );
    list($messagetypesql, $params) = $DB->get_in_or_equal($messagetypes, SQL_PARAMS_NAMED);

    $sql = "SELECT DISTINCT pml.id
              FROM {prog_messagelog} pml
              JOIN {prog_message} pm
                ON pm.id = pml.messageid AND pm.messagetype {$messagetypesql}
              JOIN {prog} p ON pm.programid = p.id
              JOIN {certif_completion} cc ON cc.certifid = p.certifid AND cc.userid = pml.userid
             WHERE pml.timeissued < cc.timewindowopens
               AND cc.renewalstatus = :due";
    $params['due'] = CERTIFRENEWALSTATUS_DUE;

    $pmlrs = $DB->get_recordset_sql($sql, $params);
    foreach ($pmlrs as $pml) {
        $DB->delete_records('prog_messagelog', array('id' => $pml->id));
    }
    $pmlrs->close();

    // 2) Reset messages where the user is expired and the message type couldn't have been sent since window opened.
    $messagetypes = array(
        MESSAGETYPE_PROGRAM_COMPLETED,
        MESSAGETYPE_PROGRAM_OVERDUE,
        MESSAGETYPE_RECERT_WINDOWOPEN,
        MESSAGETYPE_RECERT_FAILRECERT,
        MESSAGETYPE_LEARNER_FOLLOWUP,
    );
    list($messagetypesql, $params) = $DB->get_in_or_equal($messagetypes, SQL_PARAMS_NAMED);

    $sql = "SELECT DISTINCT pml.id
              FROM {prog_messagelog} pml
              JOIN {prog_message} pm
                ON pm.id = pml.messageid AND pm.messagetype {$messagetypesql}
              JOIN {prog} p ON pm.programid = p.id
              JOIN {certif_completion} cc ON cc.certifid = p.certifid AND cc.userid = pml.userid
              JOIN {prog_completion} pc ON pc.programid = p.id AND pc.userid = pml.userid AND pc.coursesetid = 0
             WHERE pml.timeissued < pc.timedue
               AND cc.renewalstatus = :expired";
    $params['expired'] = CERTIFRENEWALSTATUS_EXPIRED;

    $pmlrs = $DB->get_recordset_sql($sql, $params);
    foreach ($pmlrs as $pml) {
        $DB->delete_records('prog_messagelog', array('id' => $pml->id));
    }
    $pmlrs->close();

    // 3) Reset messages where the user is certified and the message type couldn't have been sent since window opened.
    $messagetypes = array(
        MESSAGETYPE_PROGRAM_COMPLETED,
        MESSAGETYPE_RECERT_WINDOWOPEN,
        MESSAGETYPE_LEARNER_FOLLOWUP,
    );
    list($messagetypesql, $params) = $DB->get_in_or_equal($messagetypes, SQL_PARAMS_NAMED);

    $sql = "SELECT DISTINCT pml.id
              FROM {prog_messagelog} pml
              JOIN {prog_message} pm
                ON pm.id = pml.messageid AND pm.messagetype {$messagetypesql}
              JOIN {prog} p ON pm.programid = p.id
              JOIN {certif_completion} cc ON cc.certifid = p.certifid AND cc.userid = pml.userid
             WHERE pml.timeissued < cc.timecompleted
               AND cc.renewalstatus = :notdue";
    $params['notdue'] = CERTIFRENEWALSTATUS_NOTDUE;

    $pmlrs = $DB->get_recordset_sql($sql, $params);
    foreach ($pmlrs as $pml) {
        $DB->delete_records('prog_messagelog', array('id' => $pml->id));
    }
    $pmlrs->close();
}
