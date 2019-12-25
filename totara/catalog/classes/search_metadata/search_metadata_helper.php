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
defined('MOODLE_INTERNAL') || die();

/**
 * A helper class to add/load/remove the search_metadata in the database
 */
final class search_metadata_helper {
    /**
     * Adding or updating the search_metadata(s) in database. If updating the search_metadata, then empty string value
     * ($value) that is passing in will result in deleting search_metadata record.
     *
     * If creating a new search_metadata, then it will skip if empty string value is being passed in.
     *
     * @param string $value
     * @param string $component
     * @param int    $instanceid
     * @return void
     */
    public static function process_searchmetadata(string $value, string $component, int $instanceid): void {
        $metadata = static::find_searchmetadata($component, $instanceid);

        if (null !== $metadata) {
            if (empty($value)) {
                // If it is an empty $value, then we delete it.
                $metadata->delete();
            } else {
                if ($metadata->value === $value) {
                    // The value of $metadata and $value are the same
                    return;
                }

                $metadata->set_value($value);
                $metadata->save();
            }

            return;
        }

        if (empty($value)) {
            // This is most likely we are going to create a new record for search_metadata, though the value is empty.
            // So we should skip it anyway.
            return;
        }

        [$plugintype, $pluginname] = \core_component::normalize_component($component);

        $metadata = new search_metadata();
        $metadata->set_instanceid($instanceid);
        $metadata->set_pluginname($pluginname);
        $metadata->set_plugintype($plugintype);
        $metadata->set_value($value);
        $metadata->save();
    }

    /**
     * Given the full qualified $component parameter (for example: core_course) and an instanceid, then this helper
     * function will be able to find they search_metadata for us.
     *
     * @param string $component
     * @param int    $instanceid
     *
     * @return search_metadata|null
     */
    public static function find_searchmetadata(string $component, int $instanceid): ?search_metadata {
        global $DB;
        [$plugintype, $pluginname] = \core_component::normalize_component($component);

        $record = $DB->get_record(
            search_metadata::DBTABLE,
            [
                'instanceid' => $instanceid,
                'pluginname' => $pluginname,
                'plugintype' => $plugintype
            ]
        );

        if (!$record) {
            return null;
        }

        $searchmetadata = search_metadata::from_record($record);
        return $searchmetadata;
    }

    /**
     * Removing the search_metadata base on the component and the object id ($instanceid).
     *
     * @param string $component
     * @param int    $instanceid
     *
     * @return void
     */
    public static function remove_searchmetadata(string $component, int $instanceid): void {
        $metadata = static::find_searchmetadata($component, $instanceid);

        if (null == $metadata) {
            // No search metadata to be deleted!
            return;
        }

        $metadata->delete();
    }
}