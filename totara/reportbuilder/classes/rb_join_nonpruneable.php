<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Class defining a report builder join that will not be pruned during optimizations
 *
 * A join object contains all the information required to
 * generate the SQL to include the join in a query, as well
 * as information about any dependencies (other joins) that
 * the join may have, and its relationship to the table(s)
 * it is joining to.
 */


class rb_join_nonpruneable extends rb_join {
    /**
     * Returns always false
     * Thats the sense of this class
     *
     */
    public function pruneable() {
        return false;
    }

}
