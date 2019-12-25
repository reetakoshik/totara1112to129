<?php
/*
 * This file is part of Totara LMS
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

use context_system;
use totara_catalog\local\filter_handler;
use totara_catalog\output\catalog as catalog_template;
use totara_catalog\output\details;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");

/**
 * Totara Catalog external api.
 *
 * This class presents an external API for interacting with the Catalog API.
 * It can be consumed via any external service, web services, AJAX included.
 *
 * Please note that all functions must have a matching entry in totara/catalog/db/services.php
 * Internally all functions must perform full access and control checks.
 */
class external extends \external_api {

    /**
     * Returns an object that describes the parameters get_catalog requires.
     *
     * @return \external_function_parameters
     */
    public static function get_catalog_template_data_parameters() {
        $filterparams = [];

        foreach (filter_handler::instance()->get_all_filters() as $filter) {
            $optionalparams = $filter->selector->get_optional_params();
            foreach ($optionalparams as $optionalparam) {
                if ($optionalparam->multiplevalues) {
                    $filterparams[$optionalparam->key] = new \external_multiple_structure(
                        new \external_value(
                            $optionalparam->type,
                            $optionalparam->key,
                            VALUE_OPTIONAL
                        ),
                        $optionalparam->key,
                        VALUE_OPTIONAL
                    );
                } else {
                    $filterparams[$optionalparam->key] = new \external_value(
                        $optionalparam->type,
                        $optionalparam->key,
                        VALUE_OPTIONAL,
                        $optionalparam->default
                    );
                }
            }
        }

        return new \external_function_parameters(
            array(
                'itemstyle' => new \external_value(PARAM_ALPHA, 'The style of item to display in the grid'),
                'limitfrom' => new \external_value(PARAM_INT, 'Number of results to skip before calculating a page of results'),
                'maxcount' => new \external_value(PARAM_INT, 'The maximum number of records that might be visible to the user'),
                'orderbykey' => new \external_value(PARAM_ALPHA, 'Order the results in the specified way'),
                'resultsonly' => new \external_value(PARAM_BOOL, 'Only return the grid results, not a full page refresh'),
                'debug' => new \external_value(PARAM_BOOL, 'Include debug output'),
                'filterparams' => new \external_single_structure($filterparams, 'Filter params'),
                'request' => new \external_value(PARAM_RAW, 'Something to identify the request'),
            )
        );
    }

    /**
     * Gets everything needed to display the catalog template, including results.
     *
     * This function is intended to be used by totara's desktop browser catalog, and returns data
     * relating to the way that catalog has been configured.
     *
     * @param string $itemstyle 'narrow' or 'wide'
     * @param int $limitfrom only return records after the specified number
     * @param int $maxcount the maximum number of matching records, refined by subsequent page calls
     * @param string $orderbykey 'alpha', 'score', 'featured' or 'date'
     * @param bool $resultsonly true if only the
     * @param bool $showdebugging true if debugging information should be included
     * @param array $filterparams
     * @param string $request
     * @return array
     */
    public static function get_catalog_template_data(
        string $itemstyle,
        int $limitfrom,
        int $maxcount,
        string $orderbykey,
        bool $resultsonly,
        bool $showdebugging,
        array $filterparams,
        string $request
    ) {
        global $PAGE;
        // We need to set the page context otherwise icon rendering will cause debugging messages (and fall
        // back to system context anyway). This is a workaround for shortcomings of icon rendering that will
        // be dealt with in TL-19154.
        $PAGE->set_context(context_system::instance());

        $catalogtemplate = catalog_template::create(
            $itemstyle,
            $limitfrom,
            $maxcount,
            $orderbykey,
            $resultsonly,
            $showdebugging,
            $filterparams,
            $request
        );

        return $catalogtemplate->get_template_data();
    }

    /**
     * Returns an object that describes the structure of the return from get_catalog.
     *
     * @return \external_description
     */
    public static function get_catalog_template_data_returns() {
        // It's not possible to define recursive structures in this function. Catalog browse filter can contain
        // a tree of categories. It's all or nothing, so we have to do nothing. See TL-19175 for some more details.
        return null;
    }

    /**
     * Returns an object that describes the parameters get_details requires.
     *
     * @return \external_function_parameters
     */
    public static function get_details_template_data_parameters() {
        return new \external_function_parameters(
            array(
                'catalogid' => new \external_value(PARAM_INT, 'Catalog ID of the object to load'),
                'request' => new \external_value(PARAM_RAW, 'Something to identify the request'),
            )
        );
    }

    /**
     * Gets the details for the given catalog object.
     *
     * @param int $catalogid
     * @param string $request
     * @return array
     */
    public static function get_details_template_data(int $catalogid, string $request) {
        global $DB, $PAGE;

        // We need to set the page context otherwise icon rendering will cause debugging messages (and fall
        // back to system context anyway). This is a workaround for shortcomings of icon rendering that will
        // be dealt with in TL-19154.
        $PAGE->set_context(context_system::instance());

        // Find the object in the database.
        $object = $DB->get_record('catalog', ['id' => $catalogid], 'id, objecttype, objectid, contextid');

        // Get the corresponding provider.
        $provider = provider_handler::instance()->get_provider($object->objecttype);

        // Check that the current user is allowed to see the data.
        if (!$provider->can_see([$object])[$object->objectid]) {
            throw new \moodle_exception('Tried to access data without permission');
        }

        // Get the dataholders required to display details for the provider.
        $providerrequireddataholders = details::get_required_dataholders($provider);
        $requireddataholders = [$object->objecttype => $providerrequireddataholders];

        // Add the formatted data from the dataholders.
        $objects = provider_handler::instance()->get_data_for_objects([$object], $requireddataholders);
        $object = reset($objects);

        // Create the template, putting each bit of raw data into the correct placeholder.
        $detailstemplate = details::create($object, $request);

        return $detailstemplate->get_template_data();
    }

    /**
     * Returns an object that describes the structure of the return from get_catalog.
     *
     * @return \external_description
     */
    public static function get_details_template_data_returns() {
        // Return data from this function is complicated. It might be possible to define, but we've already found that it's
        // not possible for the main function, so we're skipping this one as well. See TL-19175 for some more details.
        return null;
    }
}