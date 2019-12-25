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

namespace totara_catalog\dataformatter;

defined('MOODLE_INTERNAL') || die();

/**
 * Class formatter
 *
 * Implement this class to provide a class which can be used to format data. Each class can define the
 * required data properties and the format of the output.
 *
 * @package totara_catalog\dataformatter
 */
abstract class formatter {

    // Dataformatters must return string suitable for display.
    const TYPE_PLACEHOLDER_TITLE     =  1;
    // Dataformatters must return string suitable for display.
    const TYPE_PLACEHOLDER_TEXT      =  2;
    // Dataformatters must return a stdClass with either 'icon' or 'url' and 'alt' properties.
    const TYPE_PLACEHOLDER_ICON      =  3;
    // Dataformatters must return an array of stdClass with either 'icon' or 'url' and 'alt' properties.
    const TYPE_PLACEHOLDER_ICONS     =  4;
    // Dataformatters must return a stdClass with 'url' and 'alt' properties.
    const TYPE_PLACEHOLDER_IMAGE     =  5;
    // Dataformatters must return data compatible with progress_bar->export_for_template().
    const TYPE_PLACEHOLDER_PROGRESS  =  6;
    // Dataformatters must return string suitable for display, may contain html.
    const TYPE_PLACEHOLDER_RICH_TEXT =  7;
    // Dataformatters must return string suitable for including in the FTS index.
    const TYPE_FTS                   =  8;
    // Dataformatters must return string suitable for sorting.
    const TYPE_SORT_TEXT             =  9;
    // Dataformatters must return int (representing a date) suitable for sorting.
    const TYPE_SORT_TIME             = 10;

    private $requirefields = [];

    /**
     * @return string[]
     */
    abstract public function get_suitable_types(): array;

    /**
     * @param int $formattertype
     * @return bool
     */
    public function is_suitable_for_type(int $formattertype) {
        return in_array($formattertype, $this->get_suitable_types());
    }

    /**
     * Format the given data.
     *
     * The value returned MUST be sanitary, e.g. by using format_string.
     *
     * @param array $data
     * @param \context $context
     * @return mixed
     */
    abstract public function get_formatted_value(array $data, \context $context);

    /**
     * Define a source of data that is required by this formatter.
     *
     * @param string $key The key which will be used when the data is returned
     * @param string $source The sql source of the data
     * @throws \coding_exception
     */
    protected function add_required_field(string $key, string $source) {
        if (!empty($this->requirefields[$key])) {
            throw new \coding_exception("Tried to define a required field more than once: " . $key);
        }

        $this->requirefields[$key] = $source;
    }

    /**
     * Get all of the data sources which are required by this data formatter.
     *
     * @return array
     */
    public function get_required_fields(): array {
        return $this->requirefields;
    }
}
