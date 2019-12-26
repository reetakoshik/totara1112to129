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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Iterates over all of the custom fields for the given field prefix and gives them sequential sortorders.
 *
 * This function is designed to fix the sort orders of custom fields used within hierarchies.
 * Due to a bug in Totara the sort order may not be sequential, there may be duplicates, and there
 * may be gaps.
 *
 * Rather than identify the problem records this function simply ensures consistent sorting of custom
 * fields by reading them out in a prescribed way and then ensuring sequential sort orders exist.
 * The order it reads out is based on code that exists at the time of writing.
 *
 * @param string $tableprefix
 */
function totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix) {
    global $DB;

    $table = $tableprefix.'_info_field';

    $rs = $DB->get_recordset($table, [], 'typeid ASC, sortorder ASC, id ASC', 'id,typeid,sortorder');
    $fieldsbytype = array();
    foreach ($rs as $field) {
        $typeid = $field->typeid;
        if (!isset($fieldsbytype[$typeid])) {
            $fieldsbytype[$typeid] = [$field];
        } else {
            $fieldsbytype[$typeid][] = $field;
        }
    }
    $rs->close();
    unset($rs);

    foreach ($fieldsbytype as $typeid => $fields) {
        $sortorder = 1;
        foreach ($fields as $field) {
            if ($field->sortorder != $sortorder) {
                $field->sortorder = $sortorder;
                $DB->update_record($table, $field, true);
            }
            $sortorder++;
        }
        // Explicitly unset this object to reduce memory as we progress.
        // Shouldn't be needed, but doesn't hurt!
        unset($fieldsbytype[$typeid]);
    }
}

/*
 * TL-20233 Convert old "extrainfo" into the new format.
 *
 * There are three possible states:
 * - NULL - if there is no extrainfo then the assignment is current and reason is in the assignment
 * - OLD:x,y - x is the assigntype, y is the rule instance id (rule may not be reason, but we don't know)
 * - PAR:x,z - x is the assigntype, z is the assignmentid - audience cannot have PAR
 *
 * We upgrade NULL to the new format.
 * We don't touch OLD, because we don't know the real reason. Instead, these are handled separately when displayed.
 * We change PAR to OLD because this will happen anyway, but in upgrade it is controlled, and real code can stay clean.
 */
function totara_hierarchy_upgrade_user_assignment_extrainfo() {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

    // Upgrade NULL to "ITEM:x,y,z" where x is the assigntype, y is the assignmentid and z is the reason instance id.
    $itemid = "(SELECT grp.posid FROM {goal_grp_pos} grp WHERE grp.id = {goal_user_assignment}.assignmentid)";
    $sql = "UPDATE {goal_user_assignment}
               SET extrainfo = " . $DB->sql_concat("'ITEM:'", 'assigntype', "','", 'assignmentid', "','", $itemid) . "
             WHERE assigntype = :assigntype
               AND extrainfo IS NULL
    ";
    $params = ['assigntype' => GOAL_ASSIGNMENT_POSITION];
    $DB->execute($sql, $params);

    $itemid = "(SELECT grp.orgid FROM {goal_grp_org} grp WHERE grp.id = {goal_user_assignment}.assignmentid)";
    $sql = "UPDATE {goal_user_assignment}
               SET extrainfo = " . $DB->sql_concat("'ITEM:'", 'assigntype', "','", 'assignmentid', "','", $itemid) . "
             WHERE assigntype = :assigntype
               AND extrainfo IS NULL
    ";
    $params = ['assigntype' => GOAL_ASSIGNMENT_ORGANISATION];
    $DB->execute($sql, $params);

    $itemid = "(SELECT grp.cohortid FROM {goal_grp_cohort} grp WHERE grp.id = {goal_user_assignment}.assignmentid)";
    $sql = "UPDATE {goal_user_assignment}
               SET extrainfo = " . $DB->sql_concat("'ITEM:'", 'assigntype', "','", 'assignmentid', "','", $itemid) . "
             WHERE assigntype = :assigntype
               AND extrainfo IS NULL
    ";
    $params = ['assigntype' => GOAL_ASSIGNMENT_AUDIENCE];
    $DB->execute($sql, $params);

    // Mark PAR records as OLD, because they would anyway next time cron runs (both before or after this upgrade).
    $itemid = "(SELECT grp.posid FROM {goal_grp_pos} grp WHERE grp.id = {goal_user_assignment}.assignmentid)";
    $sql = "UPDATE {goal_user_assignment}
               SET extrainfo = " . $DB->sql_concat("'OLD:'", 'assigntype', "','", $itemid) . ",
                   assigntype = :individualtype,
                   assignmentid = 0,
                   timemodified = :timemodified,
                   usermodified = :usermodified
             WHERE assigntype = :assigntype
               AND " . $DB->sql_like('extrainfo', ':par');
    $params = [
        'individualtype' => GOAL_ASSIGNMENT_INDIVIDUAL,
        'timemodified' => time(),
        'usermodified' => $USER->id,
        'assigntype' => GOAL_ASSIGNMENT_POSITION,
        'par' => "PAR%",
    ];
    $DB->execute($sql, $params);

    $itemid = "(SELECT grp.orgid FROM {goal_grp_org} grp WHERE grp.id = {goal_user_assignment}.assignmentid)";
    $sql = "UPDATE {goal_user_assignment}
               SET extrainfo = " . $DB->sql_concat("'OLD:'", 'assigntype', "','", $itemid) . ",
                   assigntype = :individualtype,
                   assignmentid = 0,
                   timemodified = :timemodified,
                   usermodified = :usermodified
             WHERE assigntype = :assigntype
               AND " . $DB->sql_like('extrainfo', ':par');
    $params = [
        'individualtype' => GOAL_ASSIGNMENT_INDIVIDUAL,
        'timemodified' => time(),
        'usermodified' => $USER->id,
        'assigntype' => GOAL_ASSIGNMENT_ORGANISATION,
        'par' => "PAR%",
    ];
    $DB->execute($sql, $params);

    // There are no audience PAR records (audiences don't have hierarchies).

    // After upgrade, we only have OLD:x,z (which we can't match to an original assignment) and ITEM:x,y,z records.
}

/**
 * Used by the environment check on install/upgrade (and for admins going to Server -> Environment in the admin menu).
 *
 * This checks if there are any scales on the site where proficient scale values are not in correct order (that is, all
 * proficient scale values at the top and all non-proficient values below them).
 *
 * Upgrade to Totara 13 will be blocked if any incorrectly ordered scales are found.
 *
 * @param environment_results $result
 * @return environment_results|null
 */
function totara_hierarchy_check_comp_value_order(environment_results $result) {
    global $DB;

    $dbman = $DB->get_manager();
    $table = new xmldb_table('comp_scale_values');
    if (!$dbman->table_exists($table)) {
        // We're likely on a fresh install, so there's no problems.
        return null;
    }

    $result->info = 'competency_value_order';

    // The result below will have values for the scales together within that, ordered from top sortorder.
    $values = $DB->get_records('comp_scale_values', null, 'scaleid, sortorder ASC');

    if (empty($values)) {
        return null;
    }

    $current_scale_id = reset($values)->scaleid;
    $expect_not_proficient = false;

    foreach ($values as $value) {
        if ($current_scale_id != $value->scaleid) {
            // We take this to mean we've moved on to values for another scale.
            $current_scale_id = $value->scaleid;
            $expect_not_proficient = false;
        }

        if ($value->proficient) {
            if ($expect_not_proficient) {
                // So we've come across a proficient value. But we'd already seen a not proficient one for this
                // scale id.
                // We can answer of the question of whether all the values are in order (they're not).
                // No need to check any futher.
                $result->setRestrictStr(array('competencyscaleupgradeorder', 'totara_hierarchy'));
                $result->setStatus(false);
                return $result;
            }
        } else {
            // If it wasn't proficient, then the rest of the values for this scale shouldn't be proficient.
            $expect_not_proficient = true;
        }
    }

    // We've made it through all the values and proficient fields were all as expected.
    return null;
}
