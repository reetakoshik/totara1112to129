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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone;

defined('MOODLE_INTERNAL') || die();

final class api {

    const ENDPOINT = 'https://api.go1.com/v2';
    const MAX_PAGE_SIZE = 50;
    const MAX_AVAILABLE_RESULTS = 10000;

    /** @var oauth_rest_client */
    private $client;

    /** @var config_storage  */
    private $config;

    /** @var \cache Cache for individual learning objects */
    private $learningobjectcache;
    /** @var \cache Cache for bulk results, eg. a search. */
    private $bulklearningobjectcache;
    /** @var \cache Cache for counts of objects. */
    private $countcache;

    /**
     * The api constructor.
     *
     * @param config_storage|null $config
     */
    public function __construct(config_storage $config = null) {
        $this->config = isset($config) ? $config : new config_db_storage();
        $oauth = new oauth($this->config);
        $this->client = new oauth_rest_client(self::ENDPOINT, $oauth);
        $this->learningobjectcache = \cache::make('contentmarketplace_goone', 'goonewslearningobject');
        $this->bulklearningobjectcache = \cache::make('contentmarketplace_goone', 'goonewslearningobjectbulk');
        $this->countcache = \cache::make('contentmarketplace_goone', 'goonewscount');
    }

    /**
     * Get an individual learning object.
     * @param int $id remote id of the learning object.
     * @return \stdClass Object returned from the Go1 web service.
     */
    public function get_learning_object(int $id) {
        $data = $this->learningobjectcache->get($id);
        if ($data === false) {
            $data = $this->client->get('learning-objects/' . $id);
            $this->clean_learning_object($data);
            $this->learningobjectcache->set($id, $data);
        }

        return $data;
    }

    /**
     * Smooths and cleans data on the given object (by reference)
     * @param \stdClass $data
     */
    private function clean_learning_object(&$data) {
        // Populate any missing non-guaranteed properties.
        $data->image = isset($data->image) ? $data->image : null;
        $data->portal_collection = isset($data->portal_collection) ? $data->portal_collection : false;
        $data->assessable = isset($data->assessable) ? $data->assessable : false;
        $data->pricing = isset($data->pricing) ? $data->pricing : new \stdClass();
        $data->pricing->currency = isset($data->pricing->currency) ? $data->pricing->currency : null;
        $data->pricing->price = isset($data->pricing->price) ? $data->pricing->price : null;
        $data->pricing->tax = isset($data->pricing->tax) ? $data->pricing->tax : null;
        $data->pricing->tax_included = isset($data->pricing->tax_included) ? $data->pricing->tax_included : null;
        $data->provider = isset($data->provider) ? $data->provider : new \stdClass();
        $data->provider->logo = isset($data->provider->logo) ? $data->provider->logo : null;
        $data->provider->name = isset($data->provider->name) ? $data->provider->name : null;
        $data->subscription = isset($data->subscription) ? $data->subscription : new \stdClass();
        $data->subscription->licenses = isset($data->subscription->licenses) ? $data->subscription->licenses : null;
        $data->reviews = isset($data->reviews) ? $data->reviews : new \stdClass();
        $data->reviews->count = isset($data->reviews->count) ? $data->reviews->count : null;
        $data->reviews->rating = isset($data->reviews->rating) ? $data->reviews->rating : null;
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function get_scorm(int $id) {
        $url = 'learning-objects/' . $id . '/scorm';
        $headers = [
            'Accept: application/zip',
        ];
        return $this->client->get($url, [], $headers);
    }

    /**
     * Generate a cache key from query parameters.
     * @param  array  $params The parameters to the API query.
     * @return string         A string unique to that query.
     */
    private function gen_cache_key(array $params) {
        // Include the oauth_client_id to handle the case of a user adjusting admin settings.
        $cachekey = $this->config->get("oauth_client_id");
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $cachekey .= "_$key:$value";
        }
        return $cachekey;
    }

    /**
     * Generate keys for use in the count cache
     * @param  string $scope  The specific count being cached, total, subscribed, collection etc.
     * @param  array  $params The parameters to the API query.
     * @return string         A string unique to that specific count and query.
     */
    private function gen_count_cache_key($scope, array $params) {
        unset($params["limit"]);
        unset($params["sort"]);
        unset($params["offset"]);
        return $scope . '_' . $this->gen_cache_key($params);
    }

    /**
     * Perform a search for matching learning objects via API.
     * @param  array $params Search parameters
     * @return object         Data returned from API
     */
    public function get_learning_objects(array $params = []) {
        $this->check_processing_cache_timer();

        $this->apply_common_params_for_get_learning_objects($params);
        $cachekey = $this->gen_cache_key($params);
        $data = $this->bulklearningobjectcache->get($cachekey);
        if ($data === false) {
            $data = $this->client->get('learning-objects', $params);
            if (!isset($data->total) or !is_number($data->total)) {
                throw new \Exception('Response from GO1 API for "learning-objects" is missing expected value for "total"');
            }

            foreach ($data->hits as $hit) {
                $this->clean_learning_object($hit);
            }
            $this->bulklearningobjectcache->set($cachekey, $data);

            // Cache a count and avoid another query.
            if (isset($params['collection'])) {
                $countcachekey = $this->gen_count_cache_key('collection', $params);
            } else if (isset($params['subscribed'])) {
                unset($params["collection"]);
                $countcachekey = $this->gen_count_cache_key('subscribed', $params);
            } else {
                unset($params["subscribed"]);
                unset($params["collection"]);
                $countcachekey = $this->gen_count_cache_key('total', $params);
            }
            $this->countcache->set($countcachekey, $data->total);
        }
        // Cache any learning objects for later use, e.g. details popdown or next step.
        foreach ($data->hits as $hit) {
            $hit->id = (string)$hit->id;
            $this->learningobjectcache->set($hit->id, $hit);
        }

        return $data;
    }

    public function get_account() {
        $account = $this->client->get('account');
        // Populate any missing non-guaranteed properties with null.
        $account->plan->active_user_count = isset($account->plan->active_user_count) ? $account->plan->active_user_count : null;
        $account->plan->licensed_user_count = isset($account->plan->licensed_user_count) ? $account->plan->licensed_user_count : null;
        $account->plan->pricing = isset($account->plan->pricing) ? $account->plan->pricing : new \stdClass();
        $account->plan->pricing->currency = isset($account->plan->pricing->currency) ? $account->plan->pricing->currency : null;
        $account->plan->pricing->price = isset($account->plan->pricing->price) ? $account->plan->pricing->price : null;
        $account->plan->pricing->tax = isset($account->plan->pricing->tax) ? $account->plan->pricing->tax : null;
        $account->plan->pricing->tax_included = isset($account->plan->pricing->tax_included) ? $account->plan->pricing->tax_included : null;
        $account->plan->region = isset($account->plan->region) ? $account->plan->region : null;
        $account->plan->renewal_date = isset($account->plan->renewal_date) ? $account->plan->renewal_date : null;
        $account->plan->type = isset($account->plan->type) ? $account->plan->type : null;
        return $account;
    }

    public function get_configuration() {
        $configuration = $this->client->get('configuration');
        // Populate any missing non-guaranteed properties with null.
        $configuration->pay_per_seat = isset($configuration->pay_per_seat) ? $configuration->pay_per_seat : null;
        return $configuration;
    }

    public function save_configuration($data) {
        return $this->client->put('configuration', $data);
    }

    /**
     * @param array $params The parameters to the API query.
     * @return int The total number of all packages for this account
     */
    public function get_learning_objects_total_count(array $params = []) {
        unset($params["subscribed"]);
        unset($params["collection"]);
        $params["limit"] = 0;
        $this->apply_common_params_for_get_learning_objects($params);
        $cachekey = $this->gen_count_cache_key('total', $params);

        $data = $this->countcache->get($cachekey);
        if ($data === false) {
            $data = $this->get_learning_objects($params)->total;
            $this->countcache->set($cachekey, $data);
        }
        return $data;
    }

    /**
     * @param array $params The parameters to the API query.
     * @return int The total number of subscribed packages for this account
     */
    public function get_learning_objects_subscribed_count(array $params = []) {
        $params["subscribed"] = "true";
        unset($params["collection"]);
        $params["limit"] = 0;
        $this->apply_common_params_for_get_learning_objects($params);
        $cachekey = $this->gen_count_cache_key('subscribed', $params);
        $data = $this->countcache->get($cachekey);
        if ($data === false) {
            $data = $this->get_learning_objects($params)->total;
            $this->countcache->set($cachekey, $data);
        }
        return $data;
    }

    /**
     * @param array $params The parameters to the API query.
     * @param string $collectionid
     * @return int The total number of packages for the given collection
     */
    public function get_learning_objects_collection_count(array $params = [], $collectionid = 'default') {
        $this->check_processing_cache_timer();
        unset($params["subscribed"]);
        $params['collection'] = $collectionid;
        $params["limit"] = 0;
        $this->apply_common_params_for_get_learning_objects($params);
        $cachekey = $this->gen_count_cache_key('collection', $params);
        $data = $this->countcache->get($cachekey);
        if ($data === false) {
            $data = $this->get_learning_objects($params)->total;
            $this->countcache->set($cachekey, $data);
        }
        return $data;
    }

    /**
     * @param array $params The parameters to the API query.
     * @return array Listing of all the learning objects id's for the given filter
     */
    public function list_ids_for_all_learning_objects(array $params = []) {
        $ids = [];
        for ($page = 0; $page < self::MAX_AVAILABLE_RESULTS/self::MAX_PAGE_SIZE; $page += 1) {
            $params['offset'] = $page * self::MAX_PAGE_SIZE;
            $params['limit'] = self::MAX_PAGE_SIZE;
            $response = $this->get_learning_objects($params);
            foreach ($response->hits as $hit) {
                $ids[] = $hit->id;
            }
            if (count($ids) >= $response->total) {
                break;
            }
        }
        return $ids;
    }

    /**
     * Apply filter options that are common across all uses of get_learning_objects.
     * @param array $params
     */
    private function apply_common_params_for_get_learning_objects(array &$params) {
        $params['event'] = "false"; // Exclude events from API calls as we can't really handle them in the UI.
    }

    /**
     * Set a buffer time that will cause caches to be expired after the remote Go1 API server
     * has had some time to process changes to collections (adding or removing items).
     * This is to prevent us caching out of date collection information.
     * @param int $numitems The number of items to be processed.
     */
    private function set_processing_cache_timer($numitems) {
        // Number of seconds we think the Go1 API will take to process the changes.
        // Based on a rough test by Chris Hood and then fudged upwards to be conservative.
        $processingallowance = ceil($numitems / 15.0) + 1;
        $existingexpiry = get_config('contentmarketplace_goone', 'collectioncacheexpiry');
        if ($existingexpiry === false || $existingexpiry == 0 || $existingexpiry < time()) {
            // There's no existing buffer. Set one.
            $cacheexpiry = time() + $processingallowance;
        } else {
            // Extend the current one.
            $cacheexpiry = $existingexpiry + $processingallowance;
        }
        set_config('collectioncacheexpiry', $cacheexpiry, 'contentmarketplace_goone');
    }

    /**
     * Check if the buffer period for the remote API has passed and if so clear the caches.
     * @return void
     */
    private function check_processing_cache_timer() {
        $expiry = get_config('contentmarketplace_goone', 'collectioncacheexpiry');
        if ($expiry !== false && $expiry != 0 && (int) $expiry < time()) {
            $this->learningobjectcache->purge();
            $this->bulklearningobjectcache->purge();
            $this->countcache->purge();
            // Reset the timer.
            set_config('collectioncacheexpiry', 0, 'contentmarketplace_goone');
        }
    }

    /**
     * @param string $operation
     * @param array $items
     * @param string $collectionid
     * @return void
     */
    private function update_collection($operation, array $items, $collectionid) {
        if (empty($items)) {
            return;
        }

        $this->set_processing_cache_timer(count($items));

        $this->learningobjectcache->delete_many($items); // Reset any cached learning objects affected.
        $this->bulklearningobjectcache->purge();
        $this->countcache->purge();
        $items = array_map(function($value) {return (int)$value;}, $items);
        for ($n = 0; $n < count($items); $n += self::MAX_PAGE_SIZE) {
            $data = [
                'lo' => array_slice($items, $n, self::MAX_PAGE_SIZE),
            ];
            $this->client->post('collections/' . $collectionid . '/items/' . $operation, $data);
        }
    }

    /**
     * Adds items to collection.
     *
     * @param array of item IDs.
     * @param string $collectionid Collection ID ("default" by default).
     * @return void
     */
    public function add_to_collection(array $items, $collectionid = 'default') {
        $this->update_collection('add', $items, $collectionid);
    }

    /**
     * Removes items from collection.
     *
     * @param array of item IDs.
     * @param string $collectionid Collection ID ("default" by default).
     * @return void
     */
    public function remove_from_collection(array $items, $collectionid = 'default') {
        $this->update_collection('remove', $items, $collectionid);
    }

    /**
     * Clears all API caches.
     */
    public function purge_all_caches() {
        $this->learningobjectcache->purge();
        $this->bulklearningobjectcache->purge();
        $this->countcache->purge();
    }

}
