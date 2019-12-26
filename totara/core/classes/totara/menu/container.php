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
 * @package totara_core
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 */
namespace totara_core\totara\menu;

class container extends item {
    /**
     * Container cannot have URL.
     *
     * @param bool $replaceparams ignored
     * @return string always ''
     */
    final public function get_url($replaceparams = true) {
        return '';
    }

    /**
     * Container cannot have URL.
     *
     * @return string always ''
     */
    final protected function get_default_url() {
        return '';
    }
}
