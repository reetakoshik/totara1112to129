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

use totara_contentmarketplace\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Test local class
 *
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_local_testcase extends advanced_testcase {

    protected static function normalise_spaces($text) {
        $text = str_replace("\u{00a0}", ' ', $text);
        $text = str_replace("\u{202f}", ' ', $text);
        return $text;
    }

    /**
     * @dataProvider money_provider
     */
    public function test_format_money($locale, $value, $currency, $expected) {
        $this->resetAfterTest();
        $this->overrideLangString('locale', 'langconfig', $locale);
        $price = local::format_money($value, $currency);
        // Formatter may use different unicode spaces in each OS,
        // just make sure there is some space in result.
        $price = self::normalise_spaces($price);
        $this->assertSame($expected, $price);
    }

    public function money_provider() {
        return [
            ['en_AU.UTF-8', 0, "AUD", 'A$0.00'],
            ['en_AU.UTF-8', 1, "AUD", 'A$1.00'],
            ['en_AU.UTF-8', 1.5, "AUD", 'A$1.50'],
            ['en_AU.UTF-8', 1234.5, "AUD", 'A$1,234.50'],
            ['en_AU.UTF-8', 1, "JPY", '¥1'],
            ['fr_FR.UTF-8', 1234.5, "AUD", "1 234,50 \$AU"],
            ['de_DE.UTF-8', 1234.5, "USD", "1.234,50 \$"],

        ];
    }

    /**
     * @dataProvider integer_provider
     */
    public function test_format_integer($locale, $integer, $expected) {
        $this->resetAfterTest();
        $this->overrideLangString('locale', 'langconfig', $locale);
        $number = local::format_integer($integer);
        // Formatter may use different unicode spaces in each OS,
        // just make sure there is some space in result.
        $number = self::normalise_spaces($number);
        $this->assertSame($expected, $number);
    }

    public function integer_provider() {
        return [
            ['en_AU.UTF-8', 0, "0"],
            ['en_AU.UTF-8', 1, "1"],
            ['en_AU.UTF-8', 1000, "1,000"],
            ['en_AU.UTF-8', 1000000, "1,000,000"],
            ['fr_FR.UTF-8', 1000000, "1 000 000"],
            ['de_DE.UTF-8', 1000000, "1.000.000"],
        ];
    }

    public function test_is_enabled() {
        $this->resetAfterTest(true);
        set_config('enablecontentmarketplaces', 1);
        $this->assertTrue((bool)local::is_enabled());
        set_config('enablecontentmarketplaces', 0);
        $this->assertFalse((bool)local::is_enabled());
        $this->expectException(\moodle_exception::class);
        local::require_contentmarketplace();
    }

}
