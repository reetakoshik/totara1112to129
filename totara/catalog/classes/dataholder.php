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

use totara_catalog\dataformatter\formatter;

defined('MOODLE_INTERNAL') || die();

/**
 * A mostly-simple container to define a dataholder, which connects the sql required to retrieve some data
 *  from the database with the dataformatters needed to properly display it.
 *
 * @package totara_catalog
 */
final class dataholder {

    /** @var string */
    public $key;

    /** @var string */
    public $name;

    /** @var formatter[] */
    public $formatters = [];

    /** @var string[] */
    public $datajoins;

    /** @var [] */
    public $dataparams;

    /** @var string */
    public $category;

    /**
     * Dataholder constructor.
     *
     * @param string   $key            of type PARAM_ALPHANUMEXT (enforced)
     * @param string   $name           that can be displayed to users, MUST be sanitisedif it comes from
     *                                 user data, e.g. by using format_string
     * @param formatter[] $formatters  the dataformatters that can be used to output the data
     * @param string[] $datajoins      e.g. "LEFT JOIN {course} course ON course.id = catalog.objectid"
     * @param string[] $dataparams     of param => value
     * @param string   $category       optional, used for sectioning of select lists in admin config form
     */
    public function __construct(
        string $key,
        string $name,
        array $formatters,
        array $datajoins = [],
        array $dataparams = [],
        string $category = null
    ) {
        if ($key != clean_param($key, PARAM_ALPHANUMEXT)) {
            throw new \coding_exception('Tried to create a dataholder with invalid key type: ' . $key);
        }

        foreach ($formatters as $formattertype => $formatter) {
            if (!$formatter->is_suitable_for_type($formattertype)) {
                throw new \coding_exception(
                    "Tried to create dataholder with data formatter type which isn't suitable for the data formatter: " . $key
                );
            }
        }

        $this->key = $key;
        $this->name = $name;
        $this->formatters = $formatters;
        $this->datajoins = $datajoins;
        $this->dataparams = $dataparams;
        $this->category = $category ?? new \lang_string('default_option_group', 'totara_catalog');
    }

    /**
     * Given the data configured by the 'data' properties, gets the formatted value using the formatter
     * of the specified formatter type.
     *
     * @param int $formattertype
     * @param \context $context
     * @param array $data
     * @return mixed
     */
    public function get_formatted_value(int $formattertype, array $data, \context $context) {
        if (isset($this->formatters[$formattertype])) {
            return $this->formatters[$formattertype]->get_formatted_value($data, $context);
        } else {
            throw new \coding_exception('Invalid formatter type: ' . $formattertype);
        }
    }
}
