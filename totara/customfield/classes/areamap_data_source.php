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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_customfield
 * @category cache
 */

namespace totara_customfield;

/**
 * Class areamap_data_source
 *
 * @internal this should never be instantiated other than by the cache API.
 */
class areamap_data_source implements \cache_data_source {

    /**
     * The key to the component map in the cached data.
     */
    const COMPONENTMAP = 'componentmap';

    /**
     * The key to the class map in the cached data.
     */
    const CLASSMAP = 'classmap';

    /**
     * The key to the class map in the cached data.
     */
    const FILEAREAMAP = 'fileareamap';

    /**
     * The key to the class map in the cached data.
     */
    const PREFIXMAP = 'prefixmap';

    /**
     * An array mapping an areaname to a component (string).
     *
     * Populated only by {@link \totara_customfield\areamap_data_source::populate()} and should never be read directly
     * other than by {@link \totara_customfield\areamap_data_source::load_for_cache()}.
     *
     * The data in this key will be an array, with the following internal properties:
     *
     *  - Key:   Area (string)
     *  - Value: Component (string)
     *
     * @var string[]
     */
    private $componentmap = null;

    /**
     * An array mapping an areaname to a class that manages it.
     *
     * Populated only by {@link \totara_customfield\areamap_data_source::populate()} and should never be read directly
     * other than by {@link \totara_customfield\areamap_data_source::load_for_cache()}.
     *
     * The data in this key will be an array, with the following internal properties:
     *
     *  - Key:   Area (string)
     *  - Value: Classname (string) Fully qualified class name for the managing class.
     *
     * @var \totara_customfield\area[]
     */
    private $classmap = null;

    /**
     * An array mapping file areas to their owning customfield areas.
     *
     * Populated only by {@link \totara_customfield\areamap_data_source::populate()} and should never be read directly
     * other than by {@link \totara_customfield\areamap_data_source::load_for_cache()}.
     *
     * The data in this property will be an array, with the following internal properties:
     *
     *  - Key:   Filearea (string)
     *  - Value: Classname (string) Fully qualified class name for the managing class.
     *
     * Note: An area can have multiple file areas, although typically each area has two.
     *
     * @var \totara_customfield\area[]
     */
    private $fileareamap = null;

    /**
     * An array mapping a prefix to a class that manages it.
     *
     * Populated only by {@link \totara_customfield\areamap_data_source::populate()} and should never be read directly
     * other than by {@link \totara_customfield\areamap_data_source::load_for_cache()}.
     *
     * The data in this property will be an array, with the following internal properties:
     *
     *  - Key:   Prefix (string) Used by the customfields API as a unique area identifier.
     *  - Value: Class (string)  Fully qualified class name for the managing class.
     *
     * @var \totara_customfield\area[]
     */
    private $prefixmap = null;

    /**
     * Returns an instance of the data source class that the cache can use for loading data using the other methods
     * specified by this interface.
     *
     * @param \cache_definition $definition
     * @return areamap_data_source
     */
    public static function get_instance_for_cache(\cache_definition $definition) {
        // Don't statically cache this, that would pollute memory within tests.
        return new areamap_data_source($definition);
    }

    /**
     * The definition used to create this instance.
     * @var \cache_definition
     */
    private $definition;

    /**
     * Constructs a new areamap cache datasource.
     *
     * @param \cache_definition $definition
     */
    protected function __construct(\cache_definition $definition) {
        // We'll hold onto this just in case we need it in the future.
        $this->definition = $definition;
    }

    /**
     * Loads the data for the key provided ready formatted for caching.
     *
     * @param string|int $key The key to load.
     * @return area[]|string[]|false What ever data should be returned, or false if it can't be loaded.
     */
    public function load_for_cache($key) {
        $this->populate();
        switch ($key) {
            case self::COMPONENTMAP:
                return $this->componentmap;
            case self::CLASSMAP:
                return $this->classmap;
            case self::FILEAREAMAP:
                return $this->fileareamap;
            case self::PREFIXMAP:
                return $this->prefixmap;
            default:
                debugging('Unknown key requested from \totara_customfield\cache_areamap::load_for_cache ' . $key, DEBUG_DEVELOPER);
                return false;
        }
    }

    /**
     * Loads several keys for the cache.
     *
     * @param array $keys An array of keys each of which will be string|int.
     * @return array An array of matching data items.
     */
    public function load_many_for_cache(array $keys) {
        $return = array();
        foreach ($keys as $key) {
            $return[$key] = $this->load_for_cache($key);
        }
        return $return;

    }

    /**
     * Populates the componentmap and classmap variables.
     *
     * There are four variables are used to populate the cache.
     * Because they are generated together through namespace inspection, we use properties to store them in order to save us from
     * having to run the inspection multiple times.
     */
    private function populate() {
        if ($this->componentmap !== null) {
            return;
        }
        $this->componentmap = array();
        $this->classmap = array();
        $this->fileareamap = array();

        $classes = \core_component::get_namespace_classes('customfield_area', '\totara_customfield\area');
        foreach ($classes as $class) {

            $prefix = forward_static_call(array($class, 'get_prefix'));
            $area = forward_static_call(array($class, 'get_area_name'));
            $component = forward_static_call(array($class, 'get_component'));
            $fileareas = forward_static_call(array($class, 'get_fileareas'));

            if (isset($this->componentmap[$area])) {
                // If you get here then you are adding a new custom field area, and you're going to hit problems because you have
                // chosen an area name that is not unique. It must be unique.
                throw new \coding_exception('Duplicate customfield area name found when populating the cache.', $area);
            }

            if (isset($this->prefixmap[$prefix])) {
                // If you get here then the prefix you need to use is not unique.
                // It may be unique in the database, but it must be unique in code as well.
                // Please choose a new, unique prefix.
                throw new \coding_exception('Duplicate customfield prefix found when populating the cache.', $area);
            }

            $this->componentmap[$area] = $component;
            $this->classmap[$area] = $class;
            $this->prefixmap[$prefix] = $class;
            foreach ($fileareas as $filearea) {
                if (isset($this->fileareamap[$filearea])) {
                    // The file area you have chosen is not unique.
                    // It may be unique to the component, but because of how Totara customfields works it must be unique without
                    // the component as well.
                    // Please choose a new, unique file area name.
                    throw new \coding_exception('Duplicate customfield filearea found when populating the cache.', $filearea);
                }
                $this->fileareamap[$filearea] = $class;
            }
        }
    }
}
