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
 * @author Jon Sharp <jon.sharp@catalyst-eu.net>
 * @package totara
 * @subpackage certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/certification/lib.php');

/**
 * Certification module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit --verbose totara_certification_recertdates_testcase totara/certification/tests/recertdates_test.php
 *
 * @package    totara_certifications
 * @category   phpunit
 * @group      totara_certifications
 * @author     Jon Sharp <jonathans@catalyst-eu.net>
 * @copyright  Catalyst IT Ltd 2013 <http://catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class totara_certification_recertdates_testcase extends advanced_testcase {

    /**
     * Test get_certiftimebase, get_timeexpires and get_timewindowopens.
     */
    public function test_recertdates() {

        $unuseddate = strtotime('22-July-2010 02:22'); // This date can be used to ensure that a parameter is correctly ignored.
        $unusedperiod = '7 year'; // This period can be used to ensure that a parameter is correctly ignored.

        // Certification is using CERTIFRECERT_EXPIRY to calculate the next expiry date.
        // User is in the recertification stage with the window open.
        $activeperiod = '1 year';
        $windowperiod = '1 month';
        $curtimeexpires = strtotime('3-May-2013 08:14');
        $timecompleted = strtotime('15-April-2013 12:01');

        $base = get_certiftimebase(CERTIFRECERT_EXPIRY, $curtimeexpires, $timecompleted, $unuseddate,
            $unusedperiod, $unusedperiod, $windowperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate(strtotime('3-May-2014 08:14')), userdate($newtimeexpires)); // One year after previous expiry date.
        $this->assertEquals(userdate(strtotime('3-Apr-2014 08:14')), userdate($timewindowopens));

        unset($activeperiod, $windowperiod, $curtimeexpires, $timecompleted);

        // Certification is using CERTIFRECERT_EXPIRY to calculate the next expiry date.
        // User is in the recertification stage before the window is open (caused by completion upload).
        $activeperiod = '1 year';
        $windowperiod = '1 month';
        $curtimeexpires = strtotime('3-May-2013 08:14');
        $timecompleted = strtotime('15-February-2013 12:01');

        $base = get_certiftimebase(CERTIFRECERT_EXPIRY, $curtimeexpires, $timecompleted, $unuseddate,
            $activeperiod, $unusedperiod, $windowperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate(strtotime('3-May-2013 08:14')), userdate($newtimeexpires)); // Equal to current expiry date!
        $this->assertEquals(userdate(strtotime('3-Apr-2013 08:14')), userdate($timewindowopens));

        unset($activeperiod, $windowperiod, $curtimeexpires, $timecompleted);

        // Certification is using CERTIFRECERT_EXPIRY to calculate the next expiry date.
        // User is in the primary certification stage, with an assignment due date set.
        $activeperiod = '1 year';
        $windowperiod = '1 month';
        $timedue = strtotime('3-May-2013 08:14');
        $timecompleted = strtotime('15-April-2013 12:01');

        $base = get_certiftimebase(CERTIFRECERT_EXPIRY, null, $timecompleted, $timedue,
            $unusedperiod, $unusedperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate(strtotime('3-May-2014 08:14')), userdate($newtimeexpires));// One year after assignment due date.
        $this->assertEquals(userdate(strtotime('3-Apr-2014 08:14')), userdate($timewindowopens));

        unset($activeperiod, $windowperiod, $timedue, $timecompleted);

        // Certification is using CERTIFRECERT_EXPIRY to calculate the next expiry date.
        // User is in the primary certification stage, with no assignment due date set.
        $activeperiod = '1 year';
        $windowperiod = '1 month';
        $timecompleted = strtotime('15-April-2013 12:01');

        $base = get_certiftimebase(CERTIFRECERT_EXPIRY, null, $timecompleted, null,
            $unusedperiod, $unusedperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate(strtotime('15-April-2014 12:01')), userdate($newtimeexpires)); // One year after date completed.
        $this->assertEquals(userdate(strtotime('15-March-2014 12:01')), userdate($timewindowopens));

        unset($activeperiod, $windowperiod, $timecompleted);

        // Certification is using CERTIFRECERT_COMPLETION to calculate the next expiry date.
        // User is in the recertification stage.
        $activeperiod = '1 year';
        $windowperiod = '1 month';
        $timecompleted = strtotime('15-April-2013 12:01');

        $base = get_certiftimebase(CERTIFRECERT_COMPLETION, $unuseddate, $timecompleted, $unuseddate,
            $unusedperiod, $unusedperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate(strtotime('15-April-2014 12:01')), userdate($newtimeexpires)); // One year after the completion date.
        $this->assertEquals(userdate(strtotime('15-March-2014 12:01')), userdate($timewindowopens));

        unset($activeperiod, $windowperiod, $timecompleted);

        // Certification is using CERTIFRECERT_COMPLETION to calculate the next expiry date.
        // User is in the primary certification stage, with an assignment due date set.
        $activeperiod = '1 year';
        $windowperiod = '1 month';
        $timecompleted = strtotime('15-April-2013 12:01');

        $base = get_certiftimebase(CERTIFRECERT_COMPLETION, null, $timecompleted, $unuseddate,
            $unusedperiod, $unusedperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate(strtotime('15-April-2014 12:01')), userdate($newtimeexpires)); // One year after the completion date.
        $this->assertEquals(userdate(strtotime('15-March-2014 12:01')), userdate($timewindowopens));

        unset($activeperiod, $windowperiod, $timecompleted);

        // Certification is using CERTIFRECERT_COMPLETION to calculate the next expiry date.
        // User is in the primary certification stage, with no assignment due date set.
        $activeperiod = '1 year';
        $windowperiod = '1 month';
        $timecompleted = strtotime('15-April-2013 12:01');

        $base = get_certiftimebase(CERTIFRECERT_COMPLETION, null, $timecompleted, null,
            $unusedperiod, $unusedperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate(strtotime('15-April-2014 12:01')), userdate($newtimeexpires)); // One year after the completion date.
        $this->assertEquals(userdate(strtotime('15-March-2014 12:01')), userdate($timewindowopens));

        unset($activeperiod, $windowperiod, $timecompleted);

        // Certification is using CERTIFRECERT_COMPLETION to calculate the next expiry date (window period is weeks).
        // User is in the recertification stage.
        $activeperiod = '1 year';
        $windowperiod = '3 week';
        $curtimeexpires = strtotime('3-May-2013 08:14');
        $timecompleted = strtotime('15-April-2013 12:01');

        $base = get_certiftimebase(CERTIFRECERT_COMPLETION, $curtimeexpires, $timecompleted, null,
            $unusedperiod, $unusedperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate(strtotime('15-April-2014 12:01')), userdate($newtimeexpires)); // One year after the completion date.
        $this->assertEquals(userdate(strtotime('25-March-2014 12:01')), userdate($timewindowopens));

        unset($activeperiod, $windowperiod, $curtimeexpires, $timecompleted);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // User is in the recertification stage.
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '1 month';
        $currentyear = date("Y");
        $currentmonth = date("n");
        $currentday = date("j");
        // We calculate these backwards - start by calculating today, then choose a current expiry date
        // which means that today is within the recertification window.
        $timecompleted = mktime(8, 14, 0, $currentmonth, $currentday, $currentyear); // Today at 8:14am.
        $curtimeexpires = strtotime('2 week', $timecompleted); // Due in two weeks.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, $curtimeexpires, $timecompleted, $unuseddate,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $expectedtimeexpired = strtotime('1 year', $curtimeexpires); // One year after the current expiry date.
        $this->assertEquals(userdate($expectedtimeexpired), userdate($newtimeexpires));
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $expectedtimeexpired)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timecompleted, $curtimeexpires, $expectedtimeexpired);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // User is in the primary certification stage, with an assignment due date set beyond the minimum active period.
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '1 month';
        $currentyear = date("Y");
        $currentmonth = date("n");
        $currentday = date("j");
        // We calculate these backwards - start by calculating today, then choose an assignment due date
        // which is beyond the minimum active period from today.
        $timecompleted = mktime(8, 14, 0, $currentmonth, $currentday, $currentyear); // Today at 8:14am.
        $timedue = strtotime('8 month', $timecompleted); // Due in eight months, bigger than the minimum active period.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, null, $timecompleted, $timedue,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $this->assertEquals(userdate($timedue), userdate($newtimeexpires)); // Should just be the assignment due date.
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $timedue)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timecompleted, $timedue);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // User is in the primary certification stage, with an assignment due date set within the minimum active period.
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '1 month';
        $currentyear = date("Y");
        $currentmonth = date("n");
        $currentday = date("j");
        // We calculate these backwards - start by calculating today, then choose an assignment due date
        // which is within the minimum active period from today.
        $timecompleted = mktime(8, 14, 0, $currentmonth, $currentday, $currentyear); // Today at 8:14am.
        $timedue = strtotime('4 month', $timecompleted); // Due in four months, thess than the minimum active period.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, null, $timecompleted, $timedue,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $expectedtimeexpired = strtotime($activeperiod, $timedue); // 1 year after the assignment due date.
        $this->assertEquals(userdate($expectedtimeexpired), userdate($newtimeexpires));
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $expectedtimeexpired)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timedue, $timecompleted, $expectedtimeexpired);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // User is in the primary certification stage, with no assignment due date set.
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '1 month';
        $currentyear = date("Y");
        $currentmonth = date("n");
        $currentday = date("j");
        $timecompleted = mktime(8, 14, 0, $currentmonth, $currentday, $currentyear); // Today at 8:14am.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, null, $timecompleted, null,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $expectedtimeexpired = strtotime($activeperiod, $timecompleted); // 1 year after the date completed.
        $this->assertEquals(userdate($expectedtimeexpired), userdate($newtimeexpires));
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $expectedtimeexpired)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timecompleted, $expectedtimeexpired);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // User is in the primary certification stage, with an assignment due date set FAR beyond the minimum active
        // period, and several "active period"s into the future.
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '1 month';
        $currentyear = date("Y");
        $currentmonth = date("n");
        $currentday = date("j");
        // We calculate these backwards - start by calculating today, then choose an assignment due date
        // which is beyond the minimum active period from today.
        $timecompleted = mktime(8, 14, 0, $currentmonth, $currentday, $currentyear); // Today at 8:14am.
        $timedue = strtotime('128 month', $timecompleted); // Due in 10 years and 8 months.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, null, $timecompleted, $timedue,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        $expectedtimeexpired = strtotime('-10 year', $timedue); // 10 years (active periods) before the assignment due date.
        $this->assertEquals(userdate($expectedtimeexpired), userdate($newtimeexpires));
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $expectedtimeexpired)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timecompleted, $timedue, $expectedtimeexpired);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // User is in the primary certification stage, with an assignment due date FAR in the past (overdue).
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '1 month';
        $currentyear = date("Y");
        $currentmonth = date("n");
        $currentday = date("j");
        // We calculate these backwards - start by calculating today, then choose an assignment due date
        // which is far in the past.
        $timecompleted = mktime(8, 14, 0, $currentmonth, $currentday, $currentyear); // Today at 8:14am.
        $timedue = strtotime('-119 month', $timecompleted); // Due 9 years and eleven months ago.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, null, $timecompleted, $timedue,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        // The "recurring" date is in one month's time. This is within the minimum active period, so the
        // expiry date should be period after that, so eleven years after the assignment due date.
        $expectedtimeexpired = strtotime('11 year', $timedue);
        $this->assertEquals(userdate($expectedtimeexpired), userdate($newtimeexpires));
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $expectedtimeexpired)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timecompleted, $timedue, $expectedtimeexpired);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // Upload of completion record in the past, before minimum active period (e.g. new assignment).
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '2 month';
        $timecompleted = mktime(8, 14, 0, 1, 4, 2012); // April 1 2012 at 8:14am.
        $timedue = mktime(0, 0, 0, 12, 31, 2012); // Due 31 December 2012.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, null, $timecompleted, $timedue,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        // Time completed was less than minimum from time due, so new expiry is the same as time due.
        $expectedtimeexpired = $timedue;
        $this->assertEquals(userdate($expectedtimeexpired), userdate($newtimeexpires));
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $expectedtimeexpired)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timecompleted, $timedue, $expectedtimeexpired);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // Upload of completion record in the past, within window.
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '2 month';
        $timecompleted = mktime(8, 14, 0, 11, 5, 2012); // November 5 2012 at 8:14am.
        $timedue = mktime(0, 0, 0, 12, 31, 2012); // December 31 2012.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, $timedue, $timecompleted, $timedue,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        // Time completed is less than minimum active period from time due, so time expires must be one active period further.
        $expectedtimeexpired = strtotime($activeperiod, $timedue);
        $this->assertEquals(userdate($expectedtimeexpired), userdate($newtimeexpires));
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $expectedtimeexpired)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timecompleted, $timedue, $expectedtimeexpired);

        // Certification is using CERTIFRECERT_FIXED to calculate the next expiry date.
        // Upload of completion record in the past, expired.
        $activeperiod = '1 year';
        $minimumactiveperiod = '6 month';
        $windowperiod = '2 month';
        $timecompleted = mktime(8, 14, 0, 2, 4, 2013); // February 4 2013 at 8:14am.
        $timedue = mktime(0, 0, 0, 12, 31, 2012); // Due December 31 2012.

        $base = get_certiftimebase(CERTIFRECERT_FIXED, null, $timecompleted, $timedue,
            $activeperiod, $minimumactiveperiod, $unusedperiod);
        $newtimeexpires = get_timeexpires($base, $activeperiod);
        $timewindowopens = get_timewindowopens($newtimeexpires, $windowperiod);

        // Expired and more than minimum active period from next expiry date.
        $expectedtimeexpired = strtotime($activeperiod, $timedue);
        $this->assertEquals(userdate($expectedtimeexpired), userdate($newtimeexpires));
        $this->assertEquals(userdate(strtotime('-' . $windowperiod, $expectedtimeexpired)), userdate($timewindowopens));

        unset($activeperiod, $minimumactiveperiod, $windowperiod, $timecompleted, $timedue, $expectedtimeexpired);

    }
}
