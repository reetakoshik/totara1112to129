<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @copyright 2017 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @package   mod_feedback
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("{$CFG->dirroot}/mod/feedback/item/numeric/lib.php");

class feedback_item_numeric_testcase extends advanced_testcase {

    /**
     * It should test the format of user entered range values.
     *
     * The feedback_numeric_form contains two range fields which
     * permit the hyphen to be entered OR a valid numeric value which
     * depends on the current locale. Prior to this validation step
     * introduced in TL-11674 / MDL-53557 any unrecognised value
     * was forced to '-' representing intentionally omitted value leading
     * to confusing results.
     *
     * Note: Number formatting is locale-specific. The following tests
     * based on the assumption that we're using EN as that is the only
     * language pack bundled with the core distribution. The validation
     * *should* be locale aware via unformat_float() however this requires
     * access to corresponding language packs and therefore cannot
     * be reliably added as a unit-test here.
     */
    public function test_it_validates_range_values_en() {
        global $CFG;

        // See PHPDoc notes above.
        $this->assertEquals($CFG->lang, 'en');

        // - Empty should pass as it is then forced to '-'.
        // - '-4 2.34' while strange looking is permissible by unformat_float as in some
        //   locales a space may be used after thousands and is stripped.
        // - Decimal point represented by a comma will only be replaced by unformat_float
        //   based on the value of a string in the current language pack (e.g. it is
        //   permissible in DE but will fail in EN).
        // - While having the minus sign after a value is permissible in some locales
        //   it is not currently supported by unformat_float() and therefore will not
        //   work at all here.
        $valid = [ '23', '1.23', '-', "  \n  -\t\t", '-42.43', ' -4243  ', '-4 2.34', '' ];
        $notvalid = [ 34, true, 'foo', "-foo", ' 10985-', '234,24' ];

        foreach ($valid as $value) {
            $message = "'{$value}' failed validation but was expected to pass.";
            $this->assertTrue(feedback_item_numeric::is_valid_range_value($value), $message);
        }

        foreach ($notvalid as $value) {
            $message = "'{$value}' passed validation but was expected to fail.";
            $this->assertFalse(feedback_item_numeric::is_valid_range_value($value), $message);
        }
    }

}
