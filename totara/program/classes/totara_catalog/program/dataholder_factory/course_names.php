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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program\dataholder_factory;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\fts;
use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;

/**
 * Names of the courses in programs.
 */
class course_names extends dataholder_factory {

    public static function get_dataholders(): array {
        global $DB;

        $fullnames = $DB->sql_group_concat('course_fullnames_c.fullname', ', ');
        $shortnames = $DB->sql_group_concat('course_shortnames_c.shortname', ', ');

        return [
            new dataholder(
                'course_fullnames',
                'not used course_fullnames',
                [
                    formatter::TYPE_FTS => new fts(
                        'course_fullnames.fullnames'
                    ),
                ],
                [
                    'course_fullnames' =>
                        "LEFT JOIN (SELECT course_fullnames_pcs.programid, {$fullnames} AS fullnames
                                      FROM {prog_courseset} course_fullnames_pcs
                                      JOIN {prog_courseset_course} course_fullnames_pcsc
                                        ON course_fullnames_pcsc.coursesetid = course_fullnames_pcs.id
                                      JOIN {course} course_fullnames_c
                                        ON course_fullnames_c.id = course_fullnames_pcsc.courseid
                                     GROUP BY course_fullnames_pcs.programid) course_fullnames
                                ON course_fullnames.programid = base.id",
                ]
            ),
            new dataholder(
                'course_shortnames',
                'not used course_shortnames',
                [
                    formatter::TYPE_FTS => new fts(
                        'course_shortnames.shortnames'
                    ),
                ],
                [
                    'course_shortnames' =>
                        "LEFT JOIN (SELECT course_shortnames_pcs.programid, {$shortnames} AS shortnames
                                      FROM {prog_courseset} course_shortnames_pcs
                                      JOIN {prog_courseset_course} course_shortnames_pcsc
                                        ON course_shortnames_pcsc.coursesetid = course_shortnames_pcs.id
                                      JOIN {course} course_shortnames_c
                                        ON course_shortnames_c.id = course_shortnames_pcsc.courseid
                                     GROUP BY course_shortnames_pcs.programid) course_shortnames
                                ON course_shortnames.programid = base.id",
                ]
            ),
        ];
    }
}
