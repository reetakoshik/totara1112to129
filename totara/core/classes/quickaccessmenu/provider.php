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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */

namespace totara_core\quickaccessmenu;

/**
 * Provider interface
 *
 * Components, plugins, and sub plugins wishing to add items to the quick access menu need to define a class that implements this
 * interface.
 *
 * @package totara_core
 */
interface provider {

    /**
     * Return the items that this component / plugin / sub plugin wishes to introduce to the quick access menu.
     *
     * @return item[]
     */
    public static function get_items(): array;

}
