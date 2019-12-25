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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_gap
 */

/**
 * Check if current $USER can edit aspirational position of given user
 * @param int $userid
 * @return boolean true if $USER can edit aspirational position of $userid
 */
function totara_gap_can_edit_aspirational_position($userid) {
    global $USER;

    // can assign any user's position
    if (has_capability('totara/gap:assignaspirationalposition', context_system::instance())) {
        return true;
    }

    if (!empty($userid) && $userid > 0) {
        $personalcontext = context_user::instance($userid);

        // can assign this particular user's position
        if (has_capability('totara/gap:assignaspirationalposition', $personalcontext)) {
            return true;
        }

        // editing own position and have capability to assign own position
        if ($USER->id == $userid && has_capability('totara/gap:assignselfaspirationalposition', context_system::instance())) {
            return true;
        }
    }
    return false;
}

/**
 * Get data of assigned aspirational position (+ position fullname)
 * @param int $userid
 * @return stdClass|boolean with position data or false if position not set
 */
function totara_gap_get_aspirational_position($userid) {
    global $DB;
    if ($userid < 1) {
        return false;
    }
    $pos = $DB->get_record_sql("
        SELECT ga.*, p.fullname
        FROM {pos} p 
        INNER JOIN {gap_aspirational} ga ON (ga.positionid = p.id) 
        WHERE ga.userid = :userid",
        array('userid' => $userid)
    );
    return $pos;
}

/**
 * Set record saving aspirational position
 * @param int $userid
 * @param int $positionid
 */
function totara_gap_assign_aspirational_position($userid, $positionid) {
    global $USER, $DB;
    if ($userid < 1) {
        throw new coding_exception("User id missing for aspirational position");
    }
    if ($positionid < 1) {
        $DB->delete_records('gap_aspirational', array('userid' => $userid));
        return;
    }

    $asppos = new stdClass();
    $asppos->userid = $userid;
    $asppos->positionid = $positionid;
    $asppos->usermodified = $USER->id;
    $asppos->timemodified = time();

    $trans = $DB->start_delegated_transaction();

    $existspos = $DB->get_record('gap_aspirational', array('userid' => $userid));
    if ($existspos) {
        $asppos->id = $existspos->id;
        $DB->update_record('gap_aspirational', $asppos);
    } else {
        $asppos->timecreated = time();
        $DB->insert_record('gap_aspirational', $asppos);
    }

    $trans->allow_commit();
}