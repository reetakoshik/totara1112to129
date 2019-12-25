<?php // $Id$
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 *
 */
class rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;

/**
 * Class constructor
 *
 * Call from the constructor of all child classes with:
 *
 *  parent::__construct()
 *
 * to ensure child class has implemented everything necessary to work.
 *
 */
    function __construct() {
        // check that child classes implement required properties
        $properties = array(
            'url',
            'source',
            'fullname',
            'columns',
        );
        foreach ($properties as $property) {
            if (!property_exists($this, $property)) {
                throw new Exception("Property '$property' must be set in class " .
                    get_class($this));
            }
        }

        // set sensible defaults for optional properties
        $defaults = array(
            'filters' => array(),
            'embeddedparams' => array(),
            'hidden' => 1, // hide embedded reports by default
            'accessmode' => 0,
            'contentmode' => 0,
            'accesssettings' => array(),
            'contentsettings' => array(),
        );
        foreach ($defaults as $property => $default) {
            if (!property_exists($this, $property)) {
                $this->$property = $default;
            } else if ($this->$property === null) {
                $this->$property = $default;
            }
        }
    }

    /**
     * Clarify if current embedded report support global report restrictions.
     * Override to true for reports that support GRR
     * @return boolean
     */
    public function embedded_global_restrictions_supported() {
        return false;
    }

    /**
     * Is this embedded report usable?
     *
     * If true returned the report is not displayed in the list of all embedded reports.
     * If source is ignored then this method is irrelevant.
     *
     * @deprecated since Totara 12.3
     * @return bool
     */
    public function is_ignored() {
        return false;
    }

    /**
     * Is this embedded report usable?
     *
     * If true returned, the report is not displayed in the list of all embedded reports.
     * If source is ignored, then this method is irrelevant.
     *
     * @return bool
     */
    public static function is_report_ignored() {
        return false;
    }

    /**
     * Look up the embedded name for a heading for a particular embedded report
     *
     * @param string $type The type of the column
     * @param string $value The value of the column
     *
     * @return string The heading specified in the embedded report or false if it's not specified
     */
    function get_embedded_heading($type, $value) {
        if (!isset($this->columns) || !is_array($this->columns)) {
            // no columns defined
            return false;
        }
        foreach ($this->columns as $column) {
            if ($column['type'] == $type && $column['value'] == $value) {
                // return the column's heading
                return $column['heading'];
            }
        }
        // column matching that type/value pair not found
        return false;
    }

    /**
     * Get extra buttons for the top right of the tables toolbar.
     *
     * @return string The rendered output for the buttons
     */
    function get_extrabuttons() {
        return false;
    }

    /**
     * Allows embedded report to override page header in reportbuilder exports.
     *
     * @param reportbuilder $report
     * @param string $format 'html', 'text', 'excel', 'ods', 'csv' or 'pdf'
     * @return string|null must be possible to cast to string[][]
     */
    public function get_custom_export_header(reportbuilder $report, $format) {
        return $report->src->get_custom_export_header($report, $format);
    }

    /**
     * Returns true if require_login should be executed when the report is access through a page other than
     * report.php or an embedded report's webpage, e.g. through ajax calls.
     *
     * @return boolean True if require_login should be executed
     */
    public function needs_require_login() {
        return true;
    }
}
