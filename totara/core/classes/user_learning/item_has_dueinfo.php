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
 * Interface item_has_dueinfo
 *
 * @package totara_core
 * @category user_learning
 */
interface item_has_dueinfo {

    /**
     * TODO: document me - is my naming correct?
     *
     * @return mixed
     */
    public function get_dueinfo();

    /**
     * Returns information on this user learning items due date requirements.
     *
     * @return \stdClass
     */
    public function export_dueinfo_for_template();
}
