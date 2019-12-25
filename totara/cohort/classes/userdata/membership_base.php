<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Class membership_base
 *
 * This is the base class for items that purge, export and count audience membership.
 */
abstract class membership_base extends item {

    /**
     * This item allows purging for any user status.
     *
     * @param int $userstatus
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * This item allows exporting.
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * This item allows counting.
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * This item can be executed within course category or system contexts.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_COURSECAT, CONTEXT_SYSTEM];
    }

    /**
     * Context aware method for returning data relating to audience membership.
     *
     * @param string $fields An SQL snippet to be added after the SELECT when querying the database.
     * @param target_user $user as supplied to the purge, export or count methods.
     * @param \context $context as supplied to the purge, export or count methods.
     * @param int $cohorttype should be either \cohort::TYPE_STATIC or \cohort::TYPE_DYNAMIC
     * @return array of records containing fields specified in the $fields parameter.
     */
    protected static function get_memberships($fields, target_user $user, \context $context, int $cohorttype) {
        global $DB;

        switch ($context->contextlevel) {
            case CONTEXT_SYSTEM:
                $contextjoin = '';
                break;
            case CONTEXT_COURSECAT:
                $contextjoin = ' JOIN {context} ctx ON co.contextid = ctx.id ';
                break;
            default:
                throw new \coding_exception('Unexpected context');
        }

        $sql = 'SELECT ' . $fields . '
                FROM {cohort_members} cm
                JOIN {cohort} co
                ON cm.cohortid = co.id 
                ' . $contextjoin . '
                WHERE cm.userid = :userid
                  AND co.cohorttype = :cohorttype';
        $params = ['userid' => $user->id, 'cohorttype' => $cohorttype];

        if ($context->contextlevel == CONTEXT_COURSECAT) {
            $sql .= ' AND ctx.id = :contextid';
            $params['contextid'] = $context->id;
        }

        return $DB->get_records_sql($sql, $params);
    }

    protected static function unassign_roles_in_cohort(\stdClass $cohort, target_user $user) {
        global $DB;

        // There should only be one of each such record. But we won't rely on assumptions for this.
        $roleassignments = $DB->get_records(
            'role_assignments',
            ['userid' => $user->id, 'component' =>  'totara_cohort', 'itemid'=> $cohort->id]
        );

        foreach ($roleassignments as $roleassignment) {
            role_unassign(
                $roleassignment->roleid,
                $roleassignment->userid,
                $roleassignment->contextid,
                'totara_cohort',
                $roleassignment->itemid
            );
        }
    }
}
