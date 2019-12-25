<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\search_metadata;

use totara_catalog\dataformatter\{fts, formatter};
use totara_catalog\dataholder;

defined('MOODLE_INTERNAL') || die();

/**
 * A factory class to provide the dataholder for search_metadata.
 */
final class search_metadata_dataholder_factory {
    /**
     * @param string $component
     *
     * @return dataholder[]
     */
    public static function get_dataholders(string $component): array {
        $cleancomponent = clean_param($component, PARAM_COMPONENT);

        if ($component !== $cleancomponent) {
            throw new \coding_exception("Invalid component name: '{$component}'");
        }

        [$plugintype, $pluginname] = \core_component::normalize_component($cleancomponent);
        $tablename = search_metadata::DBTABLE;

        return [
            new dataholder(
                'search_metadata',
                new \lang_string('searchmetadata', 'totara_catalog'),
                [formatter::TYPE_FTS => new fts('search_metadata.value')],
                [
                    'search_metadata' =>
                        "LEFT JOIN {{$tablename}} search_metadata 
                         ON search_metadata.instanceid = base.id 
                         AND search_metadata.plugintype = '{$plugintype}' 
                         AND search_metadata.pluginname = '{$pluginname}'"
                ]
            )
        ];
    }
}