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

namespace totara_catalog\local;

use totara_catalog\filter;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Filter handler.
 */
class filter_handler {

    private static $instance;

    /** @var filter[] */
    private $allfilters = null;

    /** @var filter[] */
    private $activefilters = null;

    /** @var filter[][] */
    private $regionfilters = null;

    /** @var filter[] */
    private $enabledpanelfilters = null;

    /** @var filter */
    private $browsefilter = null;

    /** @var filter */
    private $fulltextsearchfilter = null;

    /** @var filter[] */
    private $categoryfilters = null;

    /** @var filter[] */
    private $learningtypefilters = null;

    /**
     * Return a singleton instance.
     *
     * @return filter_handler
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    /**
     * Reset the singleton's internal cache, so that the values will be loaded again next time they are accessed.
     *
     * This function should be used after data relating to filters has changed, including during testing.
     */
    public function reset_cache() {
        $this->allfilters = null;
        $this->activefilters = null;
        $this->regionfilters = null;
        $this->enabledpanelfilters = null;
        $this->browsefilter = null;
        $this->fulltextsearchfilter = null;
        $this->categoryfilters = null;
        $this->learningtypefilters = null;
    }

    /**
     * Gets all the filters from all the (active) providers, plus those built into the catalog.
     *
     * They will be combined where appropriate.
     *
     * @return filter[]
     */
    public function get_all_filters() {
        if (is_null($this->allfilters)) {
            $this->allfilters = [];

            foreach (provider_handler::instance()->get_active_providers() as $provider) {
                $providerfilters = $provider->get_filters();
                foreach ($providerfilters as $providerfilter) {
                    $this->register_filter($providerfilter);
                }
            }

            $categoryfilters = $this->get_category_filters();
            foreach ($categoryfilters as $categoryfilter) {
                $this->register_filter($categoryfilter);
            }
            $this->register_filter($this->get_full_text_search_filter());
            $learningtypefilters = $this->get_learning_type_filters();
            foreach ($learningtypefilters as $learningtypefilter) {
                $this->register_filter($learningtypefilter);
            }
        }

        return $this->allfilters;
    }

    /**
     * Get an array of all the active filters.
     *
     * @return filter[]
     */
    public function get_active_filters() {
        if (is_null($this->activefilters)) {
            $this->activefilters = $this->get_enabled_panel_filters();

            $browsefilter = $this->get_current_browse_filter();
            if (!empty($browsefilter)) {
                $this->activefilters[] = $browsefilter;
            }

            $this->activefilters[] = $this->get_full_text_search_filter();
        }

        return $this->activefilters;
    }

    /**
     * Register a filter in the allfilters array.
     *
     * Filters will be merged if they have the same key. Checks will be performed to ensure that
     * the region, datafilter, selector and templatename all match, and will throw an exception
     * if they don't. When merging datafilters and selectors, their "merge" functions will be used.
     *
     * @param filter $filter
     */
    private function register_filter(filter $filter) {
        if (isset($this->allfilters[$filter->key])) {
            $existingfilter = $this->allfilters[$filter->key];

            if (!$existingfilter->can_merge($filter)) {
                throw new \coding_exception('Tried to define two catalog filters with the same key but which cannot be merged');
            }

            $existingfilter->merge($filter);
        } else {
            $this->allfilters[$filter->key] = $filter;
        }
    }

    /**
     * Get all of the filters that can be put into the panel.
     *
     * @param int $region
     * @return filter[]
     */
    public function get_region_filters(int $region) {
        if (is_null($this->regionfilters)) {
            $this->regionfilters = [];

            foreach ($this->get_all_filters() as $filter) {
                if (!isset($this->regionfilters[$filter->region])) {
                    $this->regionfilters[$filter->region] = [];
                }
                $this->regionfilters[$filter->region][$filter->key] = $filter;
            }
        }

        return $this->regionfilters[$region];
    }

    /**
     * Get the filters which have been added to the panel position.
     *
     * @return filter[]
     */
    public function get_enabled_panel_filters() {
        if (is_null($this->enabledpanelfilters)) {
            $this->enabledpanelfilters = [];

            $enabledfilters = config::instance()->get_value('filters');
            $allpanelfilters = $this->get_region_filters(filter::REGION_PANEL);

            $systemcontent = \context_system::instance();

            foreach ($enabledfilters as $key => $title) {
                if (empty($allpanelfilters[$key])) {
                    continue;
                }

                $selectedfilter = $allpanelfilters[$key];
                $selectedfilter->selector->set_title(format_string($title, true, ['context' => $systemcontent]));

                $this->enabledpanelfilters[] = $selectedfilter;
            }
        }

        return $this->enabledpanelfilters;
    }

    /**
     * Get the filter which is currently set in the browse position.
     *
     * @return filter
     */
    public function get_current_browse_filter() {
        if (is_null($this->browsefilter)) {
            if (config::instance()->get_value('browse_by') == 'category') {
                $categoryfilters = $this->get_category_filters();
                foreach ($categoryfilters as $categoryfilter) {
                    if ($categoryfilter->region == filter::REGION_BROWSE) {
                        $this->browsefilter = $categoryfilter;
                    }
                }
            } else if (config::instance()->get_value('browse_by') == 'custom') {
                $browsefilters = $this->get_region_filters(filter::REGION_BROWSE);
                if (!empty($browsefilters[config::instance()->get_value('browse_by_custom')])) {
                    $this->browsefilter = $browsefilters[config::instance()->get_value('browse_by_custom')];
                }
            }
        }

        return $this->browsefilter;
    }

    /**
     * Gets the category filter (which allows search over all learning items that are contained in the category structure)
     * @return array|filter[]
     */
    public function get_category_filters() {
        if (is_null($this->categoryfilters)) {
            $this->categoryfilters = category_filters::create();
        }

        return $this->categoryfilters;
    }

    /**
     * Get the full text search filter
     *
     * @return filter
     */
    public function get_full_text_search_filter() {
        if (is_null($this->fulltextsearchfilter)) {
            $this->fulltextsearchfilter = full_text_search_filter::create();
        }

        return $this->fulltextsearchfilter;
    }

    /**
     * Gets the learning types fitler.
     */
    public function get_learning_type_filters() {
        if (is_null($this->learningtypefilters)) {
            $this->learningtypefilters = learning_type_filters::create();
        }

        return $this->learningtypefilters;
    }
}
