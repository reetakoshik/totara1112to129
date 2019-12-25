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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 * @category user_learning
 */

namespace totara_core\user_learning;

/**
 * Designation Asset
 *
 * An asset is a user learning item that is neither a primary learning item, nor a secondary learning item.
 * It very likely does not represent a component or plugin instance that the user must complete as part of their learning.
 *
 * @package totara_core
 * @category user_learning
 */
trait designation_asset {

    /**
     * Determines if the items is a is a primary item.
     *
     * @return bool
     */
    public static function is_a_primary_user_learning_class() {
        return false;
    }

    /**
     * Returns true if this is a primary learning item, and false if not.
     *
     * @return bool
     */
    public function is_primary_user_learning_item() {
        return false;
    }

}
