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

namespace totara_catalog\output;

use core\output\template;
use totara_catalog\catalog_retrieval;
use totara_catalog\local\config;
use totara_catalog\local\filter_handler;
use totara_catalog\provider_handler;
use totara_core\output\grid;
use totara_core\output\select_region_panel;
use totara_core\output\select_region_primary;
use totara_core\output\select_tree;

defined('MOODLE_INTERNAL') || die();

class catalog extends template {

    /**
     * @param string $itemstyle The style of item to display in the grid, either 'narrow' or 'wide'
     * @param int $limitfrom Number of results to skip before calculating a page of results
     * @param int $maxcount The maximum number of records that might be visible to the user
     * @param string $orderbykey Order the results in the specified way
     * @param bool $resultsonly Only return the grid results, don't include filter template data
     * @param bool $showdebugging Include debug output
     * @param array $filterparams
     * @param string|null $request Something to identify the request
     * @return catalog
     */
    public static function create(
        string $itemstyle,
        int $limitfrom,
        int $maxcount,
        string $orderbykey,
        bool $resultsonly,
        bool $showdebugging,
        array $filterparams,
        string $request = null
    ) {
        $config = config::instance();

        $data = new \stdClass();

        // Process filter params.
        $filterhandler = filter_handler::instance();
        foreach ($filterhandler->get_active_filters() as $filter) {
            $optionalparams = $filter->selector->get_optional_params();

            $paramdata = [];
            foreach ($optionalparams as $optionalparam) {
                if (isset($filterparams[$optionalparam->key])) {
                    $paramdata[$optionalparam->key] = $filterparams[$optionalparam->key];
                }
            }

            $filter->selector->set_current_data($paramdata);
            $standarddata = $filter->selector->get_data();
            $filter->datafilter->set_current_data($standarddata);
        }

        // Item type toggle.
        $viewoptions = $config->get_value('view_options');
        $data->item_style_toggle_enabled = $viewoptions == 'tile_and_list';
        if ($viewoptions == 'list_only') {
            $itemstyle = 'wide';
        } else if ($viewoptions == 'tile_only' || empty($itemstyle)) {
            $itemstyle = 'narrow';
        }

        // Grid with items.
        $catalog = new catalog_retrieval();
        $page = $catalog->get_page_of_objects($config->get_value('items_per_load'), $limitfrom, $maxcount, $orderbykey);
        $items = static::get_item_templates($page->objects, $itemstyle);
        $gridtemplate = grid::create($items, $itemstyle == 'wide');
        $data->grid_template_name = $gridtemplate->get_template_name();
        $data->grid_template_data = $gridtemplate->get_template_data();

        // Pagination.
        $paginationtemplate = pagination::create($page->limitfrom, $page->maxcount, $page->endofrecords);
        $data->pagination_template_name = $paginationtemplate->get_template_name();
        $data->pagination_template_data = $paginationtemplate->get_template_data();

        // Results count.
        $data->results_count = static::get_results_count($page->maxcount, $page->endofrecords);

        // Request.
        if (!empty($request)) {
            $data->request = $request;
        }

        // If $resultsonly was specified, then skip the rest of the data.
        if ($resultsonly) {
            return new static((array)$data);
        }

        // Manage buttons.
        $managebuttons = static::get_manage_buttons();
        $data->manage_btns_enabled = !empty($managebuttons);
        $data->manage_btns = $managebuttons;

        // Primary region.
        $primaryregiontemplate = static::get_primary_region_template();
        $data->primary_region_template_name = $primaryregiontemplate->get_template_name();
        $data->primary_region_template_data = $primaryregiontemplate->get_template_data();

        // Panel region.
        $panelregiontemplate = static::get_panel_region_template();
        $data->panel_region_enabled = !empty($panelregiontemplate->data['selectors']);
        $data->panel_region_template_name = $panelregiontemplate->get_template_name();
        $data->panel_region_template_data = $panelregiontemplate->get_template_data();

        // Order by.
        $data->order_by_enabled = $catalog->alphabetical_sorting_enabled();
        if ($data->order_by_enabled) {
            $orderbyoptions = static::get_order_by_options();
            if (empty($orderbyoptions[$orderbykey])) {
                reset($orderbyoptions);
                $orderbykey = key($orderbyoptions);
            }
            $orderbytemplate = select_tree::create(
                'orderbykey',
                get_string('sort_by', 'totara_catalog'),
                false,
                $orderbyoptions,
                $orderbykey,
                true
            );
            $data->order_by_template_name = $orderbytemplate->get_template_name();
            $data->order_by_template_data = $orderbytemplate->get_template_data();
        }

        // Debugging.
        if ($showdebugging && is_siteadmin()) {
            $data->debug = static::get_debugging_data($catalog, $orderbykey);
        }

        return new static((array)$data);
    }

    /**
     * Create a item template from each object's formatted data.
     *
     * @param \stdClass[] $objects
     * @param string $itemstyle
     * @return array
     */
    private static function get_item_templates(array $objects, string $itemstyle) {
        $providerhandler = provider_handler::instance();

        $requireddataholders = [];
        foreach ($objects as $object) {
            if (empty($requireddataholders[$object->objecttype])) {
                $provider = $providerhandler->get_provider($object->objecttype);
                $requireddataholders[$object->objecttype] = item::get_required_dataholders($provider);
            }
        }

        $objects = $providerhandler->get_data_for_objects($objects, $requireddataholders);

        $items = [];

        foreach ($objects as $object) {
            if ($itemstyle == 'narrow') {
                $items[] = item_narrow::create($object);
            } else {
                $items[] = item_wide::create($object);
            }
        }

        return $items;
    }

    /**
     * Get a text description of the number of records being displayed.
     *
     * @param int $maxcount
     * @param bool $endofrecords
     * @return string
     */
    private static function get_results_count(int $maxcount, bool $endofrecords) {
        if ($maxcount <= 0) {
            return get_string('count_none', 'totara_catalog');
        }

        if ($endofrecords) {
            return get_string('count_exact', 'totara_catalog', $maxcount);
        }

        $i = 0;
        $number = $maxcount;
        // while (($number / (10 ** $i)) >= 10) {
        //     $i++;
        //     $number = ceil($number / (10 ** $i)) * (10 ** $i);
        // }

        return get_string('count_up_to', 'totara_catalog', $number);
    }

    /**
     * Get the Primary region template, containing the browse and FTS filters.
     *
     * @return select_region_primary
     */
    private static function get_primary_region_template() {
        $selectortemplates = [];

        $filterhandler = filter_handler::instance();

        $browsefilter = $filterhandler->get_current_browse_filter();
        if ($browsefilter) {
            $selectortemplates[] = $browsefilter->selector->get_template();
        }

        $fulltextsearchselector = $filterhandler->get_full_text_search_filter()->selector;
        $selectortemplates[] = $fulltextsearchselector->get_template();

        return select_region_primary::create($selectortemplates);
    }

    /**
     * Get the Panel region template, which (usually) contains a bunch of filters.
     *
     * @return select_region_panel
     */
    private static function get_panel_region_template() {
        $selectortemplates = [];

        foreach (filter_handler::instance()->get_enabled_panel_filters() as $filter) {
            $filterselector = $filter->selector;
            $filterselector->set_title_hidden();
            $selectortemplates[] = $filterselector->get_template();
        }

        return select_region_panel::create(
            get_string('filters', 'totara_catalog'),
            $selectortemplates,
            true,
            true,
            true
        );
    }

    /**
     * Get the data for the manage buttons.
     *
     * @return \stdClass
     */
    private static function get_manage_buttons(): \stdClass {
        $systemcontext = \context_system::instance();

        $managebuttons = new \stdClass();

        $buttons = [];
        $createbuttons = [];

        if (has_capability('totara/catalog:configurecatalog', $systemcontext)) {
            $button = new \stdClass();
            $button->label = get_string('catalog:configurecatalog', 'totara_catalog');
            $button->url = (new \moodle_url('/totara/catalog/config.php'))->out();
            $buttons[] = $button;
        }

        foreach (provider_handler::instance()->get_active_providers() as $provider) {
            $buttons = array_merge($buttons, $provider->get_buttons());
            $createbuttons = array_merge($createbuttons, $provider->get_create_buttons());
        }

        if (count($createbuttons) == 1) {
            $createbutton = reset($createbuttons);
            $createbutton->label = get_string('createx', 'totara_catalog', $createbutton->label);
            $buttons[] = $createbutton;
        } else if (count($createbuttons) > 1) {
            $managebuttons->has_create_dropdown = true;
            $managebuttons->create_buttons = $createbuttons;
        }

        $managebuttons->has_buttons = !empty($buttons);
        $managebuttons->buttons = $buttons;

        return $managebuttons;
    }

    /**
     * Get a list of sorting options.
     *
     * @return \stdClass[]
     */
    private static function get_order_by_options() {
        $options = [];

        // If there is an active full text search then relevance becomes the first order by option.
        if (filter_handler::instance()->get_full_text_search_filter()->datafilter->is_active()) {
            $score = new \stdClass();
            $score->key = 'score';
            $score->name = get_string('sort_score', 'totara_catalog');
            $options['score'] = $score;
        }

        // Ordering by featured learning is only possible if some featured learning has been specified.
        if (config::instance()->get_value('featured_learning_enabled')) {
            $featured = new \stdClass();
            $featured->key = 'featured';
            $featured->name = get_string('sort_featured', 'totara_catalog');
            $options['featured'] = $featured;
        }

        $alpha = new \stdClass();
        $alpha->key = 'text';
        $alpha->name = get_string('sort_text', 'totara_catalog');
        $options['text'] = $alpha;

        $latest = new \stdClass();
        $latest->key = 'time';
        $latest->name = get_string('sort_time', 'totara_catalog');
        $options['time'] = $latest;

        reset($options);
        $firstkey = key($options);
        $options[$firstkey]->default = true;

        return $options;
    }

    /**
     * Get the data required to display debugging information.
     *
     * @param catalog_retrieval $catalog
     * @param string $orderbykey
     * @return \stdClass
     */
    private static function get_debugging_data(catalog_retrieval $catalog, string $orderbykey) {

        list($selectsql, $countsql, $rawparams) = $catalog->get_sql($orderbykey);

        $params = [];
        foreach ($rawparams as $key => $value) {
            $param = new \stdClass();
            $param->key = $key;
            $param->value = $value;
            $params[] = $param;
        }

        $debug = new \stdClass();
        $debug->sql = $selectsql;
        $debug->params = $params;

        return $debug;
    }
}