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
 * @package totara_customfield
 * @category totara_catalog
 */

namespace totara_customfield\totara_catalog;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataholder;
use totara_catalog\provider;
use totara_customfield\totara_catalog\dataformatter\customfield;
use totara_customfield\totara_catalog\dataformatter\customfield_fts;

class dataholder_factory {

    /**
     * Create placeholders for custom fields that belong to the plugin (identified by the $tableprefix).
     *
     * @param string $tableprefix The table prefix of the customfield
     * @param string $prefix The prefix of the custom field
     * @param provider $provider
     * @return dataholder[]
     */
    public static function get_dataholders(string $tableprefix, string $prefix, provider $provider): array {
        global $CFG;
        require_once($CFG->dirroot.'/totara/customfield/fieldlib.php');
        $customfields = customfield_get_fields_definition($tableprefix, ['hidden' => 0]);

        $idfield = "base.{$provider->get_objectid_field()}";

        $dataholders = [];

        foreach ($customfields as $customfield) {
            switch ($customfield->datatype) {
                case 'datetime':
                case 'checkbox':
                    $formatters = [
                        formatter::TYPE_PLACEHOLDER_TEXT => new customfield(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                    ];
                    break;
                case 'menu':
                    $formatters = [
                        formatter::TYPE_FTS => new customfield_fts(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                        formatter::TYPE_PLACEHOLDER_TEXT => new customfield(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                    ];
                    break;
                case 'text':
                    $formatters = [
                        formatter::TYPE_FTS => new customfield_fts(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                        formatter::TYPE_PLACEHOLDER_TEXT => new customfield(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                        formatter::TYPE_PLACEHOLDER_TITLE => new customfield(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                    ];
                    break;
                case 'multiselect':
                    $formatters = [
                        formatter::TYPE_FTS => new customfield_fts(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                    ];
                    break;
                case 'textarea':
                    $formatters = [
                        formatter::TYPE_FTS => new customfield_fts(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                        formatter::TYPE_PLACEHOLDER_RICH_TEXT => new customfield(
                            $customfield->id,
                            $idfield,
                            $tableprefix,
                            $prefix
                        ),
                    ];
                    break;
                case 'location':
                case 'url':
                case 'file':
                default:
                    // Skip this custom field type.
                    continue 2;
            }

            $tablealias = "cf_{$tableprefix}_{$customfield->id}";

            $systemcontent = \context_system::instance();
            $dataholders[] = new dataholder(
                $tablealias,
                format_string($customfield->fullname, true, ['context' => $systemcontent]),
                $formatters,
                [
                    $tablealias => "LEFT JOIN {{$tableprefix}_info_data} {$tablealias}
                                           ON {$tablealias}.{$prefix}id = base.id
                                          AND {$tablealias}.fieldid = :{$tablealias}_data",
                ],
                [$tablealias . '_data' => $customfield->id],
                new \lang_string('customfields', 'totara_customfield')
            );
        }

        return $dataholders;
    }

    /**
     * Get all of the FTS dataholder keys for the given provider.
     *
     * @param string $tableprefix The table prefix of the customfield
     * @param string $prefix The prefix of the custom field
     * @param provider $provider
     * @return string[]
     */
    public static function get_fts_dataholder_keys(string $tableprefix, string $prefix, provider $provider): array {
        $keys = [];

        foreach (self::get_dataholders($tableprefix, $prefix, $provider) as $dataholder) {
            if (array_key_exists(formatter::TYPE_FTS, $dataholder->formatters)) {
                $keys[] = $dataholder->key;
            }
        }

        return $keys;
    }
}
