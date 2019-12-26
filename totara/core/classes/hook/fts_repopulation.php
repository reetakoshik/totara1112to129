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
 * @package totara_core
 */

namespace totara_core\hook;

/**
 * Hook used for discovery of all database tables used for full text search
 * and related repopulate methods.
 */
class fts_repopulation extends \totara_core\hook\base {
    /**
     * @var callable[]
     */
    protected $methods = array();

    /**
     * Add new method to repopulate fts table data.
     *
     * @param string $tablename name of database table, must be unique
     * @param callable $method something executable to rebuild the fts index table contents
     */
    public function add_method(string $tablename, callable $method) {
        if (isset($this->methods[$tablename])) {
            debugging('FTS repopulation table name collision: ' . $tablename, DEBUG_DEVELOPER);
            return;
        }
        $this->methods[$tablename] = $method;
    }

    /**
     * Returns all methods discovered.
     *
     * @return callable[] list of methods indexed with table name.
     */
    public function get_methods() {
        return $this->methods;
    }
}