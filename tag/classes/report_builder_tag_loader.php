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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_tag
 */

namespace core_tag;

defined('MOODLE_INTERNAL') || die();

/**
 * Report builder tag loader class
 *
 * This class helps us by ensuring that the tags we're looking at across all reports are consistent.
 * Tags are statically cached when loaded, and held for the lifetime of the request.
 * We don't mind this, as you shouldn't be able to add a tag and produce a report afterwards in the same request.
 * Adding a tag should always lead to a refresh.
 * If you do need to reset this static cache then please call \core_tag\report_builder_tag_loader::reset()
 */
final class report_builder_tag_loader {

    /**
     * @var report_builder_tag_loader|null
     */
    private static $instance;

    /**
     * A multidimensional array, tags[component][itemtype][tags]
     * @var array
     */
    private $tags = [];

    /**
     * Gets all of the tags for the given component and itemtype.
     *
     * @param string $component
     * @param string $itemtype
     * @return \stdClass[]
     */
    public static function get_tags(string $component, string $itemtype): array {
        $instance = self::instance();
        $instance->ensure_tags_loaded($component, $itemtype);
        return $instance->tags[$component][$itemtype];
    }

    /**
     * Returns an instance of this class.
     *
     * @return report_builder_tag_loader
     */
    private static function instance(): report_builder_tag_loader {
        if (self::$instance === null) {
            self::$instance = new report_builder_tag_loader();
        }
        return self::$instance;
    }

    /**
     * Resets this instance.
     */
    public static function reset() {
        self::$instance = null;
    }

    /**
     * Loads the tags for the given component and item type if not already loaded.
     *
     * @param string $component
     * @param string $itemtype
     */
    private function ensure_tags_loaded(string $component, string $itemtype) {
        if (!isset($this->tags[$component][$itemtype])) {

            $tagcollectionid = \core_tag_area::get_collection($component, $itemtype);
            $tags = \core_tag_collection::get_tags($tagcollectionid);
            $this->tags[$component][$itemtype] = $tags;
        }
    }
}