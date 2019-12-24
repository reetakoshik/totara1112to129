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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_hierarchy
 */

namespace hierarchy_competency\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;


defined('MOODLE_INTERNAL') || die();

/**
 * Handler for the tracking of a user's progress towards achieving a competency.
 */
class competency_progress extends item {
    /**
     * {@inheritDoc}
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    protected static function purge(target_user $user, \context $unused) {
        global $DB;

        $params = ['userid' => $user->get_user_record()->id];
        $DB->delete_records('comp_criteria_record', $params);

        return self::RESULT_STATUS_SUCCESS;
    }


    /**
     * {@inheritDoc}
     */
    public static function is_exportable() {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;
        $params = ['userid' => $user->get_user_record()->id];
        $filter = "
            SELECT c.shortname, cc.itemtype, ccr.timecreated
              FROM {comp_criteria_record} ccr
              JOIN {comp_criteria} cc ON ccr.itemid = cc.id
              JOIN {comp} c ON ccr.competencyid = c.id
             WHERE ccr.userid = :userid
        ";

        $export = new export();
        foreach ($DB->get_records_sql($filter, $params) as $competency) {
            $export->data[] = [
                'competency' => $competency->shortname,
                'criteria' => $competency->itemtype,
                'created on' => $competency->timecreated
            ];
        }

        return $export;
    }


    /**
     * {@inheritDoc}
     */
    public static function is_countable() {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $params = ['userid' => $user->get_user_record()->id];
        return $DB->count_records('comp_criteria_record', $params);
    }
}
