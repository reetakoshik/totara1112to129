<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2013 onwards Totara Learning Solutions LTD
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Base hook class - all other hook classes must extend this class.
 *
 * The reason is that we want all hooks to be clearly documented
 * so that developers can manage hook listeners easily. Supporting
 * arbitrary classes would end up in a horrible mess.
 *
 * Each hook class is responsible for managing and maintaining its own properties and information.
 * Please keep performance in mind when writing a hook and don't do anything you don't need to.
 * Instead think about the information that is available on hand and just work with that.
 *
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */
abstract class base {
    /**
     * Execute the callbacks.
     *
     * @return self $this allows chaining
     */
    public function execute() {
        manager::execute($this);
        return $this;
    }
}
