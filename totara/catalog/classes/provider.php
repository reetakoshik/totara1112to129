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

use core\task\manager as task_manager;
use totara_catalog\dataformatter\formatter;
use totara_catalog\local\catalog_storage;
use totara_catalog\local\config;
use totara_catalog\local\learning_type_dataholders;
use totara_catalog\observer\object_update_observer;
use core\event\base as event_base;
use totara_catalog\task\provider_active_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for modules that provide content for the learning catalog.
 */
abstract class provider {

    // Combination of lowercase letters and underscores, starting with a letter.
    const OBJECT_TYPE_REGEX = '[a-z]{1}[a-z_]*';

    /**
     * Provider Active status
     * @var int
     */
    const PROVIDER_STATUS_ACTIVE = 1;

    /**
     * Provider Inactive status
     * @var int
     */
    const PROVIDER_STATUS_INACTIVE = 0;

    /** @var filter[] */
    private $filters = null;

    /** @var feature[] */
    private $features = null;

    /** @var dataholder[][] */
    private $dataholders = null;

    public function __construct() {
        if (!self::is_valid_object_type($this->get_object_type())) {
            throw new \coding_exception(
                "Bad provider object_type: '{$this->get_object_type()}'. " .
                "Only lowercase and underscores allowed, must start with letter."
            );
        }
    }

    /**
     * Determines if the give object type string is formed correctly.
     *
     * The objecttype is used in url params and various other keys, so is required to confirm to contain only
     * lowercase letters and underscores, and start with a letter.
     *
     * @param string $objecttype
     * @return bool
     */
    public static function is_valid_object_type(string $objecttype): bool {
        return (1 === preg_match('/^' . self::OBJECT_TYPE_REGEX . '$/', $objecttype));
    }

    /**
     * Get the name of the content provider. Used in content type filter and admin screen.
     * Name should be singlular rather than plural.
     *
     * @return string
     */
    abstract public static function get_name(): string;

    /**
     * Get a string which can be used to uniquely identify data that belongs to this provider.
     *
     * @return string
     */
    abstract public static function get_object_type(): string;

    /**
     * Get management link for the current user, or null (e.g. if the user shouldn't see this link).
     *
     * Returned object should contain:
     * label: string
     * url: string
     *
     * @param int $objectid
     * @return \stdClass|null
     */
    abstract public function get_manage_link(int $objectid);

    /**
     * Get details_link template data, or null.
     *
     * Returned object should contain:
     * title: string
     * description: string
     * button: \stdClass (optional)
     * button->label: string
     * button->url: string
     *
     * @param int $objectid
     * @return \stdClass|null
     */
    abstract public function get_details_link(int $objectid);

    /**
     * Get the name of the table that contains the objects that the provider is referencing in the catalog.
     *
     * @return string
     */
    abstract public function get_object_table(): string;

    /**
     * Get the field in the object table that contains the objectid of the objects that the provider is referencing in the catalog.
     *
     * @return string
     */
    abstract public function get_objectid_field(): string;

    /**
     * Get Data holder config
     *
     * @param string $key
     * @return string|array
     */
    abstract public function get_data_holder_config(string $key);

    final public function get_config(string $key) {
        $providerconfig = config::instance()->get_provider_config($this->get_object_type());
        if (!array_key_exists($key, $providerconfig)) {
            throw new \coding_exception("Tried to get a catalog provider config setting that doesn't exist: " . $key);
        }
        return $providerconfig[$key];
    }

    /**
     * Checks if the current user should be able to see the given objects.
     *
     * Return false if the current user does not meet specific criteria that are needed, such as capability checks.
     *
     * @param \stdClass[] containing int objectid and (sometimes) int contextid
     * @return bool[] indexed by objectid
     */
    abstract public function can_see(array $objects): array;

    /**
     * Get sql and params needed to retrieve all objects belonging to this provider which should be included
     * in the catalog index.
     *
     * @return array [sql, params]
     */
    abstract public function get_all_objects_sql(): array ;

    /**
     * Is the plugin that the provider comes from enabled?
     *
     * @return bool
     */
    abstract public static function is_plugin_enabled(): bool;

    /**
     * Get an array of buttons that should be added to the top of the catalog.
     *
     * @return array of url => label
     */
    public function get_buttons(): array {
        return [];
    }

    /**
     * Get an array of create buttons that should be added to the top of the catalog.
     *
     * If only one create button is specified across all providers then the provided label will be prefixed with
     * 'Create ' (or similar in other languages), otherwise a single 'Create' button will be shown and the label
     * will appear in a list when the button is pressed.
     *
     * @return array of url => label
     */
    public function get_create_buttons(): array {
        return [];
    }

    /**
     * Set the active status of the provider. Triggers update of the catalog data - immediately delete when
     * disabling the provider, creating an adhoc task to populate the table when enabling the provider.
     *
     * @param int $status
     * @return bool
     */
    final public static function change_status(int $status): bool {

        switch ($status) {
            case self::PROVIDER_STATUS_ACTIVE:
                if (!catalog_storage::has_provider_data(static::get_object_type())) {
                    $adhoctask = new provider_active_task();
                    $adhoctask->set_custom_data(array('objecttype' => static::get_object_type()));
                    $adhoctask->set_component('totara_catalog');
                    task_manager::queue_adhoc_task($adhoctask);
                }

                break;
            case self::PROVIDER_STATUS_INACTIVE:
                catalog_storage::delete_provider_data(static::get_object_type());
                break;
        }

        return true;
    }

    /**
     * Load and cache filters
     */
    final private function load_filters(): void {
        $filters = [];

        $currentclass = static::class;
        $namespace = substr($currentclass, strpos($currentclass, 'totara_catalog')) . '\\filter_factory';
        $filterfactories = \core_component::get_namespace_classes($namespace, 'totara_catalog\filter_factory');

        /** @var filter_factory $filterfactory */
        foreach ($filterfactories as $filterfactory) {
            $filters = array_merge($filters, $filterfactory::get_filters());
        }

        $this->filters = $filters;
    }

    /**
     * Load and cache features
     */
    final private function load_features(): void {
        if (!is_null($this->features)) {
            return;
        }

        $features = [];

        $currentclass = static::class;
        $namespace = substr($currentclass, strpos($currentclass, 'totara_catalog')) . '\\feature_factory';
        $featurefactories = \core_component::get_namespace_classes($namespace, 'totara_catalog\feature_factory');

        /** @var feature_factory $featurefactory */
        foreach ($featurefactories as $featurefactory) {
            $features = array_merge($features, $featurefactory::get_features());
        }

        $this->features = $features;
    }

    /**
     * Load and cache data holders
     */
    final private function load_dataholders(): void {
        $this->dataholders = [
            formatter::TYPE_PLACEHOLDER_TITLE     => [],
            formatter::TYPE_PLACEHOLDER_TEXT      => [],
            formatter::TYPE_PLACEHOLDER_ICON      => [],
            formatter::TYPE_PLACEHOLDER_ICONS     => [],
            formatter::TYPE_PLACEHOLDER_IMAGE     => [],
            formatter::TYPE_PLACEHOLDER_PROGRESS  => [],
            formatter::TYPE_PLACEHOLDER_RICH_TEXT => [],
            formatter::TYPE_FTS                   => [],
            formatter::TYPE_SORT_TEXT             => [],
            formatter::TYPE_SORT_TIME             => [],
        ];

        $currentclass = static::class;
        $namespace = substr($currentclass, strpos($currentclass, 'totara_catalog')) . '\\dataholder_factory';
        $dataholderfactories = \core_component::get_namespace_classes($namespace, 'totara_catalog\dataholder_factory');

        /** @var dataholder_factory $dataholderfactory */
        foreach ($dataholderfactories as $dataholderfactory) {
            $dataholders = $dataholderfactory::get_dataholders();
            foreach ($dataholders as $dataholder) {
                $this->add_dataholder($dataholder);
            }
        }

        // Add static data holders.
        $providerholders = learning_type_dataholders::create($this->get_name());
        foreach ($providerholders as $providerholder) {
            $this->add_dataholder($providerholder);
        }
    }

    /**
     * Adds the specified dataholder into this object's dataholder property, indexed by each type that the
     * dataholder can be used as.
     *
     * @param dataholder $dataholder
     */
    final private function add_dataholder(dataholder $dataholder) {
        foreach ($dataholder->datajoins as $joinalias => $joinstring) {
            if (substr($joinalias, 0, strlen($dataholder->key)) != $dataholder->key) {
                throw new \coding_exception('Dataholder datajoin alias not prefixed with dataprovider key: ' . $dataholder->key);
            }
        }

        foreach ($dataholder->dataparams as $paramkey => $paramvalue) {
            if (substr($paramkey, 0, strlen($dataholder->key)) != $dataholder->key) {
                throw new \coding_exception('Dataholder dataparam key not prefixed with dataprovider key: ' . $dataholder->key);
            }
        }

        foreach ($dataholder->formatters as $formattertype => $formatter) {
            if (isset($this->dataholders[$formattertype][$dataholder->key])) {
                throw new \coding_exception("Tried to create two catalog dataholders with the same key: " . $dataholder->key);
            }
            $this->dataholders[$formattertype][$dataholder->key] = $dataholder;
        }
    }

    /**
     * Observe object update events
     *
     * @param event_base $event
     */
    final public static function object_update_observer(event_base $event) {
        global $CFG;

        if ($CFG->catalogtype != 'totara') {
            return ;
        }

        $currentclass = static::class;
        $namespace = substr($currentclass, strpos($currentclass, 'totara_catalog')) . '\\observer';
        $observers = \core_component::get_namespace_classes($namespace, 'totara_catalog\observer\object_update_observer');

        foreach ($observers as $observerclass) {
            /** @var object_update_observer $observer */
            $observer = new $observerclass(static::get_object_type(), $event);
            foreach ($observer->get_observer_events() as $eventclass) {
                if ($event instanceof $eventclass) {
                    $observer->process();
                }
            }
        }
    }

    /**
     * Get all the filter belonging to this provider.
     *
     * @return filter[]
     */
    final public function get_filters(): array {
        if (is_null($this->filters)) {
            $this->load_filters();
        }

        return $this->filters;
    }

    /**
     * Get all the features belonging to this provider.
     *
     * @return feature[]
     */
    final public function get_features(): array {
        if (is_null($this->features)) {
            $this->load_features();
        }

        return $this->features;
    }

    /**
     * Get all dataholders of the specified formatter type belonging to this provider.
     *
     * @param int $formattertype
     * @return dataholder[]
     */
    final public function get_dataholders(int $formattertype): array {
        if (is_null($this->dataholders)) {
            $this->load_dataholders();
        }

        return $this->dataholders[$formattertype];
    }
}
