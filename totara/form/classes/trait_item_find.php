<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package totara_form
 */

namespace totara_form;

/**
 * Trait for item lookup.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
trait trait_item_find {
    /**
     * Find first child item that matches the criteria.
     *
     * @param mixed $search value to match
     * @param string $method method to get value from item
     * @param string $class filter by class, totara_form\form\item means all
     * @param bool $recursive true means look in sub items too
     * @param array $arguments method arguments
     * @param bool $strict use strict comparison
     * @return item found item or null if not found
     */
    public function find($search, $method, $class = 'totara_form\item', $recursive = true, array $arguments = null, $strict = true) {
        $arguments = (array)$arguments;
        /** @var item $this */
        foreach ($this->get_items() as $item) {
            if ($item instanceof $class) {
                if (method_exists($item, $method)) {
                    $result = call_user_func_array(array($item, $method), $arguments);
                    if ($strict) {
                        if ($search === $result) {
                            return $item;
                        }
                    } else {
                        if ($search == $result) {
                            return $item;
                        }
                    }
                }
            }
            if ($recursive) {
                $result = $item->find($search, $method, $class, true, $arguments, $strict);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }
}
