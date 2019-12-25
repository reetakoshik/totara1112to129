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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\hook;

use totara_core\hook\base as base_hook;
use stdClass;

defined('MOODLE_INTERNAL') || die();


/**
 * Class base
 * @package totara_catalog\hook
 */
abstract class base extends base_hook {
    protected $item;

    /**
     * The attributes of the object $item are specified as below:
     * + id: int                -> {catalog}.id
     * + objecttype: string     -> {catalog}.objecttype And this link with course/program/cert id
     * + objectid: int          -> {catalog}.objectid
     * + contextid: int         -> {catalog}.contextid
     * + sorttext: string       -> {catalog}.sorttext
     *
     * @param stdClass $item
     */
    public function __construct(stdClass $item) {
        $this->set_item($item);
    }

    /**
     * @param stdClass $item
     * @return void
     */
    public function set_item(stdClass $item): void {
        if (!object_property_exists($item, 'id')) {
            throw new \coding_exception("The object passed in does not have attribute 'id'");
        }

        $this->item = $item;
    }

    /**
     * Returning the current item
     * @return stdClass
     */
    final public function get_item(): stdClass {
        return $this->item;
    }
}
