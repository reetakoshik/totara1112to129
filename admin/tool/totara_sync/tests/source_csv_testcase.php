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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');

abstract class totara_sync_csv_testcase extends advanced_testcase {

    /* @var \tool_totara_sync\internal\source\csv_trait|totara_sync_source */
    protected $source;
    protected $elementname;

    /**
     * Sets the source config.
     *
     * @param array $config
     */
    public function set_source_config($config) {
        foreach ($config as $k => $v) {
            $this->source->set_config($k, $v);
        }
    }

    /**
     * Sets the element config.
     *
     * @param array $config
     */
    public function set_element_config($config) {
        foreach ($config as $k => $v) {
            $this->get_element($this->elementname)->set_config($k, $v);
        }
    }

    /**
     * Get the element object
     *
     * @return object
     */
    public function get_element() {
        $elements = totara_sync_get_elements(true);
        return $elements[$this->elementname];
    }

    /**
     * Create the file directory structure
     *
     * @return string The file directory path
     */
    public function create_filedir() {
        global $CFG;

        $filedir = $CFG->dataroot . '/totara_sync';
        mkdir($filedir . '/csv/ready', 0777, true);
        return $filedir;
    }

    /**
     * Run the check_sanity
     *
     * @return bool
     */
    public function check_sanity() {
        $synctable = $this->get_element()->get_source_sync_table();
        $synctable_clone = $this->get_element()->get_source_sync_table_clone($synctable);
        $result = $this->get_element()->check_sanity($synctable, $synctable_clone);
        $this->source->drop_table($synctable_clone);

        return $result;
    }

    /**
     * Run the sync
     *
     * @return bool
     */
    public function sync() {
        return $this->get_element($this->elementname)->sync();
    }

    /**
     * Adds a csv file to the external directory
     *
     * @param string $name The csv filename
     * @param string $element The element name
     */
    public function add_csv($name, $element = 'user') {
        $data = file_get_contents(__DIR__ . '/fixtures/' . $name);
        $filepath = $this->filedir . '/csv/ready/' . $element . '.csv';
        file_put_contents($filepath, $data);
    }
}
