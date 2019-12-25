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
 * Interface item_has_progress
 *
 * @package totara_core
 * @category user_learning
 */
interface item_has_progress {

    /**
     * Returns true if this user learning item is completable.
     *
     * @return bool
     */
    public function can_be_completed();

    /**
     * Returns the user progress as a percentage (0 - 1).
     *
     * @return float
     */
    public function get_progress_percentage();

    /**
     * Exports the users progress on this learning item as context data for use with templates.
     *
     * @return \stdClass
     */
    public function export_progress_for_template();
}
