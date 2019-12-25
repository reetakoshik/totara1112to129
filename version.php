<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * MOODLE VERSION INFORMATION
 *
 * This file defines the current version of the core Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    core
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$version  = 2016120509.00;              // YYYYMMDD      = weekly release date of this DEV branch.
                                        //         RR    = release increments - 00 in DEV branches.
                                        //           .XX = incremental changes.

$release  = '3.2.9 (Build: 20180517)'; // Human-friendly version name

$branch   = '32';                       // This version's branch.
$maturity = MATURITY_STABLE;             // This version's maturity level.


// TOTARA VERSION INFORMATION

// This file defines the current version of the core Totara code being used.
// This can be used for modules to set a minimum functionality requirement.

$TOTARA = new stdClass();

$TOTARA->version    = '11.12';          // Please keep as string.
$TOTARA->build      = '20190214.00';   // Please keep as string.

if ($maturity == MATURITY_EVERGREEN) {
    $TOTARA->release = "Evergreen (Build: {$TOTARA->build})";
} else {
    $TOTARA->release = "{$TOTARA->version} (Build: {$TOTARA->build})";
}
