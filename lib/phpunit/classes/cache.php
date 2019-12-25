<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package @core
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/cache/lib.php");

/**
 * Fast PHPUnit cache.
 */
class phpunit_cache implements cache_loader {
    /** @var cache_definition  */
    protected $definition;

    /**
     * The data source to use if we need to load data.
     * @var cache_data_source
     */
    protected $datasource = null;

    /**
     * @internal
     * @var array
     */
    public $phpunitcache = array();

    /**
     * @internal
     * @var bool
     */
    public $phpunitmodified = true;

    /**
     * Constructs a new cache instance.
     *
     *
     * @param cache_definition $definition The definition for the cache instance.
     * @param cache_data_source $datasource the data source if there is one and there
     */
    public function __construct(cache_definition $definition, cache_data_source $datasource = null) {
        $this->definition = $definition;
        $this->datasource = $datasource;
    }

    /**
     * Alters the identifiers that have been provided to the definition.
     *
     * This is an advanced method and should not be used unless really needed.
     * It allows the developer to slightly alter the definition without having to re-establish the cache.
     * It will cause more processing as the definition will need to clear and reprepare some of its properties.
     *
     * @param array $identifiers
     */
    public function set_identifiers(array $identifiers) {
        if ($this->definition->set_identifiers($identifiers)) {
            $this->phpunitcache = array();
            $this->phpunitmodified = true;
        }
    }

    /**
     * Retrieves the value for the given key from the cache.
     *
     * @param string|int $key The key for the data being requested.
     *      It can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param int $strictness One of IGNORE_MISSING | MUST_EXIST
     * @return mixed|false The data from the cache or false if the key did not exist within the cache.
     * @throws coding_exception
     */
    public function get($key, $strictness = IGNORE_MISSING) {
        if (!isset($this->phpunitcache[$key])) {
            $result = false;
        } else {
            $data = $this->phpunitcache[$key]['data'];
            if ($data instanceof cache_cached_object) {
                $result = $data->restore_object();
            } else if ($this->phpunitcache[$key]['serialized']) {
                $result = unserialize($data);
            } else {
                $result = $data;
            }
        }

        if ($result === false and $this->datasource) {
            $result = $this->datasource->load_for_cache($key);
            if ($result !== false) {
                $this->set($key, $result);
            }
        }

        if ($strictness === MUST_EXIST && $result === false) {
            throw new coding_exception('Requested key did not exist in any cache stores and could not be loaded.');
        }

        return $result;
    }

    /**
     * Retrieves an array of values for an array of keys.
     *
     * Using this function comes with potential performance implications.
     * Not all cache stores will support get_many/set_many operations and in order to replicate this functionality will call
     * the equivalent singular method for each item provided.
     * This should not deter you from using this function as there is a performance benefit in situations where the cache store
     * does support it, but you should be aware of this fact.
     *
     * @param array $keys The keys of the data being requested.
     *      Each key can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param int $strictness One of IGNORE_MISSING or MUST_EXIST.
     * @return array An array of key value pairs for the items that could be retrieved from the cache.
     *      If MUST_EXIST was used and not all keys existed within the cache then an exception will be thrown.
     *      Otherwise any key that did not exist will have a data value of false within the results.
     * @throws coding_exception
     */
    public function get_many(array $keys, $strictness = IGNORE_MISSING) {
        $result = array();
        $missing = false;

        // First up check the persist cache for each key.
        foreach ($keys as $key) {
            $value = $this->get($key);
            $result[$key] = $value;
            if ($value === false) {
                $missing = true;
            }
        }

        if ($strictness === MUST_EXIST and $missing) {
            throw new coding_exception('Not all the requested keys existed within the cache stores.');
        }

        return $result;
    }

    /**
     * Sends a key => value pair to the cache.
     *
     * <code>
     * // This code will add four entries to the cache, one for each url.
     * $cache->set('main', 'http://moodle.org');
     * $cache->set('docs', 'http://docs.moodle.org');
     * $cache->set('tracker', 'http://tracker.moodle.org');
     * $cache->set('qa', 'http://qa.moodle.net');
     * </code>
     *
     * @param string|int $key The key for the data being requested.
     *      It can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param mixed $data The data to set against the key.
     * @return bool True on success, false otherwise.
     */
    public function set($key, $data) {
        $cachedobject = false;
        if (is_object($data) && $data instanceof cacheable_object) {
            $data = new cache_cached_object($data);
            $cachedobject = true;
        }
        if ($cachedobject or is_scalar($data) or $this->definition->uses_simple_data()) {
            $this->phpunitcache[$key]['data'] = $data;
            $this->phpunitcache[$key]['serialized'] = false;
        } else {
            $this->phpunitcache[$key]['data'] = serialize($data);
            $this->phpunitcache[$key]['serialized'] = true;
        }
        $this->phpunitmodified = true;

        return true;
    }

    /**
     * Sends several key => value pairs to the cache.
     *
     * Using this function comes with potential performance implications.
     * Not all cache stores will support get_many/set_many operations and in order to replicate this functionality will call
     * the equivalent singular method for each item provided.
     * This should not deter you from using this function as there is a performance benefit in situations where the cache store
     * does support it, but you should be aware of this fact.
     *
     * <code>
     * // This code will add four entries to the cache, one for each url.
     * $cache->set_many(array(
     *     'main' => 'http://moodle.org',
     *     'docs' => 'http://docs.moodle.org',
     *     'tracker' => 'http://tracker.moodle.org',
     *     'qa' => ''http://qa.moodle.net'
     * ));
     * </code>
     *
     * @param array $keyvaluearray An array of key => value pairs to send to the cache.
     * @return int The number of items successfully set. It is up to the developer to check this matches the number of items.
     *      ... if they care that is.
     */
    public function set_many(array $keyvaluearray) {
        $count = 0;
        foreach ($keyvaluearray as $key => $data) {
            $this->set($key, $data);
            $count++;
        }
        return $count;
    }

    /**
     * Test is a cache has a key.
     *
     * The use of the has methods is strongly discouraged. In a high load environment the cache may well change between the
     * test and any subsequent action (get, set, delete etc).
     * Instead it is recommended to write your code in such a way they it performs the following steps:
     * <ol>
     * <li>Attempt to retrieve the information.</li>
     * <li>Generate the information.</li>
     * <li>Attempt to set the information</li>
     * </ol>
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param string|int $key
     * @param bool $tryloadifpossible If set to true, the cache doesn't contain the key, and there is another cache loader or
     *      data source then the code will try load the key value from the next item in the chain.
     * @return bool True if the cache has the requested key, false otherwise.
     */
    public function has($key, $tryloadifpossible = false) {
        return isset($this->phpunitcache[$key]);
    }

    /**
     * Test is a cache has all of the given keys.
     *
     * It is strongly recommended to avoid the use of this function if not absolutely required.
     * In a high load environment the cache may well change between the test and any subsequent action (get, set, delete etc).
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param array $keys
     * @return bool True if the cache has all of the given keys, false otherwise.
     */
    public function has_all(array $keys) {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Test if a cache has at least one of the given keys.
     *
     * It is strongly recommended to avoid the use of this function if not absolutely required.
     * In a high load environment the cache may well change between the test and any subsequent action (get, set, delete etc).
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param array $keys
     * @return bool True if the cache has at least one of the given keys
     */
    public function has_any(array $keys) {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Delete the given key from the cache.
     *
     * @param string|int $key The key to delete.
     * @param bool $recurse When set to true the key will also be deleted from all stacked cache loaders and their stores.
     *     This happens by default and ensure that all the caches are consistent. It is NOT recommended to change this.
     * @return bool True of success, false otherwise.
     */
    public function delete($key, $recurse = true) {
        unset($this->phpunitcache[$key]);
        $this->phpunitmodified = true;
        return true;
    }

    /**
     * Delete all of the given keys from the cache.
     *
     * @param array $keys The key to delete.
     * @param bool $recurse When set to true the key will also be deleted from all stacked cache loaders and their stores.
     *     This happens by default and ensure that all the caches are consistent. It is NOT recommended to change this.
     * @return int The number of items successfully deleted.
     */
    public function delete_many(array $keys, $recurse = true) {
        $count = 0;
        foreach ($keys as $key) {
            $this->delete($key);
            $count++;
        }
        return $count;
    }

    /**
     * Purges the cache store, and loader if there is one.
     *
     * @return bool True on success, false otherwise
     */
    public function purge() {
        $this->phpunitcache = array();
        $this->phpunitmodified = true;
        return true;
    }

    /**
     * @return cache_definition
     */
    public function get_definition() {
        return $this->definition;
    }
}