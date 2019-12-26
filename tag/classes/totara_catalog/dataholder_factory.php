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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package core_tag
 * @category totara_catalog
 */

namespace core_tag\totara_catalog;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\fts;
use totara_catalog\dataformatter\ordered_list;
use totara_catalog\dataholder;

class dataholder_factory {

    /**
     * Get tag data holders
     *
     * @param string $itemtype the tag item type (not objecttype!) that is relevant
     * @return array
     */
    public static function get_dataholders(string $itemtype): array {
        global $CFG, $DB;

        if (empty($CFG->usetags)) {
            return [];
        }

        $areas = \core_tag_area::get_areas();
        $component = array_keys($areas[$itemtype])[0];

        if (!\core_tag_area::is_enabled($component, $itemtype)) {
            return [];
        }

        return [
            new dataholder(
                'ftstags',
                new \lang_string('tags', 'tag'),
                [formatter::TYPE_FTS => new fts('ftstags.tags')],
                [
                    'ftstags' =>
                        "LEFT JOIN (SELECT ti.itemid, {$DB->sql_group_concat('t.name',',')} AS tags
                                      FROM {tag_instance} ti
                                      JOIN {tag} t ON t.id = ti.tagid
                                     WHERE ti.itemtype = '{$itemtype}'
                                     GROUP BY ti.itemid) ftstags
                           ON ftstags.itemid = base.id"
                ]
            ),
            new dataholder(
                'tags',
                new \lang_string('tags', 'tag'),
                [formatter::TYPE_PLACEHOLDER_TEXT => new ordered_list('tags.tags')],
                [
                    'tags' =>
                        "LEFT JOIN (SELECT ti.itemid, {$DB->sql_group_concat('t.rawname',',')} AS tags
                                      FROM {tag_instance} ti
                                      JOIN {tag} t ON t.id = ti.tagid
                                     WHERE ti.itemtype = '{$itemtype}'
                                     GROUP BY ti.itemid) tags
                           ON tags.itemid = base.id"
                ]
            )
        ];
    }
}
