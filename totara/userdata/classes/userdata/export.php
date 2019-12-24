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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\userdata;

defined('MOODLE_INTERNAL') || die();

/**
 * Instance of this class represents data returned fom export methods.
 *
 * This is intended to be used from export method of item classes.
 */
final class export {

    /**
     * data exported from item
     *
     * @var array
     */
    public $data = [];

    /**
     * list of stored files referenced in data
     *
     * @var \stored_file[]
     */
    public $files = [];

    /**
     * Make sure no other properties can be added
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        throw new \coding_exception('export instance cannot be modified');
    }

    /**
     * Adds a file to the export and returns data for the file
     * which can be added to the exported data
     *
     * @param \stored_file $file
     * @return array
     */
    public function add_file(\stored_file $file) {
        $this->files[$file->get_id()] = $file;
        return $this->prepare_file_info($file);
    }

    /**
     * Prepare file information for export to have consistent keys
     *
     * @param \stored_file $file
     * @return array
     */
    private function prepare_file_info(\stored_file $file) {
        return [
            'fileid' => $file->get_id(),
            'filename' => $file->get_filename(),
            'contenthash' => $file->get_contenthash()
        ];
    }

}