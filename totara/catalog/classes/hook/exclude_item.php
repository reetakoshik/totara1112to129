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
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * A hook that helps to exclude the item specifically that can be specified/defined
 * in its own handlers.
 *
 * Class exclude_item
 * @package totara_catalog\hook
 */
final class exclude_item extends base {
    /**
     * @var bool
     */
    private $exclude;

    /**
     * exclude_item constructor.
     * @param stdClass $item
     */
    public function __construct(stdClass $item) {
        parent::__construct($item);

        // We probably want the item to not be excluded by default.
        $this->exclude = false;
    }

    /**
     * @return bool
     */
    public function is_excluded(): bool {
        return $this->exclude;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function set_exclude(bool $value): void {
        $this->exclude = $value;
    }
}
