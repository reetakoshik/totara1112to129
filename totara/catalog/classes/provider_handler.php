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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog;

use totara_catalog\local\config;
use totara_catalog\local\required_dataholder;

defined('MOODLE_INTERNAL') || die();

class provider_handler {

    /** @var provider_handler */
    private static $instance;

    /** @var provider[] */
    private $allproviderclasses = null;

    /** @var provider[] */
    private $activeproviderclasses = null;

    /** @var provider[] */
    private $activeproviders = null;

    /**
     * Return a singleton instance.
     *
     * @return provider_handler
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
     * This function should be used after data relating to providers has changed, including during testing.
     */
    public function reset_cache() {
        $this->allproviderclasses = null;
        $this->activeproviderclasses = null;
        $this->activeproviders = null;
    }

    /**
     * Gets an array of all provider class names that can be enabled in the catalog, indexed by objecttype.
     *
     * Excludes providers that are not available because is_plugin_enabled() returns false.
     *
     * @return string[]
     */
    public function get_all_provider_classes() {
        if (empty($this->allproviderclasses)) {
            $providernames = \core_component::get_namespace_classes('totara_catalog', 'totara_catalog\provider');

            $allproviderclasses = [];

            /** @var provider $providername */
            foreach ($providernames as $key => $providername) {
                if (!$providername::is_plugin_enabled()) {
                    continue;
                }

                $allproviderclasses[$providername::get_object_type()] = $providername;
            }

            $this->allproviderclasses = $allproviderclasses;
        }

        return $this->allproviderclasses;
    }

    /**
     * Gets an array of all provider class names that are active in the catalog, indexed by objecttype.
     *
     * Excludes providers that are not available because the plugin they belong to is disabled.
     *
     * @return string[]
     */
    private function get_active_provider_classes() {
        if (empty($this->activeproviderclasses)) {
            $providernames = $this->get_all_provider_classes();

            $activeproviderclasses = [];

            /** @var provider $providername */
            foreach ($providernames as $key => $providername) {
                if (!config::instance()->is_provider_active($providername::get_object_type())) {
                    continue;
                }

                $activeproviderclasses[$providername::get_object_type()] = $providername;
            }

            $this->activeproviderclasses = $activeproviderclasses;
        }

        return $this->activeproviderclasses;
    }

    /**
     * Get all active providers, indexed by objecttype.
     *
     * Excludes providers that are not available because the plugin they belong to is disabled.
     *
     * @return provider[]
     */
    public function get_active_providers() {
        if (is_null($this->activeproviders)) {
            $providernames = $this->get_active_provider_classes();

            $providers = [];

            /** @var provider $providername */
            foreach ($providernames as $providername) {
                /** @var provider $provider */
                $provider = new $providername();

                $object_type = $provider::get_object_type();

                $providers[$object_type] = $provider;
            }

            $this->activeproviders = $providers;
        }

        return $this->activeproviders;
    }

    /**
     * Determine if the specified provider is active or not.
     *
     * @param string $objecttype
     * @return bool
     */
    public function is_active(string $objecttype) {
        $providers = $this->get_active_provider_classes();

        return !empty($providers[$objecttype]);
    }

    /**
     * Gets one of the active providers.
     *
     * Inactive providers should not be instantiated, and trying to get one will result in an exception.
     *
     * @param string $objecttype
     * @return provider
     */
    public function get_provider(string $objecttype) {
        $providers = $this->get_active_providers();

        if (empty($providers[$objecttype])) {
            throw new \coding_exception("Tried to get instance of a catalog provider that wasn't found: " . $objecttype);
        }

        return $providers[$objecttype];
    }

    /**
     * Gets the sql needed to retrieve all of the data for the given dataholders.
     *
     * The sql returned does not include a WHERE clause. You should add one, and specify which object(s)
     * should be retrieved, from table "base".
     *
     * Note that the dataholders must be related to the given provider.
     *
     * @param provider $provider
     * @param required_dataholder[] $requireddataholders
     * @return array [$sql, $params]
     */
    private function get_sql_from_dataholders(provider $provider, array $requireddataholders): array {

        $fields = [];
        $joins = [];
        $params = [];

        foreach ($requireddataholders as $requireddataholder) {
            $dataholder = $requireddataholder->dataholder;
            $formattertype = $requireddataholder->formattertype;
            $formatter = $requireddataholder->formatter;

            foreach ($formatter->get_required_fields() as $fieldalias => $fieldsource) {
                $fields[$dataholder->key . '_' . $formattertype . '_' . $fieldalias] = $fieldsource;
            }

            foreach ($dataholder->datajoins as $joinalias => $joinstring) {
                // No need to check for key conflicts - providers guarantee they are prefixed with the dataholder key.
                $joins[$joinalias] = $joinstring;
            }

            foreach ($dataholder->dataparams as $paramkey => $paramvalue) {
                // No need to check for key conflicts - providers guarantee they are prefixed with the dataholder key.
                $params[$paramkey] = $paramvalue;
            }
        }

        $fieldsql = 'base.id';
        foreach ($fields as $fieldalias => $fieldsource) {
            $fieldsql .= ", {$fieldsource} AS {$fieldalias}";
        }

        $joinsql = implode(" ", $joins);

        $sql = "SELECT {$fieldsql}
                  FROM {$provider->get_object_table()} base
                  {$joinsql}";
        $params['catalog_objecttype'] = $provider->get_object_type();

        return array($sql, $params);
    }

    /**
     * Pass the appropriate results to the dataholder to format using the relevant dataformatter.
     *
     * @param \stdClass $record
     * @param dataholder $dataholder
     * @param int $formattertype
     * @param \context $context
     * @return string
     */
    private function get_formatted_value_from_dataholder(
        \stdClass $record,
        dataholder $dataholder,
        int $formattertype,
        \context $context
    ) {
        $fields = [];

        $formatter = $dataholder->formatters[$formattertype];

        foreach ($formatter->get_required_fields() as $fieldalias => $fieldsource) {
            $recordfield = $dataholder->key . '_' . $formattertype . '_' . $fieldalias;
            $fields[$fieldalias] = $record->$recordfield;
        }

        return $dataholder->get_formatted_value($formattertype, $fields, $context);
    }

    /**
     * Get formatted data for the given objects.
     *
     * The result is the objects passed in, each with an additional 'data' property.
     * Data is a multi-dimensional array [$dataholder->type][$dataholder->key] of values.
     *
     * Don't pass in too many objects in one call! Use array_chunk and call this function in batches.
     *
     * @param \stdClass[] $objects containing 'objecttype', 'objectid' and 'contextid'
     * @param required_dataholder[][] $requireddataholders indexed by objecttype
     * @return \stdClass[]
     */
    public function get_data_for_objects(array $objects, array $requireddataholders) {
        global $DB;

        // Divide the objects into containers for each provider.
        $providers = [];
        foreach ($objects as $object) {
            if (!isset($providers[$object->objecttype])) {
                // Prepare all the stuff relating to the provider.
                $providerinfo = new \stdClass();
                $providerinfo->provider = $this->get_provider($object->objecttype);
                $providerinfo->objectids = [];
                $providerinfo->contextids = [];

                list($providerinfo->sql, $providerinfo->params) =
                    $this->get_sql_from_dataholders($providerinfo->provider, $requireddataholders[$object->objecttype]);

                $providers[$object->objecttype] = $providerinfo;
            }

            $providers[$object->objecttype]->objectids[$object->objectid] = $object->objectid;
            $providers[$object->objecttype]->contextids[$object->contextid] = $object->contextid;
        }

        // Load the data for each provider in bulk.
        foreach ($providers as $objecttype => $providerinfo) {
            // Load all of the object data.
            list($insql, $inparams) = $DB->get_in_or_equal($providerinfo->objectids, SQL_PARAMS_NAMED, 'objectid');

            $providerinfo->sql .= " WHERE base.id {$insql}";
            $providerinfo->params = array_merge($providerinfo->params, $inparams);

            $providerinfo->records = $DB->get_records_sql($providerinfo->sql, $providerinfo->params);

            // Load all of the context data.
            list($insql, $inparams) = $DB->get_in_or_equal($providerinfo->contextids, SQL_PARAMS_NAMED, 'contextid');

            $ctxfields = \context_helper::get_preload_record_columns_sql('ctx');
            $sql = "SELECT {$ctxfields}
                      FROM {context} ctx
                     WHERE id {$insql}";

            $providerinfo->contextrecords = $DB->get_records_sql($sql, $inparams);
        }

        // Iterate over the original list of objects to produce the final results.
        foreach ($objects as $object) {
            $providerinfo = $providers[$object->objecttype];

            if (empty($providerinfo->records[$object->objectid])) {
                // The data for the object doesn't exist. This is most likely because the specified object has been
                // deleted. Generally, this objectid shouldn't have been specified, but it's safe to just skip it.
                continue;
            }

            // Find the data for the object from the bulk data that was retrieved just above.
            $record = $providerinfo->records[$object->objectid];

            if (empty($providerinfo->contextrecords[$object->contextid])) {
                // The context for the object doesn't exist. Objects without contexts should use the system context.
                // The specified contextid must not exist in the context table. Either the contextid is wrong, or the
                // context record was deleted but the object record wasn't. Neither case should happen, ever.
                throw new \coding_exception("Missing context record for id: " . $object->contextid);
            }

            // Load the context for the object.
            \context_helper::preload_from_record($providerinfo->contextrecords[$object->contextid]);
            $context = \context::instance_by_id($object->contextid);

            // Put the formatted data into a structure matching the dataholder specifications.
            $object->data = [];
            foreach ($requireddataholders[$object->objecttype] as $requireddataholder) {
                $object->data[$requireddataholder->formattertype][$requireddataholder->dataholder->key] =
                    $this->get_formatted_value_from_dataholder(
                        $record,
                        $requireddataholder->dataholder,
                        $requireddataholder->formattertype,
                        $context
                    );
            }
        }

        return $objects;
    }
}
