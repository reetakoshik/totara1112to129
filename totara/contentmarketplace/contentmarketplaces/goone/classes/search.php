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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone;

use totara_contentmarketplace\local;
use totara_contentmarketplace\local\contentmarketplace\search_results;

defined('MOODLE_INTERNAL') || die();

final class search extends \totara_contentmarketplace\local\contentmarketplace\search {

    // Max API limit for search results is 50. However 48 happens to be a better fit for a grid of search results.
    // 48 divides by 4, 3, and 2 so then a full page of results will always finishes with a complete row.
    const SEARCH_PAGE_SIZE = 48;

    /**
     * List sorting options available for the search results.
     *
     * @return string[]
     */
    public function sort_options(): array {
        $options = array(
            'created:desc',
            'relevance',
            'popularity',
            'price',
            'price:desc',
            'title',
        );
        return $options;
    }

    /**
     * @param string $keyword
     * @param string $sort
     * @param array $filter
     * @param int $page
     * @param bool $isfirstquerywithdefaultsort
     * @param string $mode
     * @param \context $context
     * @return search_results
     */
    public function query(string $keyword, string $sort, array $filter, int $page, bool $isfirstquerywithdefaultsort, string $mode, \context $context): search_results {
        $api = new api();
        $hits = array();
        $filterid = 0;

        if ($isfirstquerywithdefaultsort) {
            $sort = 'relevance';
        }

        $params = array(
            "keyword" => $keyword,
            "sort" => $sort,
            "offset" => $page * self::SEARCH_PAGE_SIZE,
            "limit" => self::SEARCH_PAGE_SIZE,
            "facets" => "tag,language,instance",
        );
        foreach (array("tags", "language", "provider") as $name) {
            if (key_exists($name, $filter)) {
                $params[$name] = $filter[$name];
            }
        }
        $availability_selection = $this->availability_selection($filter, $context, $mode);
        $params += $this->availability_query($availability_selection);

        $response = $api->get_learning_objects($params);
        foreach ($response->hits as $hit) {

            $delivery = array();
            if ($hit->delivery->duration > 0) {
                $title = self::duration($hit);
                $delivery[] = array("title" => $title);
            }
            if ($hit->delivery->mode) {
                $title = $hit->delivery->mode;
                $delivery[] = array("title" => $title);
            }
            if (!empty($delivery)) {
                $delivery[count($delivery) - 1]["last"] = true;
            }

            $hits[] = array(
                "id" => $hit->id,
                "title" => $hit->title,
                "selectlabel" => get_string('selectcontent', 'contentmarketplace_goone', $hit->title),
                "image" => $hit->image,
                "provider" => array(
                    "name" => $hit->provider->name,
                ),
                "delivery" => $delivery,
                "delivery_has_items" => !empty($delivery),
                "price" => self::price($hit),
                "is_in_collection" => $hit->portal_collection,
            );
        }

        $results = new search_results();
        $results->hits = $hits;

        $results->filters = [
            [
                'name' => 'availability',
                'values' => $this->availability_filter($params, $context)
            ]
        ];

        $results->total = $response->total;

        $results->more = $response->total > ($page + 1) * self::SEARCH_PAGE_SIZE;
        $results->sort = $sort;

        if (!empty($params['collection'])) {
            $results->selectionmode = 'remove';
        } else {
            $results->selectionmode = 'add';
        }

        return $results;
    }

    /**
     * @param array $params
     * @param \context $context
     * @return array
     */
    public function availability_filter(array $params, \context $context) {
        $api = new api();

        $values = [];
        $availablityoptions = contentmarketplace::content_availability_options($context);
        if (in_array('all', $availablityoptions)) {
            $values["all"] = local::format_integer($api->get_learning_objects_total_count($params));
        }

        if (in_array('subscribed', $availablityoptions)) {
            $values["subscribed"] = local::format_integer($api->get_learning_objects_subscribed_count($params));
        }

        if (in_array('collection', $availablityoptions)) {
            $values["collection"] = local::format_integer($api->get_learning_objects_collection_count($params));
        }

        return $values;
    }

    /**
     * @param array $filter
     * @param \context $context
     * @param string|null $mode
     * @return null|string
     */
    public function availability_selection(array $filter, \context $context, string $mode = null) {
        if (key_exists("availability", $filter)) {
            $selection = $filter["availability"];
            if (!in_array($selection, array("all", "subscribed", "collection"))) {
                $selection = null;
            }
        } else if ($mode == \totara_contentmarketplace\explorer::MODE_EXPLORE_COLLECTION) {
            $selection = 'collection';
        } else {
            $selection = null;
        }

        if (has_capability('totara/contentmarketplace:config', $context)) {
            if (!isset($selection)) {
                $selection = "all";
            }
        } else if (has_capability('totara/contentmarketplace:add', $context)) {
            if (!isset($selection)) {
                $selection = "all";
            }
            $contentsettingscreators = get_config('contentmarketplace_goone', 'content_settings_creators');
            switch ($contentsettingscreators) {
                case "subscribed":
                    if ($selection === "all") {
                        $selection = "subscribed";
                    }
                    break;
                case "collection":
                    $selection = "collection";
                    break;
            }
        } else {
            $selection = null;
        }

        return $selection;
    }

    /**
     * @param string $selection
     * @return array
     */
    public function availability_query($selection) {
        switch ($selection) {
            case 'subscribed':
                $query = ["subscribed" => "true"];
                break;
            case 'collection':
                $query = ["collection" => "default"];
                break;
            default:
                $query = [];
        }
        return $query;
    }

    /**
     * @param string $query
     * @param array $filter
     * @param string $mode
     * @param \context $context
     * @return array
     */
    public function select_all($query, array $filter, string $mode, \context $context) {
        $params = array(
            "keyword" => $query,
        );
        foreach (array("tags", "language", "provider") as $name) {
            if (key_exists($name, $filter)) {
                $params[$name] = $filter[$name];
            }
        }
        $availability_selection = $this->availability_selection($filter, $context, $mode);
        $params += $this->availability_query($availability_selection);

        $api = new api();
        return $api->list_ids_for_all_learning_objects($params);
    }

    /**
     * @param \stdClass $course
     * @return string
     */
    public static function price($course) {
        if (!is_null($course->subscription->licenses) and ($course->subscription->licenses === -1 or $course->subscription->licenses > 0)) {
            return get_string('price:included', 'contentmarketplace_goone');
        }
        if ($course->pricing->price === 0) {
            return get_string('price:free', 'contentmarketplace_goone');
        }
        if (empty($course->pricing->price) || empty($course->pricing->currency)) {
            return '';
        }
        $price = local::format_money($course->pricing->price, $course->pricing->currency);
        if (!$course->pricing->tax_included and $course->pricing->tax > 0) {
            $a = new \stdClass();
            $a->baseprice = $price;
            $a->tax = $course->pricing->tax;
            return get_string('pricewithtax', 'contentmarketplace_goone', $a);
        } else {
            return $price;
        }
    }

    /**
     * @param \stdClass $course
     * @return string
     */
    public static function duration($course) {
        if (empty($course->delivery) || empty($course->delivery->duration)) {
            return '';
        }
        return get_string('duration', 'contentmarketplace_goone', $course->delivery->duration);
    }

    /**
     * @param int $id
     * @return \stdClass|null
     */
    public function get_details(int $id) {
        try {
            $api = new api();
            $learningobject = $api->get_learning_object($id);
        } catch (\Exception $ex) {
            debugging($ex->getMessage(), DEBUG_DEVELOPER);
            return null;
        }

        return $learningobject;
    }

    /**
     * @param string $mode
     * @param \context $context
     * @return array
     */
    public function availability_filter_seed($context, $mode) {
        $selection = $this->availability_selection([], $context, $mode);
        $filterid = 0;
        $all = [
            "htmlid" => 'tcm-filter-availability-' . $filterid++,
            "value" => "all",
            "label" => get_string("availability-filter:all", "contentmarketplace_goone"),
            "count" => "",
            "checked" => $selection === "all",
        ];

        $subscribed = [
            "htmlid" => 'tcm-filter-availability-' . $filterid++,
            "value" => "subscribed",
            "label" => get_string("availability-filter:subscription", "contentmarketplace_goone"),
            "count" => "",
            "checked" => $selection === "subscribed",
        ];

        $collection = [
            "htmlid" => 'tcm-filter-availability-' . $filterid++,
            "value" => "collection",
            "label" => get_string("availability-filter:collection", "contentmarketplace_goone"),
            "count" => "",
            "checked" => $selection === "collection",
        ];

        $content_settings = get_config('contentmarketplace_goone', 'content_settings_creators');
        if (has_capability('totara/contentmarketplace:config', $context)) {
            $options = [$all, $subscribed, $collection];
        } else if (has_capability('totara/contentmarketplace:add', $context)) {
            switch ($content_settings) {
                case "all":
                    $options = [$all, $subscribed, $collection];
                    break;
                case "subscribed":
                    $options = [$subscribed, $collection];
                    break;
                default:
                    $options = [];
            }
        } else {
            $options = [];
        }

        $seed = [
            "name" => "availability",
            "options" => $options,
        ];
        return $seed;
    }

    /**
     * @param \stdClass $response
     * @return array
     */
    public function tags_filter_seed($response) {
        $tags = [];
        $filterid = 0;
        foreach ($response->facets->tag->buckets as $bucket) {
            $tags[$bucket->key] = [
                "htmlid" => 'tcm-filter-tag-' . $filterid++,
                "value" => $bucket->key,
                "label" => $bucket->key,
                "checked" => false,
            ];
        }
        $seed = array(
            "name" => "tags",
            "options" => $tags,
        );
        return $seed;
    }

    /**
     * @param \stdClass $response
     * @return array
     */
    public function provider_filter_seed($response) {
        $providers = [];
        $filterid = 0;
        foreach ($response->facets->instance->buckets as $bucket) {
            $providers[$bucket->key] = [
                "htmlid" => 'tcm-filter-provider-' . $filterid++,
                "value" => $bucket->key,
                "label" => $bucket->name,
                "checked" => false,
            ];
        }
        $seed = [
            "name" => "provider",
            "options" => $providers,
        ];
        return $seed;
    }

    /**
     * @param \stdClass $response
     * @return array
     */
    public function language_filter_seed($response) {
        $languages = [];
        $filterid = 0;
        $stringmanager = new string_manager();
        foreach ($response->facets->language->buckets as $bucket) {
            $label = $stringmanager->get_language($bucket->key);
            $languages[$bucket->key] = [
                "htmlid" => 'tcm-filter-language-' . $filterid++,
                "value" => $bucket->key,
                "label" => $label,
                "checked" => false,
            ];
        }
        $seed = [
            "name" => "language",
            "options" => $languages,
        ];
        return $seed;
    }

    /**
     * @param string $mode
     * @param \context $context
     * @return array
     */
    public function get_filter_seeds($context, $mode) {
        $api = new api();
        $params = array(
            "offset" => 0,
            "limit" => 0,
            "facets" => "tag,language,instance",
        );
        $availabilityselection = $this->availability_selection([], $context);
        $params += $this->availability_query($availabilityselection);

        $response = $api->get_learning_objects($params);

        $data = [];
        $data[] = $this->availability_filter_seed($context, $mode);
        $data[] = $this->tags_filter_seed($response);
        $data[] = $this->provider_filter_seed($response);
        $data[] = $this->language_filter_seed($response);
        return $data;
    }

}
