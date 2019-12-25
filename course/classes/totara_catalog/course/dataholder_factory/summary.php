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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course\dataholder_factory;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\fts;
use totara_catalog\dataformatter\textarea;
use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;

class summary extends dataholder_factory {

    public static function get_dataholders(): array {
        return [
            new dataholder(
                'summary_fts',
                new \lang_string('summary', 'moodle'),
                [
                    formatter::TYPE_FTS => new fts(
                        'base.summary'
                    ),
                ]
            ),
            new dataholder(
                'summary_rich',
                new \lang_string('summary', 'moodle'),
                [
                    formatter::TYPE_PLACEHOLDER_RICH_TEXT => new textarea(
                        'base.summary',
                        'summary_rich_ctx.id',
                        "'course'",
                        "'summary'",
                        'NULL'
                    ),
                ],
                [
                    'summary_rich_ctx' =>
                        'JOIN {context} summary_rich_ctx
                           ON summary_rich_ctx.instanceid = base.id
                          AND summary_rich_ctx.contextlevel = :summary_rich_contextcourse',
                ],
                [
                    'summary_rich_contextcourse' => CONTEXT_COURSE,
                ]
            )
        ];
    }
}
