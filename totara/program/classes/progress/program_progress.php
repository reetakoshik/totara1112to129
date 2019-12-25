<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara
 * @subpackage program
 */

namespace totara_program\progress;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara program completion class
 * @since Totara 12
 */
class program_progress {

    /**
     * Returns the progressinfo key prefix for courseset_groups to ensure consistency
     * As a group doesn't have some identifying attribute, we can only standardize the prefix
     *
     * @return string Prefix for courseset_groups
     */
    private static function get_coursesetgroup_key_prefix() {
        return 'coursesetgroup_';
    }

   /**
     * Returns the prefix string to use for courseset groups
     * @param int $id courseset group identifier
     * @return string
     */
    private static function get_coursesetgroup_key($id) {
        return self::get_coursesetgroup_key_prefix() . $id;
    }

    /**
     * Return the progress information for the specified program and user
     *
     * @param int $programid Program id
     * @param int $userid User id
     * @throws \coding_exception If the aggregate function does not generate a progressinfo object.
     * @return \totara_core\progressinfo\progressinfo
     */
    public static function get_user_progressinfo_from_id($programid, $userid=null) {

        global $USER;

        if (is_object($programid)) {
            return self::get_user_progressinfo($programid, $userid);
        }

        if (is_null($userid)) {
            $userid = $USER->id;
        }

        $data = program_progress_cache::get_progressinfo_from_cache($programid, $userid);
        if ($data instanceof \totara_core\progressinfo\progressinfo) {
            return $data;
        }

        // Although calling get_program_progressinfo will again check the cache
        // it is cheaper to check the cache twice than instanciating $program when not
        // necessary
        $program = new \program($programid);
        return self::get_user_progressinfo($program, $userid);
    }


    /**
     * Return the progress information for the specified program and user
     *
     * @param program $program Program instance
     * @param int $userid User id
     * @throws \coding_exception If the aggregate function does not generate a progressinfo object.
     * @return \totara_core\progressinfo\progressinfo
     */
    public static function get_user_progressinfo($program, $userid=null) {

        global $USER;

        if (!is_object($program)) {
            return self::get_user_progressinfo_from_id($program, $userid);
        }

        if (!($program instanceof \program)) {
            throw new \coding_exception("Instance of program expected when retrieving user's program progressinfo");
        }

        if (is_null($userid)) {
            $userid = $USER->id;
        }

        $data = program_progress_cache::get_progressinfo_from_cache($program->id, $userid);
        if ($data instanceof \totara_core\progressinfo\progressinfo) {
            return $data;
        }

        $progressinfo = self::build_and_aggregate_progressinfo($program, $userid);
        program_progress_cache::add_progressinfo_to_cache($program->id, $userid, $progressinfo);

        return $progressinfo;
    }

    /**
     * Build progressinfo for the program and aggregate the user's progress towards completion
     *
     * @param program $program Program to build progressinfo for
     * @param int $userid
     * @return \totara_core\progressinfo\progressinfo $progressinfo
     */
    private static function build_and_aggregate_progressinfo($program, $userid) {
        // first check if the whole program has been completed
        if (prog_is_complete($program->id, $userid)) {
            // Create a completed progressinfo, but don't worry about generating the full structure.
            // We don't need it at this point.
            return \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 1);
        }

        // Get the program content.
        $program_content = $program->get_content();

        if ($program->certifid) {
            // If this is a certification program get course sets for groups on the path the user is on.
            $path = get_certification_path_user($program->certifid, $userid);
        } else {
            // If standard program get the courseset groups (just one path).
            $path = CERTIFPATH_STD;
        }
        $courseset_groups = $program_content->get_courseset_groups($path);

        // Initialize progressinfo
        // Aggregation method over courseset groups is always 'ALL'
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);

        foreach ($courseset_groups as $idx => $courseset_group) {
            $groupkey = self::get_coursesetgroup_key($idx);
            $groupinfo = self::build_courseset_group_progressinfo($courseset_group, $groupkey);
            $progressinfo->attach_criteria($groupkey, $groupinfo);

            // Set the scores for this user
            foreach ($courseset_group as $courseset) {
                $courseset->set_progressinfo_course_scores($groupinfo, $userid);
            }
        }

        // Aggregate score for this user
        $progressinfo->aggregate_score_weight();

        return $progressinfo;
    }


    /**
     * Build progressinfo hierarchy for the courseset group
     *
     * @param prog_content courseset_group
     * @param string groupkey progressinfo key
     * @return totara_progressinfo for aggregation of the courseset groups
     */
    private static function build_courseset_group_progressinfo($courseset_group, $groupkey) {
        // ANDed coursesets (ALL aggregation) must be on lower level than ORs (ANY aggregation)
        // as aggregation is done depth first
        $groupinfo = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);

        // We may not need the OR, but creating it now to have it if we encounter an ORed courseset
        $orinfo = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ANY);

        $andidx = 0;
        $curinfo = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);
        $curkey = $groupkey . '_and_'.$andidx;

        foreach ($courseset_group as $courseset) {
            $setinfo = $courseset->build_progressinfo();
            $curinfo->attach_criteria($courseset->get_progressinfo_key(), $setinfo);

            if ($courseset->nextsetoperator == NEXTSETOPERATOR_OR) {
                // And will be added to the correct set outside the for loop
                // Then should be in a separate courseset group
                // If there is an OR, we need to add the ANDs below the OR to ensure precedence
                $orinfo->attach_criteria($curkey, $curinfo);
                $andidx += 1;
                $curkey = $groupkey . '_and_'.$andidx;
                $curinfo = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);
            }
        }

        if ($orinfo->count_criteria() > 0) {
            // There is at least 1 ORed courseset, therefore need the OR over all the ANDs
            $orinfo->attach_criteria($curkey, $curinfo);
            $groupinfo->attach_criteria($groupkey . '_or', $orinfo);
        } else {
            // No ORed courseset - just attach the AND info directly to the group
            $groupinfo->attach_criteria($curkey, $curinfo);
        }

        return $groupinfo;
    }
}
