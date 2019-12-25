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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\local\contentmarketplace;

defined('MOODLE_INTERNAL') || die();

class search_results {

    /** @var array detailing each hit */
    public $hits;

    /** @var array of data to pass to each of the search filters */
    public $filters;

    /** @var int total number of search hits */
    public $total = 0;

    /** @var bool flag to indicate there are more results */
    public $more = false;

    /** @var string to enable different selection modes */
    public $selectionmode = '';

    /** @var string detailing sort mode */
    public $sort = '';

}
