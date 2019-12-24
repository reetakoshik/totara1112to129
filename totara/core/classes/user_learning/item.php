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
 * User learning item interface.
 *
 * @package totara_core
 * @category user_learning
 */
interface item {

    /**
     * Returns all instance of this user learning item that the given user is currently being tracked for.
     *
     * What is being tracked is determined by the component/plugin that is serving up the user learning item.
     *
     * @param \stdClass|int $userorid The user record from the database or the id of.
     * @return item[]
     */
    public static function all($userorid);

    /**
     * Returns the user learning item for the given user that has the given id.
     *
     * @param \stdClass|int $userorid The user record from the database or the id of.
     * @param \stdClass|int $itemorid The user learning item record or the id of.
     * @return item
     */
    public static function one($userorid, $itemorid);

    /**
     * Returns the context level that user learning item instances of this type are bound to.
     *
     * @return int One of CONTEXT_*
     */
    public static function get_context_level();

    /**
     * Exports this user learning item instance as context data for use in templates.
     *
     * @return \stdClass
     */
    public function export_for_template();

    /**
     * The component that this user learning item belongs to.
     *
     * @return string
     */
    public function get_component();

    /**
     * The type of this user learning item, should be unique within the component.
     *
     * @return string
     */
    public function get_type();

}
