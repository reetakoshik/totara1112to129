<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

namespace totara_core;

/**
 * Representation of the source of tabular data.
 *
 * Sources need to be able to iterate over the data rows
 * and provide basic information.
 *
 * @package totara_core
 */
abstract class tabexport_source implements \Iterator {
    /** @var string $format how the data should be formatted */
    protected $format;

    /**
     * Use from the constructor of tabexport_writer.
     *
     * This tells the source how to format the row data.
     *
     * @param string $format export type - 'html', 'text', 'excel', 'ods' or 'pdf'.
     */
    public function set_format($format) {
        $this->format = $format;
    }

    /**
     * Returns the current format used when producing row data.
     *
     * @return string 'html', 'text', 'excel', 'ods', 'csv' or 'pdf'
     */
    public function get_format() {
        return $this->format;
    }

    /**
     * Doest the source have custom header?
     * @return mixed null if standard header used, anything else is data for custom header
     */
    public function get_custom_header() {
        return null;
    }

    /**
     * Block of extra frontpage information.
     *
     * @return string[] array of html texts describing the data
     */
    public function get_extra_information() {
        return null;
    }

    /**
     * Returns localised full name of this source.
     *
     * @return string html fragment
     */
    public abstract function get_fullname();

    /**
     * Get the list of headings.
     *
     * @return string[] list of column headings
     */
    public abstract function get_headings();

    /**
     * Return graph image if present.
     *
     * This is mostly a hack for reportbuilder graphs.
     *
     * @param int $w
     * @param int $h
     * @return string SVG file content
     */
    public function get_svg_graph($w, $h) {
        return null;
    }

    /**
     * Returns current row of data formatted according to specified type.
     *
     * @return array
     */
    public abstract function current();

    /**
     * Returns the key of current row
     *
     * @return int current row
     */
    public abstract function key();

    /**
     * Moves forward to next row
     *
     * @return void
     */
    public abstract function next();

    /**
     * Did we reach the end?
     *
     * @return boolean
     */
    public abstract function valid();

    /**
     * Not necessary.
     *
     * @return void
     */
    public function rewind() {
        return;
    }

    /**
     * Free resources, source will not be used anymore.
     *
     * @return void
     */
    public abstract function close();
}
