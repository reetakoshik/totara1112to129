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
use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;
use totara_program\totara_catalog\program\dataformatter\progressbar as program_progressbar_formatter;

class progressbar extends dataholder_factory {

    public static function get_dataholders(): array {
        global $USER;

        return [
            new dataholder(
                'progressbar',
                'notused',
                [
                    formatter::TYPE_PLACEHOLDER_PROGRESS => new program_progressbar_formatter(
                        'progressbar_pc.programid'
                    ),
                ],
                [
                    'progressbar_pc' =>
                        'LEFT JOIN {prog_completion} progressbar_pc
                           ON progressbar_pc.programid = base.id
                          AND progressbar_pc.coursesetid = 0
                          AND progressbar_pc.userid = :progressbar_userid',
                ],
                [
                    'progressbar_userid' => $USER->id,
                ]
            )
        ];
    }
}
