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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_program
 */

namespace totara_program;

defined('MOODLE_INTERNAL') || die();

/**
 * Report Builder program course sortorder helper
 *
 * This class is designed to aid report builder report sources that are displaying concatenated
 * course information and need to ensure that the courses are correctly ordered across all columns.
 *
 * This class also acts as a cache data source so that it can seamlessly load
 *
 * @internal
 * @deprecated since Totara 12, will be removed once MSSQL 2017 is the minimum required version.
 */
final class rb_course_sortorder_helper implements \cache_data_source {

    /**
     * Returns a string to use as the field argument for an rb_column_option instance.
     *
     * @param string $field
     * @param string $programid db field
     * @param string $courseid db filed
     * @return string
     */
    public static function get_column_field_definition(string $field, string $programid = 'program.id', string $courseid = 'course.id'): string {
        global $DB;
        return 'COALESCE(' . $DB->sql_concat($programid, "'|'", $courseid, "'|'", 'COALESCE(' . $field . ', \'-\')') . ', \'-\')';
    }

    /**
     * Invalidates the required cache when program content is updated.
     *
     * @param event\program_contentupdated $event
     */
    public static function handle_program_contentupdated(event\program_contentupdated $event): void {
        $programid = $event->objectid;
        $cache = self::get_cache();
        $cache->delete($programid);
    }

    /**
     * Invalidates the required cache when a program is deleted.
     *
     * @param event\program_contentupdated $event
     */
    public static function handle_program_deleted(event\program_deleted $event): void {
        $programid = $event->objectid;
        $cache = self::get_cache();
        $cache->delete($programid);
    }

    /**
     * Returns the course sortorder for the program with the given id.
     *
     * The cache uses a data source, as such the request to get data will never fail.
     * If the cache does not contain the required data then {@see self::load_for_cache()} will be
     * called to load it.
     *
     * @param int $programid
     * @return int[]
     */
    public static function get_sortorder($programid): array {
        $cache = self::get_cache();
        return $cache->get($programid);
    }

    /**
     * Returns an instance of the course order cache.
     *
     * @return \cache_loader
     */
    private static function get_cache(): \cache_loader {
        return \cache::make('totara_program', 'course_order');
    }

    /**
     * Loads the data for the given program so that it can be cached and returned.
     *
     * This is part of cache data source interface.
     *
     * @param int|string $programid
     * @return int[]
     */
    public function load_for_cache($programid): array {
        global $DB;
        $sql = 'SELECT pcc.id, pcc.courseid
                  FROM {prog_courseset_course} pcc
                  JOIN {prog_courseset} pc ON pcc.coursesetid = pc.id
                WHERE pc.programid = :programid
                ORDER BY pc.sortorder ASC, pcc.id ASC';
        $params = ['programid' => $programid];
        $order = $DB->get_records_sql_menu($sql, $params);
        return $order;
    }

    /**
     * Loads the data for all of the given programs so that it can be cached and returned.
     *
     * This is part of cache data source interface.
     *
     * @param int|string $programid
     * @return int[]
     */
    public function load_many_for_cache(array $keys): array {
        global $DB;

        $return = [];
        // Ensure all keys are present, even if we don't get a result from the database we have a result that we want to store.
        foreach ($keys as $key) {
            $return[$key] = [];
        }

        list ($programidin, $params) = $DB->get_in_or_equal($keys, SQL_PARAMS_NAMED);
        $sql = "SELECT pc.programid, pcc.id, pcc.courseid
                  FROM {prog_courseset_course} pcc
                  JOIN {prog_courseset} pc ON pcc.coursesetid = pc.id
                WHERE pc.programid {$programidin}
                ORDER BY pc.programid, pc.sortorder ASC, pcc.id ASC";
        $result = $DB->get_records_sql($sql, $params);

        foreach ($result as $row) {
            $programid = $row->programid;
            $coursesetid = $row->courseid;
            $courseid = $row->id;

            $return[$programid][$coursesetid] = $courseid;
        }

        return $return;
    }

    /**
     * Returns an instance of self for the cache system.
     *
     * @param \cache_definition $definition
     * @return rb_course_sortorder_helper
     */
    public static function get_instance_for_cache(\cache_definition $definition): rb_course_sortorder_helper {
        return new rb_course_sortorder_helper;
    }
}
