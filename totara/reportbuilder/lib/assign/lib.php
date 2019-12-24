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

global $CFG;
require_once($CFG->dirroot.'/totara/core/lib/assign/lib.php');

/**
 * This class implements list of users (assignments) that someone is allowed to see.
 *
 * It is subject of restrictions.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */
class totara_assign_reportbuilder_record extends totara_assign_core {

    /** @var string Reference to the module. */
    protected static $module = 'reportbuilder';


    protected static $suffix = 'record';

    /**
     * Constructor
     * Don't change signature because it is used in assignment duplicate
     */
    public function __construct($module, $moduleinstance, $suffix = '') {
        parent::__construct('reportbuilder', $moduleinstance, 'record');
    }
}

/**
 * This class represents list of users that have limitations (restrictions).
 *
 * Every user in these assignments
 * can only see list of allowed to them users (which is defined in totara_assign_restriction_record).
 * It is object of restrictions.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */
class totara_assign_reportbuilder_user extends totara_assign_core {

    /** @var string Reference to the module. */
    protected static $module = 'reportbuilder';

    /** @var string Component suffix */
    protected static $suffix = 'user';

    /**
     * Constructor
     * Don't change signature because it is used in assignment duplicate
     */
    public function __construct($module, $moduleinstance, $suffix = '') {
        parent::__construct('reportbuilder', $moduleinstance, 'user');
    }
}
