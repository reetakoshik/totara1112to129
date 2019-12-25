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
 * @package totara_catalog
 */

namespace totara_catalog;

use totara_catalog\datasearch\datasearch;
use totara_catalog\hook\exclude_item;
use totara_catalog\local\config;
use totara_catalog\local\feature_handler;
use totara_catalog\local\filter_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * The catalog.
 */
class catalog_retrieval {

    /**
     * Determines if alphabetical sorting should be allowed.
     *
     * @return bool
     */
    public function alphabetical_sorting_enabled(): bool {
        global $CFG;

        return
            count(get_string_manager()->get_list_of_translations()) <= 1 ||
            !empty($CFG->catalog_enable_alpha_sorting_with_multiple_languages);
    }

    /**
     * @param string $orderbykey
     * @return array [$orderbycolumns, $orderbysort]
     */
    private function get_order_by_sql(string $orderbykey): array {
        if (!$this->alphabetical_sorting_enabled()) {
            // Ignore specified sorting and instead choose sorting based on current catalog condition.

            if (filter_handler::instance()->get_full_text_search_filter()->datafilter->is_active()) {
                return [
                    'catalogfts.score, catalog.sorttime',
                    'catalogfts.score DESC, catalog.sorttime DESC'
                ];
            }

            if (!empty(feature_handler::instance()->get_current_feature())) {
                // Featured not required in columns list because it must already be there if it is enabled.
                return [
                    'catalog.sorttime',
                    'COALESCE(featured, 0) DESC, catalog.sorttime DESC'
                ];
            }

            return [
                'catalog.sorttime',
                'catalog.sorttime DESC'
            ];
        }

        // Otherwise we figure out what the best sorting is given the input and current state.

        // Default featured.
        $orderbykey = trim($orderbykey);
        if (empty($orderbykey)) {
            $orderbykey = 'featured';
        }

        // Revert to default if set to score but there is no full text search active.
        if ($orderbykey == 'score' && !filter_handler::instance()->get_full_text_search_filter()->datafilter->is_active()) {
            $orderbykey = 'featured';
        }

        // Revert to title if no featured learning is configured.
        if ($orderbykey == 'featured' && empty(feature_handler::instance()->get_current_feature())) {
            $orderbykey = '';
        }

        switch ($orderbykey) {
            case 'score':
                return [
                    'catalogfts.score, catalog.sorttext',
                    'catalogfts.score DESC, catalog.sorttext ASC'
                ];
            case 'time':
                return [
                    'catalog.sorttime, catalog.sorttext',
                    'catalog.sorttime DESC, catalog.sorttext ASC'
                ];
            case 'featured':
                // Featured not required in columns list because it must already be there if it is enabled.
                return [
                    'catalog.sorttext',
                    'COALESCE(featured, 0) DESC, catalog.sorttext ASC'
                ];
            case 'text':
            default:
                return [
                    'catalog.sorttext',
                    'catalog.sorttext ASC'
                ];
        }
    }

    /**
     * Builds the sql required to get catalog results.
     *
     * @param string $orderbykey
     * @return array [$selectsql, $countsql, $params]
     */
    public function get_sql(string $orderbykey): array {
        $outputcolumns = 'catalog.id, catalog.objecttype, catalog.objectid, catalog.contextid';

        $config = config::instance();

        if ($config->get_value('featured_learning_enabled')) {
            $featuredfilter = feature_handler::instance()->get_current_feature();
        }

        if (!empty($featuredfilter)) {
            $outputcolumns .= ", COALESCE(featured, 0) AS featured";
        }

        list($orderbycolumns, $orderbysort) = $this->get_order_by_sql($orderbykey);
        $outputcolumns .= ', ' . $orderbycolumns;

        $search = new datasearch(
            '{catalog} catalog',
            $outputcolumns,
            $orderbysort
        );

        // Use get_all_filters, rather than get_active_filters, because we trust that our catalog has only set
        // data in filters that are on the page, but other users (e.g. those using the external api) might have
        // a completely different set of filters configured.
        foreach (filter_handler::instance()->get_all_filters() as $filter) {
            if ($filter->datafilter->is_active()) {
                $search->add_filter($filter->datafilter);
            }
        }

        if (!empty($featuredfilter)) {
            $featuredfilter->datafilter->set_current_data([$config->get_value('featured_learning_value')]);
            $search->add_filter($featuredfilter->datafilter);
        }

        return $search->get_sql();
    }

    /**
     * Get a page of objects. Assumes that all datasearch filters have been set up with whatever the current
     * parameters are, and that featured learning has been configured.
     *
     * Each 'object' contains:
     * - int id (from catalog table)
     * - int objectid
     * - string objecttype
     * - int contextid
     * - bool featured (optional, depending on configuration)
     *
     * @param int $pagesize
     * @param int $limitfrom
     * @param int $maxcount
     * @param string $orderbykey
     * @return \stdClass containing array 'objects', int 'limitfrom', int 'maxcount' and bool 'endofrecords'
     */
    public function get_page_of_objects(
        int $pagesize,
        int $limitfrom,
        int $maxcount = -1,
        string $orderbykey = 'featured'
    ): \stdClass {
        global $DB;

        list($selectsql, $countsql, $params) = $this->get_sql($orderbykey);

        $objects = [];
        $endofrecords = false;
        $querypagesize = $pagesize; // Doesn't need to be the same as page size, but shouldn't be smaller.
        $skipped = 0;

        $providerhandler = provider_handler::instance();

        while (!$endofrecords && count($objects) < $pagesize) {
            // Get some records.
            $records = $DB->get_records_sql($selectsql, $params, $limitfrom, $querypagesize);

            // Stop if there are no more records to be retrieved from the db.
            if (empty($records)) {
                $endofrecords = true;
                break;
            }

            foreach ($records as $record) {
                $limitfrom++; // Whether or not we return this record, we don't want to process it again.

                // Skip records for providers that aren't enabled (or maybe aren't even real!).
                if (!$providerhandler->is_active($record->objecttype)) {
                    $skipped++;
                    continue;
                }

                $provider = $providerhandler->get_provider($record->objecttype);

                // Check if the object can be included in the catalog for the given user.
                $cansees = $provider->can_see([$record]);
                if (!$cansees[$record->objectid]) {
                    $skipped++;
                    continue;
                }

                // A hook here to exclude/include the course/program/certificate based on the
                // third parties setting.
                $hook = new exclude_item($record);
                $hook->execute();

                if ($hook->is_excluded()) {
                    $skipped++;
                    continue;
                }

                // If we want to modify any record of a catalog, probably here is a good place to
                // have another seperate hook for it.

                // Unfortunately, there should not have a hook to add new record(s) into the list
                // of the result, because adding new record(s) will break the core functionality of
                // the catalog's pagination. Furthermore, we should not encourage the third party to
                // do so, because any record(s) added on the fly will not have any sorting supports

                // Not excluded, so add it to the results;
                $objects[] = $record;

                // Stop if we've got enough objects to fill the page.
                if (count($objects) == $pagesize) {
                    break 2;
                }
            }

            $querypagesize *= 2; // Exponential growth, so that we will do about O(log n) steps at most.
        }

        // Figure out if there are any more records to load, if we didn't reach the end while calculating the results.
        if ($endofrecords) {
            $totaluncheckedrecords = $limitfrom;
        } else {
            $totaluncheckedrecords = $DB->count_records_sql($countsql, $params);
            $endofrecords = $limitfrom == $totaluncheckedrecords;
        }

        // Figure out the maximum possible number of records that MIGHT be visible, according to the calculations we've done so far.
        if ($maxcount < 0) {
            $maxcount = $totaluncheckedrecords;
        }
        $maxcount -= $skipped;

        $page = new \stdClass();
        $page->objects = $objects;
        $page->limitfrom = $limitfrom;
        $page->maxcount = $maxcount;
        $page->endofrecords = $endofrecords;

        return $page;
    }

    /**
     * Returns a string which can safely be used as the alias of a table in an sql query.
     *
     * @param string $unsafestring
     * @return string
     */
    public static function get_safe_table_alias(string $unsafestring): string {
        $cleanstring = strtolower(substr(clean_param($unsafestring, PARAM_ALPHA), 0, 15));

        if (empty($cleanstring)) {
            throw new \coding_exception('Tried to create a safe table alias but the initial string was empty after cleaning');
        }

        return $cleanstring . '_' . substr(md5($unsafestring), 0, 5);
    }
}
